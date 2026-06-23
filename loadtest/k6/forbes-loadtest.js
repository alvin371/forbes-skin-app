// Forbes (Acneno) load test — k6
// Plan: docs/2026-06-20-load-test-plan.md
//
// Run (see loadtest/k6/README.md):
//   SCENARIO=smoke    k6 run forbes-loadtest.js
//   SCENARIO=baseline k6 run --out json=../results/baseline.json forbes-loadtest.js
//   SCENARIO=soak     k6 run forbes-loadtest.js
//
// Env vars:
//   BASE_URL      default https://acnenosystem.com
//   TEST_EMAIL    test account email           (REQUIRED for authed journeys)
//   TEST_PASSWORD test account password        (REQUIRED)
//   CAMPAIGN_IDS  comma list of id_campaign to exercise the rollup (e.g. "34,35,36")
//   SCENARIO      smoke|baseline|stress|spike|soak  (default smoke)
//
// SAFETY: read-only (GET) journeys + login. Guardrails auto-abort (err>10% or p95>3s).
// Do NOT add cron / webhook / write endpoints. Prod off-hours only.

import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Trend, Rate } from 'k6/metrics';
// vendored locally (was https://jslib.k6.io/k6-utils) to avoid flaky remote fetch mid-run
function randomIntBetween(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }

const BASE_URL = __ENV.BASE_URL || 'https://acnenosystem.com';
// Single account: TEST_EMAIL + TEST_PASSWORD.
// Multi-account fallback (if app enforces one-session-per-user): TEST_EMAILS + TEST_PASSWORDS
// as comma lists (same length), round-robined per VU.
const EMAILS = (__ENV.TEST_EMAILS || __ENV.TEST_EMAIL || '').split(',').map(s => s.trim()).filter(Boolean);
const PASSWORDS = (__ENV.TEST_PASSWORDS || __ENV.TEST_PASSWORD || '').split(',').map(s => s.trim()).filter(Boolean);
const CAMPAIGN_IDS = (__ENV.CAMPAIGN_IDS || '34').split(',').map(s => s.trim());
const SCENARIO = __ENV.SCENARIO || 'smoke';

// ---- think time (5-15s, avg ~10s) ----
function thinkTime() { sleep(randomIntBetween(5, 15)); }

// ---- per-journey custom metrics ----
const journeyDur = new Trend('journey_duration', true);
const journeyErr = new Rate('journey_errors');

// ---- scenario definitions (pick one via SCENARIO env) ----
const SCENARIOS = {
  smoke:    { executor: 'constant-vus', vus: 2,  duration: '2m' },
  baseline: { executor: 'ramping-vus', startVUs: 0,
              stages: [{ duration: '3m', target: 100 }, { duration: '10m', target: 100 }, { duration: '1m', target: 0 }] },
  stress:   { executor: 'ramping-vus', startVUs: 0,
              stages: [{ duration: '3m', target: 100 }, { duration: '5m', target: 200 },
                       { duration: '5m', target: 300 }, { duration: '5m', target: 400 }, { duration: '2m', target: 0 }] },
  spike:    { executor: 'ramping-vus', startVUs: 50,
              stages: [{ duration: '30s', target: 500 }, { duration: '2m', target: 500 }, { duration: '1m', target: 50 }] },
  soak:     { executor: 'constant-vus', vus: 100, duration: __ENV.SOAK_DURATION || '45m' },
  // knee-finder: hold a fixed low VU level for KNEE_DUR; sweep externally (10,15,20,25,30...).
  // baseline aborted ~34 VU (p95 14s, 0% err) so the real knee sits in this low range.
  knee:     { executor: 'constant-vus', vus: Number(__ENV.KNEE_VUS || 10), duration: __ENV.KNEE_DUR || '2m' },
};

