# Audit: every path that syncs outside the agreed schedule

Requirement: all endorse syncing must happen only inside the agreed windows — forbes all-sync
at 00:05, sec-forbes at 05:00 and 16:00 — with no leakage outside them.

Audited 2026-08-19 against the live crontab and `Api_v2`. **The requirement is not met today.**
Four cron paths sync or enqueue outside any schedule, and two of them call the provider
*directly*, bypassing both the queue and the rate limiter.

---

## Leakage sources

### 1. `cronjob_endorse` — DIRECT provider calls, every minute, BOTH apps

Routes `/api/cronjob/endorse` (crontab lines 4, 16, and again hourly at 57).

```sql
SELECT * FROM endorse
 WHERE status='Aktif' AND status_campaign='Aktif'
   AND (sync_at < TODAY OR sync_at IS NULL) AND link_upload != ''
 LIMIT 10
```

then calls `get_social_media()` per row. **No driver gate** — `ENDORSE_REFRESH_DRIVER` is never
consulted, so it runs whatever the worker is doing.

- Up to **10 posts/minute per app**, i.e. up to 600/day each, 1 200/day combined.
- The requests never touch `endorse_refresh_rate_tokens`, so the reservation store **cannot see
  or limit them**, and they are invisible in every dashboard built on the ledger.

**Currently dormant on forbes, and that is misleading.** Measured now:

```
forbes kandidat : 0
sec    kandidat : 363
```

forbes reads zero only because the worker runs continuously and `Endorse_sync::apply`
(`Endorse_sync.php:311`) keeps `sync_at` fresh. **Under the target schedule this leak switches
itself on**: outside the 00:05–05:00 window `sync_at` goes stale, and this cron starts pulling
10 posts/minute all day, against the shared key, unmetered.

### 2. `cronjob_endorse_by_campaign` — DIRECT provider calls, every 15 min, forbes

Route `/api/cronjob/endorse-sync-campaign` (line 29). Selects campaigns with
`refresh_requested_at IS NOT NULL`, then `LIMIT 10` endorses with `sync_at < today`, and calls
`get_social_media()` directly. **No driver gate.**

Bounded by user-requested refreshes, so volume is lower — but the mechanism is identical:
unmetered provider calls at an arbitrary time.

### 3. `cronjob_endorse_final_reconcile` — enqueues, every 5 min, forbes

Route `/api/cronjob/endorse-final-reconcile` (line 48). The function is 16 lines
(`Api_v2.php:8142-8157`) and calls `enqueuePendingFinals(0)`. **No driver gate.**

It does not call the provider itself, but it *feeds the queue* every five minutes, so work
appears outside the window and the worker — if running — picks it up.

> Correction: an earlier read of this function reported a driver gate. That was wrong; the
> 120-line window I inspected ran past the end of the function into `cronjob_endorse_refresh`,
> which does have one.

### 4. sec-forbes drains three times per minute

Crontab lines 22, 23, 24 all call `/api/cronjob/endorse-refresh` on bkasystem with offsets
18 s / 38 s / 58 s. These *do* respect the driver gate, so they stand down once sec-forbes sets
`ENDORSE_REFRESH_DRIVER=php_worker` — but sec's driver is currently unset, so all three run.

### 5. sec-forbes enqueue-all is at the wrong time

Line 66: `30 11 * * *` → `/api/cronjob/endorse-refresh-enqueue-all`. Does not match the agreed
05:00 / 16:00 windows.

### 6. forbes has no enqueue-all cron at all

There is **no** `endorse-refresh-enqueue-all` entry for acnenosystem.com. forbes' queue is fed
only by `endorse-final-reconcile` (§3) and manual UI actions. The 00:05 all-sync the schedule
assumes **does not currently exist and must be created.**

---

## Summary

| # | Cron | App | Freq | Bypasses queue? | Bypasses rate limiter? | Driver gate |
|---|---|---|---|---|---|---|
| 1 | `endorse` | both | 1 min | **yes** | **yes** | none |
| 2 | `endorse-sync-campaign` | forbes | 15 min | **yes** | **yes** | none |
| 3 | `endorse-final-reconcile` | forbes | 5 min | no (enqueues) | n/a | none |
| 4 | `endorse-refresh` ×3 | sec | 1 min | no | no | yes ✓ |
| 5 | `endorse-refresh-enqueue-all` | sec | 11:30 | no (enqueues) | n/a | n/a |
| 6 | *(missing)* enqueue-all | forbes | — | — | — | — |

Paths 1 and 2 are the serious ones: they spend the shared RapidAPI quota with no reservation
token, so neither tenant's limiter can account for them and no dashboard shows them.

## Required work

1. **Add a driver gate to `cronjob_endorse` and `cronjob_endorse_by_campaign`.** The mechanism
   already exists — `EndorseRefreshQueueService::driverOwnsDraining($driver)`, as used by
   `cronjob_endorse_refresh`. One `if` each, and the manual `force=1` escape hatch stays.
   This is the single change that stops the unmetered leakage on both apps.
2. **Gate or reschedule `endorse-final-reconcile`** so snapshot rows do not enter the queue
   outside the windows. If final snapshots are genuinely time-critical, keep it but accept that
   it is a deliberate exception and size the budget for it.
3. **Set `ENDORSE_REFRESH_DRIVER=php_worker` on sec-forbes** so its three-per-minute drain
   stands down. Without this, adding a worker there means cron and worker both drain.
4. **Move sec's enqueue-all from 11:30 to 05:00, and add a second at 16:00.**
5. **Create forbes' enqueue-all at 00:05.** It does not exist.
6. **Then** the cross-tenant run lock (see `DUAL-TENANT-SCHEDULE.md` RISK 2), so that a window
   overrun makes the second tenant wait rather than collide.

Items 1 and 3 are the ones that make the schedule meaningful. Without them, the windows are
decoration: work still flows outside them, unmetered, on a shared key.
