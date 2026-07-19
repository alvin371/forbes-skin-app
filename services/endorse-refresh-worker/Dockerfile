# rust:1-bookworm tracks latest stable — some deps (icu, chrono) need a recent MSRV.
# ponytail: pin to a specific rust:1.XX-bookworm if you need reproducible builds.
FROM rust:1-bookworm AS builder

WORKDIR /app

# Cache deps: build against a stub main, then swap in real sources.
COPY Cargo.toml Cargo.lock ./
RUN mkdir -p src && echo "fn main() {}" > src/main.rs
RUN cargo build --release --locked

# COPY restores build-context mtimes, which are older than the stub written above.
# Cargo's fingerprint is mtime-based, so it decides the crate is fresh and ships the
# stub binary — a container that exits 0 immediately. Touch the sources and drop the
# stub's artifacts so the real crate always recompiles. Dep caching is unaffected.
COPY src ./src
RUN touch src/*.rs \
 && rm -f target/release/endorse-refresh-worker \
           target/release/deps/endorse_refresh_worker* \
 && rm -rf target/release/.fingerprint/endorse-refresh-worker-* \
 && cargo build --release --locked

# Guard the above: a stub build is ~0.5MB, the real worker is several MB. Fail the
# build here rather than shipping a no-op binary again.
RUN test "$(stat -c%s target/release/endorse-refresh-worker)" -gt 2000000

FROM debian:bookworm-slim

RUN apt-get update && apt-get install -y ca-certificates curl && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=builder /app/target/release/endorse-refresh-worker /usr/local/bin/endorse-refresh-worker

ENV WORKER_PORT=8082
# main.rs uses EnvFilter::from_default_env(); without RUST_LOG the worker is silent,
# so a crash-looping container leaves empty `docker logs`. Override per environment.
ENV RUST_LOG=info

EXPOSE 8082

CMD ["endorse-refresh-worker"]