export const options = {
  scenarios: { [SCENARIO]: { ...SCENARIOS[SCENARIO], gracefulStop: '30s' } },
  thresholds: {
    // GUARDRAILS — auto-abort to protect prod
    http_req_failed: [{ threshold: 'rate<0.10', abortOnFail: true, delayAbortEval: '30s' }],
    http_req_duration: [{ threshold: 'p(95)<3000', abortOnFail: true, delayAbortEval: '1m' }],
    // SLO targets (reported, non-aborting)
    'http_req_duration{journey:dashboard}': ['p(95)<1500'],
    'http_req_duration{journey:rollup}': ['p(95)<2500'],
    'http_req_duration{journey:charts}': ['p(95)<2500'],
    'http_req_duration{journey:transaction}': ['p(95)<1500'],
    journey_errors: ['rate<0.01'],
  },
  // be a polite client
  noConnectionReuse: false,
  userAgent: 'k6-forbes-loadtest/1.0',
};

// ---- per-VU login state ----
const vuState = {}; // { [__VU]: loggedIn }

function ensureLogin() {
  if (vuState[__VU]) return true;
  if (!EMAILS.length || !PASSWORDS.length) {
    throw new Error('TEST_EMAIL(S) / TEST_PASSWORD(S) env required for authed journeys');
  }
  // round-robin account per VU (multi-account fallback for one-session-per-user apps)
  const i = (__VU - 1) % EMAILS.length;
  const email = EMAILS[i];          // NOTE: this is the USERNAME (login matches username col)
  const password = PASSWORDS[i % PASSWORDS.length];
  // VERIFIED: POST /auth/login_process, field `email` = username, `password` = plaintext.
  // Success => body contains "Selamat datang"; failure => "Pastikan ...".
  const res = http.post(`${BASE_URL}/auth/login_process`, { email, password },
    { tags: { journey: 'auth' }, redirects: 5 });
  const ok = check(res, { 'login ok': r => r.status === 200 && r.body && r.body.includes('Selamat datang') });
  vuState[__VU] = ok;
  return ok;
}

// ---- journeys (read-only) ----
function jDashboard() {
  group('dashboard', () => {
    const r = http.get(`${BASE_URL}/dashboard`, { tags: { journey: 'dashboard' } });
    recordJourney(r, 'dashboard');
  });
}
function jRollup() {
  group('endorse_campaign_detail', () => {
    const cid = CAMPAIGN_IDS[randomIntBetween(0, CAMPAIGN_IDS.length - 1)];
    // VERIFIED 2026-06-20: 200 OK (Endorse.php logs())
    const r = http.get(`${BASE_URL}/endorse/logs?id_campaign=${cid}`, { tags: { journey: 'rollup' } });
    recordJourney(r, 'rollup');
  });
}
function jCharts() {
  group('charts', () => {
    const cid = CAMPAIGN_IDS[randomIntBetween(0, CAMPAIGN_IDS.length - 1)];
    const until = new Date();
    const start = new Date(Date.now() - 31 * 864e5);
    const f = d => d.toISOString().slice(0, 10);
    // VERIFIED 2026-06-20: 200 OK (Ajax.php get_chart_endorse ~line 123)
    const url = `${BASE_URL}/ajax/get_chart_endorse?id_campaign=${cid}&type=daily` +
                `&start_date=${f(start)}&until_date=${f(until)}&brand=`;
    const r = http.get(url, { tags: { journey: 'charts' } });
    recordJourney(r, 'charts');
  });
}
function jTransaction() {
  group('transaction', () => {
    const r = http.get(`${BASE_URL}/transaction`, { tags: { journey: 'transaction' } });
    recordJourney(r, 'transaction');
  });
}

function recordJourney(res, name) {
  journeyDur.add(res.timings.duration, { journey: name });
  const bad = res.status === 0 || res.status >= 400;
  journeyErr.add(bad, { journey: name });
  check(res, { [`${name} ok`]: r => r.status >= 200 && r.status < 400 });
}

// weighted journey picker (dashboard 30 / rollup 25 / charts 25 / transaction 20)
function pickJourney() {
  const n = randomIntBetween(1, 100);
  if (n <= 30) return jDashboard;
  if (n <= 55) return jRollup;
  if (n <= 80) return jCharts;
  return jTransaction;
}

export default function () {
  if (!ensureLogin()) { sleep(5); return; }
  pickJourney()();
  thinkTime();
}
