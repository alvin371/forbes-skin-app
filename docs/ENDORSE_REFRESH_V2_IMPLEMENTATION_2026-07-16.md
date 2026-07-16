# Endorse Refresh v2 Implementation

Date: 2026-07-16
Branch: `agent/endorse-refresh-contract-v2`

## What was implemented

- Added the additive contract v2 schema migration:
  - `endorse_refresh_runtime_control`
  - `endorse_refresh_provider_health`
  - `endorse_refresh_worker_health`
  - `endorse_refresh_fallback_calls`
  - `endorse_refresh_quarantine`
  - strict active-attempt identity columns and indexes on `endorse_refresh_queue`
  - strict uniqueness and active lookup indexes on `endorse_refresh_queue_attempts`
  - partial-observation metadata on `endorse` and `endorse_logs`
  - duplicate-campaign-log archive/report tables for paused activation

- Applied the migration on 2026-07-16 using `php migrations/run.php 20260716093000_add_endorse_refresh_contract_v2.php`.

- Added `EndorseRefreshV2Coordinator` as the central PHP coordinator for:
  - contract version validation
  - boot UUID worker validation
  - runtime ownership validation
  - active claim identity validation
  - fallback lease and cache handling
  - provider circuit handling
  - transactional batch `/result`
  - claim release without attempt charge
  - content-key quarantine lookup

- Switched the Rust-facing worker endpoints to strict contract v2:
  - `POST /api/endorse-refresh/claim`
  - `POST /api/endorse-refresh/fetch-fallback`
  - `POST /api/endorse-refresh/release`
  - `POST /api/endorse-refresh/result`

- Added queue/direct-sync quarantine enforcement:
  - quarantined TikTok content IDs are skipped from enqueue
  - direct row sync refuses quarantined content
  - changing `link_upload` to a different TikTok content ID makes the content eligible again

- Changed the stat contract so queue processing can distinguish:
  - valid `0`
  - absent field
  - partial observation
  - complete observation

- Updated the Rust worker to:
  - generate one UUID v4-format worker ID per process boot
  - send `contract_version=2`
  - carry `queue_id`, `attempt_no`, `active_attempt_id`, and `worker_id`
  - release claims on provider circuit pauses instead of charging attempts
  - preserve nullable stat semantics

## Verification completed

- `php -l` passed for:
  - `application/controllers/Api_v2.php`
  - `application/controllers/Endorse.php`
  - `application/libraries/EndorseRefreshQueueService.php`
  - `application/libraries/EndorseRefreshV2Coordinator.php`
  - `application/libraries/Endorse_sync.php`
  - `application/libraries/Template.php`
  - `migrations/20260716093000_add_endorse_refresh_contract_v2.php`

- `php vendor/bin/phpunit tests/Unit/EndorseRefreshQueuePolicyTest.php` passed.

- `cargo test` passed in `services/endorse-refresh-worker`.

## Current operational status

- Production should remain on PHP cron ownership.
- Rust ownership is still blocked by the P1 release gates.
- The migration is additive only; activation and ownership transfer are separate operational steps.
