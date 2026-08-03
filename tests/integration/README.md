# Endorse-refresh integration tests (opt-in, real MySQL)

These exercise the **actual** claim SQL and the **real** request-start reservation across
concurrent OS processes — proving cross-worker atomicity that pure unit tests cannot.

They are excluded from the default `phpunit` run (which is scoped to `tests/Unit`) and
**skip** (never fail) unless `FORBES_TEST_DB` is set.

## Run

```sh
# disposable DB
docker run -d --name forbes_test_mysql -e MYSQL_ROOT_PASSWORD=root \
  -e MYSQL_DATABASE=forbes_test -p 33061:3306 mysql:8.0

export FORBES_TEST_DB="host=127.0.0.1;port=33061;user=root;pass=root;db=forbes_test"
php vendor/bin/phpunit tests/integration/EndorseRefreshMysqlConcurrencyTest.php --testdox

docker rm -f forbes_test_mysql
```

## What is proven

- `testThreeWorkersNeverDoubleClaimSameRow` — 3 concurrent processes run the verbatim
  `claimBatch` UPDATE against 90 pending rows; no row is claimed twice, no over-claim, no loss.
- `testThreeWorkersCannotExceedRollingRequestLimit` — 3 processes each attempt 100
  reservations against a shared rolling limit of 50 via `tryReserveToken`; the window never
  exceeds the limit.
- `testDeferredUnstartedClaimReleasesWithoutConsumingToken` — a claimed-but-unstarted row
  returns to pending and consumes no provider token.
