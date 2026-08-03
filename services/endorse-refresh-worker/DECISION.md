# Endorse-refresh Rust worker — disposition decision

**Decision: A — leave disabled intentionally.** Do not enable, repair for activation, or
remove as part of the current correctness work.

## What it is (evidence)

- Source: `services/endorse-refresh-worker/` (Rust, `src/main.rs`, ~1131 lines). A second
  Rust service `services/tiktok-worker-rust/` also exists.
- Role (from its own header): a **continuous fetch executor**. It does **not** write
  `endorse` / `endorse_logs` / the queue directly. It:
  1. `POST /api/endorse-refresh/claim` → PHP claims + rate-caps and returns fetch-ready items;
  2. fetches (scrape / RapidAPI fallback) concurrently;
  3. `POST /api/endorse-refresh/result` → PHP applies the results.
  So **PHP owns claim, rate limiting, and result application**; Rust owns only outbound fetch.
- Deployment: Swarm service `forbes_endorse-refresh-worker`, image
  `gilangp/forbes-endorse-refresh-worker:develop4f9b0b1a`, **0/0 replicas**. `ENDORSE_REFRESH_DRIVER=cron`.
- `deploy/develop.yml` explicitly preserves the 0/0 replica count on image updates; activation
  is documented as "a separate, explicit rollout step."

## Queue ownership & accidental-duplication risk

- The per-minute PHP cron (`Api_v2::cronjob_endorse_refresh`) is the authoritative worker and
  **stands down only when `DRIVER=rust`** (`Api_v2.php:8191`).
- The claim endpoint (`endorse_refresh_claim`) does **not** itself hard-gate on `DRIVER=rust`;
  mutual exclusion today is **deployment-based** (worker scaled to 0/0). The atomic claim
  UPDATE (`worker_id IS NULL`) still prevents double-claim even if both ran, but lease/owner
  semantics differ (`claim_owner='rust'`), so concurrent PHP-cron + Rust is **not a supported
  configuration**.

## Rust ↔ PHP parity vs the new correctness contract

| Concern | PHP `applyResults` (cron/incremental) | Rust `applyResultsV2` (coordinator `/result`) |
|---|---|---|
| Logical observation sequence (`stats_observation_seq`) | injects `queue_id` ✓ | **now injects `queue_id` ✓ (fixed this round)** |
| Atomic ordering guard in `Endorse_sync::apply` | yes (shared apply) ✓ | yes (shared apply) ✓ |
| Per-item **transaction** (endorse+log+queue+attempt) | **yes** ✓ | **no — not yet wrapped** ✗ |
| Crash-before-log recovery | yes ✓ | inherits apply-level duplicate recovery only, no queue-txn ✗ |
| Rate reservation scope | (incremental path) | PHP-owned via claim ✓ |

**Conclusion:** after this round's `observation_seq` fix, the Rust `/result` path is
ordering-safe, but it still **lacks the per-item transactional crash consistency** that
`applyResults` now has. Enabling Rust would therefore route result application through a
less-crash-consistent path, with no proven throughput need.

## Why disabled (Decision A rationale)

1. PHP cron is the authoritative, correctness-hardened worker.
2. No throughput requirement has been proven that the PHP path cannot meet.
3. `applyResultsV2` is not yet at full transactional parity.
4. Mutual exclusion is deployment-based; activation must be a deliberate `DRIVER=rust` rollout.
5. Enabling during a correctness rollout adds risk for no benefit.

## Guardrails

- Keep the service at **0/0**; `deploy/develop.yml` already preserves this on image updates.
- Do **not** set `ENDORSE_REFRESH_DRIVER=rust` without a separate, approved migration project.
- Prerequisites before any future activation (Decision C/D): wrap `applyResultsV2` in the same
  per-item transaction; hard-gate the claim endpoint on `DRIVER=rust`; shadow-mode compare
  business results; add the same shared ordering/crash/limiter contract tests against the Rust path.

## Not doing now

- Not removing the Rust service (no evidence it must go; removal should be a separate reviewed
  change that proves no dependency).
- Not making Rust authoritative (separate migration project, out of scope).
