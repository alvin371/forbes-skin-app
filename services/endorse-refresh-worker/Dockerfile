# rust:1-bookworm tracks latest stable — some deps (icu, chrono) need a recent MSRV.
# ponytail: pin to a specific rust:1.XX-bookworm if you need reproducible builds.
FROM rust:1-bookworm AS builder

WORKDIR /app

# Cache deps: build against a stub main, then swap in real sources.
COPY Cargo.toml ./
RUN mkdir -p src && echo "fn main() {}" > src/main.rs
RUN cargo build --release || true

COPY src ./src
RUN cargo build --release

FROM debian:bookworm-slim

RUN apt-get update && apt-get install -y ca-certificates curl && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=builder /app/target/release/endorse-refresh-worker /usr/local/bin/endorse-refresh-worker

ENV WORKER_PORT=8082

EXPOSE 8082

CMD ["endorse-refresh-worker"]
