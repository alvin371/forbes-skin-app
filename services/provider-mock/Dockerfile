# Static binary, no dependencies beyond the standard library — so the mock is never the
# thing that fails to build in the middle of a load test.
FROM golang:1.22-alpine AS build
WORKDIR /src
COPY go.mod ./
COPY main.go ./
RUN CGO_ENABLED=0 go build -trimpath -ldflags="-s -w" -o /out/provider-mock .

FROM alpine:3.20
RUN adduser -D -u 10001 mock
COPY --from=build /out/provider-mock /usr/local/bin/provider-mock
USER mock
EXPOSE 8080 443
ENTRYPOINT ["/usr/local/bin/provider-mock"]
