// Forbes (Acneno) FULL-feature load test — k6
// Covers the real endpoints users hit (from prod access log 2026-06-22) with per-journey
// tags, so a run ranks which feature/endpoint breaks first. Staged ramp to 400 VU with
// abort guardrails. Prod off-hours only.
//
// Run:
//   set -a; source loadtest/.env; set +a
//   SCENARIO=smoke   k6 run loadtest/k6/forbes-full-loadtest.js
//   SCENARIO=ramp    k6 run --out json=loadtest/results/ramp.json loadtest/k6/forbes-full-loadtest.js
//
// Env: BASE_URL, TEST_EMAILS, TEST_PASSWORDS (comma lists), CAMPAIGN_IDS, SCENARIO.
// SAFETY: read-only GET journeys + login. Guardrails auto-abort (err>10% or p95>3s sustained).

import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Trend, Rate } from 'k6/metrics';

function randInt(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }

const BASE_URL = __ENV.BASE_URL || 'https://acnenosystem.com';
const EMAILS = (__ENV.TEST_EMAILS || '').split(',').map(s => s.trim()).filter(Boolean);
const PASSWORDS = (__ENV.TEST_PASSWORDS || 'LoadTest#2026').split(',').map(s => s.trim()).filter(Boolean);
const CAMPAIGN_IDS = (__ENV.CAMPAIGN_IDS || '35,37,46,47,48').split(',').map(s => s.trim());
const SCENARIO = __ENV.SCENARIO || 'smoke';

// date range used by the real frontend
const D = { start: '2026-06-01', until: '2026-06-22' };
const SUMMARY_QS =
  `&type=Daily&start_date=${D.start}&until_date=${D.until}` +
  `&start_year=2026&until_year=2026&start_month=1&until_month=06&start_week=1&until_week=26`;

const jDur = new Trend('journey_duration', true);
const jErr = new Rate('journey_errors');

const SCENARIOS = {
  smoke: { executor: 'constant-vus', vus: 2, duration: '2m' },
  // staged ramp — find the knee at each step on the way to 400
  ramp: {
    executor: 'ramping-vus', startVUs: 0, stages: [
      { duration: '2m', target: 20 }, { duration: '3m', target: 20 },
      { duration: '2m', target: 50 }, { duration: '3m', target: 50 },
      { duration: '2m', target: 100 }, { duration: '3m', target: 100 },
      { duration: '2m', target: 200 }, { duration: '3m', target: 200 },
      { duration: '3m', target: 400 }, { duration: '5m', target: 400 },
      { duration: '2m', target: 0 },
    ],
  },
  soak: { executor: 'constant-vus', vus: 50, duration: __ENV.SOAK_DURATION || '30m' },
};

export const options = {
  scenarios: { [SCENARIO]: { ...SCENARIOS[SCENARIO], gracefulStop: '30s' } },
  thresholds: {
    http_req_failed: [{ threshold: 'rate<0.10', abortOnFail: true, delayAbortEval: '30s' }],
    http_req_duration: [{ threshold: 'p(95)<3000', abortOnFail: true, delayAbortEval: '1m' }],
    'http_req_duration{journey:dashboard}': ['p(95)<2500'],
    'http_req_duration{journey:endorse_list}': ['p(95)<2500'],
    'http_req_duration{journey:campaign_chart}': ['p(95)<3000'],
    'http_req_duration{journey:report}': ['p(95)<2500'],
    'http_req_duration{journey:transaction}': ['p(95)<2000'],
    journey_errors: ['rate<0.02'],
  },
  noConnectionReuse: false,
  userAgent: 'k6-forbes-full/1.0',
};

const vuState = {};
function ensureLogin() {
  if (vuState[__VU]) return true;
  if (!EMAILS.length) throw new Error('TEST_EMAILS required');
  const i = (__VU - 1) % EMAILS.length;
  const email = EMAILS[i];
  const password = PASSWORDS[i % PASSWORDS.length];
  const res = http.post(`${BASE_URL}/auth/login_process`, { email, password },
    { tags: { journey: 'auth' }, redirects: 5 });
  vuState[__VU] = check(res, { 'login ok': r => r.status === 200 && r.body && r.body.includes('Selamat datang') });
  return vuState[__VU];
}

function hit(url, name) {
  const r = http.get(url, { tags: { journey: name } });
  jDur.add(r.timings.duration, { journey: name });
  jErr.add(r.status === 0 || r.status >= 400, { journey: name });
  check(r, { [`${name} ok`]: x => x.status >= 200 && x.status < 400 });
  return r;
}
function cid() { return CAMPAIGN_IDS[randInt(0, CAMPAIGN_IDS.length - 1)]; }

// ---- journeys (real endpoints, real params) ----
function jDashboard() {
  group('dashboard', () => {
    hit(`${BASE_URL}/dashboard`, 'dashboard');
    // the fan-out a browser fires (a representative subset of the ~23 cards)
    ['order-1', 'order-4', 'order-7', 'order-9', 'order-11', 'order-23'].forEach(id =>
      hit(`${BASE_URL}/ajax/get_summary?site=&id=${id}&brand=&channel=${SUMMARY_QS}`, 'dashboard'));
  });
}
function jEndorseList() {
  group('endorse_list', () => {
    const c = cid();
    hit(`${BASE_URL}/endorse?id_campaign=${c}`, 'endorse_list');
    hit(`${BASE_URL}/endorse/item?id_campaign=${c}&sort_column=id&sort_order=DESC`, 'endorse_list');
  });
}
function jCampaignChart() {
  group('campaign_chart', () => {
    const c = cid();
    hit(`${BASE_URL}/ajax/get-chart-campaign?id_campaign=${c}&chart_start_date=${D.start}&chart_until_date=${D.until}&start_date=${D.start}&until_date=${D.until}`, 'campaign_chart');
    hit(`${BASE_URL}/ajax/get-summary-campaign?id_campaign=${c}&start_date=${D.start}&until_date=${D.until}`, 'campaign_chart');
    hit(`${BASE_URL}/ajax/get-filter?id_campaign=${c}`, 'campaign_chart');
  });
}
function jReport() { group('report', () => { hit(`${BASE_URL}/report`, 'report'); hit(`${BASE_URL}/ajax/get-report`, 'report'); }); }
function jTransaction() { group('transaction', () => hit(`${BASE_URL}/transaction`, 'transaction')); }
function jInfluencer() { group('influencer', () => hit(`${BASE_URL}/influencer`, 'influencer')); }

// weighted to match real usage (endorse-campaign feature is dominant)
function pick() {
  const n = randInt(1, 100);
  if (n <= 30) return jCampaignChart;   // the hottest + heaviest
  if (n <= 50) return jEndorseList;
  if (n <= 70) return jDashboard;
  if (n <= 82) return jReport;
  if (n <= 92) return jTransaction;
  return jInfluencer;
}

export default function () {
  if (!ensureLogin()) { sleep(5); return; }
  pick()();
  sleep(randInt(5, 15)); // think time
}
