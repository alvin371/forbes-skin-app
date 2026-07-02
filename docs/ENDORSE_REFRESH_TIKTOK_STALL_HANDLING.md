# Endorse Refresh TikTok Stall Handling

## Problem

Some TikTok RapidAPI calls fail with:

- `http=0`
- `apicode=n/a`
- `apimsg=n/a`
- `cURL#28=Operation timed out after 12003 milliseconds with 0 bytes received`

These failures are not the same as DNS, connect, TLS, or auth failures. In the observed cases, DNS resolution, TCP connect, and TLS handshake already finished, but the upstream never returned the first byte before the 12 second timeout. The same URL often succeeds on the next try or with a longer timeout.

## What Changed

### 1. Transport failures are classified more precisely

RapidAPI transport failures are now split into:

- `infra_dns`
- `infra_connect`
- `infra_tls`
- `infra_stall`
- `infra`

`infra_stall` is used when the request reaches the upstream but no response body starts before timeout.

### 2. Batch fetch retries only recoverable stalls

The TikTok batch fetcher now does one inline retry only for `infra_stall` failures when:

- retry budget is still available
- the row is not already in rescue lane

This avoids retrying hard failures like DNS, connect, TLS, or config errors.

### 3. Brownout mode reduces pressure during provider stalls

If a batch chunk shows a stall-heavy failure pattern:

- at least 3 TikTok results are `infra_stall`
- and at least 50% of completed TikTok results in the chunk are `infra_stall`

the worker switches into brownout mode for the rest of the run:

- effective concurrency is reduced to `2`
- inline retries are disabled

This prevents a provider-side stall from multiplying across a wide parallel fan-out.

### 4. Rescue lane for the next queue attempt

If the previous queue attempt failed with `infra_stall`, the next queue run sends that item through a rescue lane:

- concurrency `1`
- timeout `30` seconds
- `hd=1` for TikTok photo URLs

This isolates slow-recovery retries from the normal 12 second high-parallel path.

### 5. Failure metadata is richer

RapidAPI transport failures now keep additional diagnostics when available:

- `x-rapidapi-request-id`
- `x-rapidapi-region`
- `x-ratelimit-request-remaining`
- `cf-ray`
- timing breakdown for name lookup, connect, TLS, and first byte

These values are included in the normalized error metadata and selected values are appended to the failure message.

## Queue Policy

The endorse refresh queue now treats these classes differently:

- `infra_dns`, `infra_connect`, `infra_tls`, `infra`, `config`, `permanent`, `empty`: fail fast
- `infra_stall`, `transient`: retry through normal queue policy

This keeps recoverable upstream stalls retryable without turning hard infrastructure failures into noisy repeated retries.

## Expected Outcome

This change is intended to reduce repeated false-final failures for TikTok posts that only need:

- one immediate retry
- lower concurrency during provider brownout
- or one slower isolated retry on the next queue attempt

It does not eliminate all provider timeouts. It makes the retry path more selective and more consistent with the failure mode that was observed in production.
