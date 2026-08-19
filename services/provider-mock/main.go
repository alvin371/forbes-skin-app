// Controllable provider mock for endorse-refresh load tests.
//
// Serves BOTH provider legs the worker uses, so scenarios that cannot be forced through a
// real provider (a precise 429 rate, a p99 that exceeds the client timeout, malformed
// bodies) become deterministic and repeatable:
//
//	leg 1 "direct scrape"  GET https://tiktok-mock.local/@user/video/<id>
//	                       -> HTML carrying __UNIVERSAL_DATA_FOR_REHYDRATION__
//	leg 2 "rapidapi"       GET https://provider-mock.local/index/Tiktok/getVideoInfo?url=..&hd=0
//	                       -> {"code":0,"data":{...}} plus x-ratelimit-* / retry-after headers
//
// Two properties matter more than breadth of features:
//
//  1. DETERMINISM PER POST. Outcomes are seeded by crc32(contentID + profileSeed), never by
//     wall clock. The same post gets the same outcome every run, so the leg census can
//     compare run A (scrape only) against run B (rapidapi only) per queue_id, and a
//     regression is attributable rather than noise.
//
//  2. GROUND TRUTH. /_control/stats reports what the mock actually served. Every load run
//     asserts it against the ledger; a mismatch means the ledger has a hole and every
//     conclusion drawn from it is void.
//
// The `correlation` knob models the real world's most important structure: dead, private and
// removed videos fail BOTH legs. Without it the fallback's measured success rate is
// optimistic, and the requests-per-completion prediction comes out wrong.
package main

import (
	"encoding/json"
	"flag"
	"fmt"
	"hash/crc32"
	"log"
	"math"
	"math/rand"
	"net"
	"net/http"
	"os"
	"regexp"
	"sort"
	"strconv"
	"strings"
	"sync"
	"time"
)

// ---------------------------------------------------------------- profile

type LatencyProfile struct {
	P50Ms int `json:"p50_ms"`
	P95Ms int `json:"p95_ms"`
	P99Ms int `json:"p99_ms"`
}

// sample draws a lognormal latency fitted to p50/p95, clamped at p99 so a fat tail cannot
// stall a whole run for minutes. p50 is the median => mu = ln(p50); p95 is 1.645 sigma up.
func (l LatencyProfile) sample(r *rand.Rand) time.Duration {
	if l.P50Ms <= 0 {
		return 0
	}
	mu := math.Log(float64(l.P50Ms))
	sigma := 0.0
	if l.P95Ms > l.P50Ms {
		sigma = (math.Log(float64(l.P95Ms)) - mu) / 1.645
	}
	ms := math.Exp(mu + sigma*r.NormFloat64())
	if l.P99Ms > 0 && ms > float64(l.P99Ms) {
		ms = float64(l.P99Ms)
	}
	if ms < 0 {
		ms = 0
	}
	return time.Duration(ms) * time.Millisecond
}

// LegProfile controls one provider leg.
type LegProfile struct {
	SuccessRate float64            `json:"success_rate"`
	Latency     LatencyProfile     `json:"latency"`
	Failures    map[string]float64 `json:"failures"`
	RetryAfter  int                `json:"retry_after_sec"`
}

// pickFailure chooses a failure kind by weight. Deterministic for a given rng.
func (p LegProfile) pickFailure(r *rand.Rand) string {
	if len(p.Failures) == 0 {
		return "transient"
	}
	kinds := make([]string, 0, len(p.Failures))
	for k := range p.Failures {
		kinds = append(kinds, k)
	}
	sort.Strings(kinds) // map order is random in Go; sorting is what makes this reproducible

	total := 0.0
	for _, k := range kinds {
		total += p.Failures[k]
	}
	if total <= 0 {
		return kinds[0]
	}

	x := r.Float64() * total
	for _, k := range kinds {
		x -= p.Failures[k]
		if x <= 0 {
			return k
		}
	}
	return kinds[len(kinds)-1]
}

type Profile struct {
	Scrape   LegProfile `json:"scrape"`
	RapidAPI LegProfile `json:"rapidapi"`
	Seed     string     `json:"seed"`
	// Correlation is P(leg 2 also fails | leg 1 failed). 1.0 means every post that fails the
	// scrape is genuinely dead and the fallback cannot save it; 0.0 means the two legs fail
	// independently. Real corpora sit well above 0.
	Correlation float64 `json:"correlation"`
	// QuotaRemaining seeds the x-ratelimit-requests-remaining header so quota-exhaustion
	// handling can be exercised without touching a real account.
	QuotaRemaining int `json:"quota_remaining"`
}

func defaultProfile() Profile {
	return Profile{
		Seed:           "loadtest",
		Correlation:    0.7,
		QuotaRemaining: 2166666,
		Scrape: LegProfile{
			SuccessRate: 1.0,
			Latency:     LatencyProfile{P50Ms: 300, P95Ms: 1200, P99Ms: 4000},
			Failures: map[string]float64{
				"http_404": 0.2, "http_403": 0.1, "timeout": 0.2,
				"no_script_tag": 0.3, "zero_stats": 0.1, "truncated_html": 0.1,
			},
		},
		RapidAPI: LegProfile{
			SuccessRate: 1.0,
			Latency:     LatencyProfile{P50Ms: 250, P95Ms: 900, P99Ms: 5000},
			RetryAfter:  2,
			Failures: map[string]float64{
				"http_429": 0.3, "http_500": 0.2, "http_503": 0.1,
				"timeout": 0.2, "malformed_json": 0.1, "code_nonzero": 0.1,
			},
		},
	}
}

// ---------------------------------------------------------------- state

type legStats struct {
	Requests  int64            `json:"requests"`
	Successes int64            `json:"successes"`
	Failures  map[string]int64 `json:"failures"`
	LatencyMs []int            `json:"-"`
}

type Stats struct {
	Scrape   *legStats `json:"scrape"`
	RapidAPI *legStats `json:"rapidapi"`
	StartedAt time.Time `json:"started_at"`
}

type server struct {
	mu      sync.Mutex
	profile Profile
	stats   Stats
	// served records, per contentID, whether leg 1 already failed for it — the input to the
	// correlation model.
	scrapeFailed map[string]bool
}

func newServer() *server {
	return &server{
		profile:      defaultProfile(),
		stats:        newStats(),
		scrapeFailed: map[string]bool{},
	}
}

func newStats() Stats {
	return Stats{
		Scrape:    &legStats{Failures: map[string]int64{}},
		RapidAPI:  &legStats{Failures: map[string]int64{}},
		StartedAt: time.Now().UTC(),
	}
}

// rngFor makes every decision for a post reproducible across runs and across legs.
func (s *server) rngFor(contentID, leg string) *rand.Rand {
	seed := crc32.ChecksumIEEE([]byte(contentID + ":" + leg + ":" + s.profile.Seed))
	return rand.New(rand.NewSource(int64(seed)))
}

func (s *server) record(leg *legStats, ok bool, kind string, latency time.Duration) {
	s.mu.Lock()
	defer s.mu.Unlock()
	leg.Requests++
	if ok {
		leg.Successes++
	} else {
		leg.Failures[kind]++
	}
	leg.LatencyMs = append(leg.LatencyMs, int(latency.Milliseconds()))
}

var videoIDRe = regexp.MustCompile(`/(?:video|photo)/(\d+)`)

func contentIDFrom(raw string) string {
	if m := videoIDRe.FindStringSubmatch(raw); len(m) == 2 {
		return m[1]
	}
	// Fall back to the whole string so an unparseable URL is still deterministic rather
	// than silently collapsing every such post onto one outcome.
	return raw
}

// ---------------------------------------------------------------- leg 1: scrape

// universalDataHTML mirrors the exact tag Template::extractTiktokItemStructFromHtml matches:
//
//	/<script id="__UNIVERSAL_DATA_FOR_REHYDRATION__" type="application\/json">(.*?)<\/script>/s
//
// Attribute order and spacing are load-bearing. If this drifts, every scrape reads as a miss
// and the whole run silently measures the fallback path instead.
func universalDataHTML(contentID string, stats map[string]int64) string {
	item := map[string]any{
		"id":         contentID,
		"createTime": 1750000000,
		"desc":       "loadtest fixture",
		"stats":      stats,
		"video": map[string]any{
			"cover":       "https://tiktok-mock.local/cover/" + contentID + ".jpg",
			"originCover": "https://tiktok-mock.local/oc/" + contentID + ".jpg",
			"playAddr":    "https://tiktok-mock.local/play/" + contentID + ".mp4",
			"duration":    15,
		},
		"author": map[string]any{
			"id": "1", "uniqueId": "mockuser", "nickname": "Mock User",
			"avatarLarger": "https://tiktok-mock.local/avatar.jpg",
		},
	}
	payload, _ := json.Marshal(map[string]any{
		"__DEFAULT_SCOPE__": map[string]any{
			"webapp.video-detail": map[string]any{
				"itemInfo": map[string]any{"itemStruct": item},
			},
		},
	})

	return `<!DOCTYPE html><html><head><title>mock</title>` +
		`<script id="__UNIVERSAL_DATA_FOR_REHYDRATION__" type="application/json">` +
		string(payload) + `</script></head><body>mock</body></html>`
}

func statsFor(r *rand.Rand) map[string]int64 {
	return map[string]int64{
		"diggCount":    int64(r.Intn(50000) + 1),
		"shareCount":   int64(r.Intn(2000) + 1),
		"commentCount": int64(r.Intn(5000) + 1),
		"collectCount": int64(r.Intn(1000) + 1),
		"playCount":    int64(r.Intn(900000) + 1),
	}
}

func (s *server) handleScrape(w http.ResponseWriter, req *http.Request) {
	contentID := contentIDFrom(req.URL.Path)

	s.mu.Lock()
	prof := s.profile.Scrape
	s.mu.Unlock()

	r := s.rngFor(contentID, "scrape")
	ok := r.Float64() < prof.SuccessRate
	latency := prof.Latency.sample(r)

	if !ok {
		kind := prof.pickFailure(r)
		s.mu.Lock()
		s.scrapeFailed[contentID] = true
		s.mu.Unlock()

		switch kind {
		case "timeout":
			// Sleep past the client's 20s CURLOPT_TIMEOUT. This is the case that proves the
			// pipeline never head-of-line blocks: under the old batch code one such item
			// serialises its whole chunk.
			time.Sleep(35 * time.Second)
			s.record(s.stats.Scrape, false, kind, 35*time.Second)
			return
		case "http_404", "http_403":
			time.Sleep(latency)
			code := http.StatusNotFound
			if kind == "http_403" {
				code = http.StatusForbidden
			}
			w.WriteHeader(code)
			fmt.Fprint(w, "<html><body>not available</body></html>")
		case "no_script_tag":
			// HTTP 200 with no rehydration payload — how a private/removed post actually
			// presents. extractTiktokItemStructFromHtml returns [] and the item falls through.
			time.Sleep(latency)
			fmt.Fprint(w, "<!DOCTYPE html><html><body>Video currently unavailable</body></html>")
		case "zero_stats":
			// Tag present, stats all zero. Exercises the difference between
			// isValidTiktokScrapeItem (key exists => valid) and the strict
			// scrapeStatsAreUsable parity check (needs a value > 0).
			time.Sleep(latency)
			fmt.Fprint(w, universalDataHTML(contentID, map[string]int64{
				"diggCount": 0, "shareCount": 0, "commentCount": 0, "collectCount": 0, "playCount": 0,
			}))
		case "truncated_html":
			time.Sleep(latency)
			body := universalDataHTML(contentID, statsFor(r))
			fmt.Fprint(w, body[:len(body)/2])
		default:
			time.Sleep(latency)
			w.WriteHeader(http.StatusBadGateway)
			fmt.Fprint(w, "upstream error")
		}
		s.record(s.stats.Scrape, false, kind, latency)
		return
	}

	time.Sleep(latency)
	w.Header().Set("Content-Type", "text/html; charset=utf-8")
	fmt.Fprint(w, universalDataHTML(contentID, statsFor(r)))
	s.record(s.stats.Scrape, true, "", latency)
}

// ---------------------------------------------------------------- leg 2: rapidapi

func (s *server) handleRapidAPI(w http.ResponseWriter, req *http.Request) {
	target := req.URL.Query().Get("url")
	contentID := contentIDFrom(target)

	s.mu.Lock()
	prof := s.profile.RapidAPI
	correlation := s.profile.Correlation
	quota := s.profile.QuotaRemaining
	scrapeAlreadyFailed := s.scrapeFailed[contentID]
	s.mu.Unlock()

	r := s.rngFor(contentID, "rapidapi")

	// The correlation model. A post whose scrape failed is more likely genuinely dead, so
	// the fallback must not be allowed to rescue it at its unconditional success rate —
	// otherwise the measured requests-per-completion is optimistic and the capacity
	// prediction is wrong in the direction that matters.
	ok := r.Float64() < prof.SuccessRate
	if ok && scrapeAlreadyFailed && r.Float64() < correlation {
		ok = false
	}
	latency := prof.Latency.sample(r)

	setQuotaHeaders(w, quota)

	if !ok {
		kind := prof.pickFailure(r)
		switch kind {
		case "timeout":
			time.Sleep(25 * time.Second) // past the 12s RapidAPI CURLOPT_TIMEOUT
			s.record(s.stats.RapidAPI, false, kind, 25*time.Second)
			return
		case "http_429":
			time.Sleep(latency)
			if prof.RetryAfter > 0 {
				w.Header().Set("Retry-After", strconv.Itoa(prof.RetryAfter))
			}
			w.WriteHeader(http.StatusTooManyRequests)
			writeJSON(w, map[string]any{"code": -1, "msg": "rate limited"})
		case "http_500", "http_503":
			time.Sleep(latency)
			code := http.StatusInternalServerError
			if kind == "http_503" {
				code = http.StatusServiceUnavailable
			}
			w.WriteHeader(code)
			writeJSON(w, map[string]any{"code": -1, "msg": "upstream error"})
		case "malformed_json":
			time.Sleep(latency)
			w.Header().Set("Content-Type", "application/json")
			fmt.Fprint(w, `{"code":0,"data":{"id":`) // deliberately truncated
		case "code_nonzero":
			// HTTP 200 with an application-level error — the shape that makes
			// isValidRapidApiTiktokDetailResponse return false.
			time.Sleep(latency)
			writeJSON(w, map[string]any{"code": -1, "msg": "video not found"})
		default:
			time.Sleep(latency)
			w.WriteHeader(http.StatusBadGateway)
			writeJSON(w, map[string]any{"code": -1, "msg": "transient"})
		}
		s.record(s.stats.RapidAPI, false, kind, latency)
		return
	}

	time.Sleep(latency)
	writeJSON(w, map[string]any{
		"code": 0, "msg": "success", "processed_time": 0.03,
		"data": map[string]any{
			"id": contentID, "region": "ID",
			"digg_count": r.Intn(50000) + 1, "share_count": r.Intn(2000) + 1,
			"comment_count": r.Intn(5000) + 1, "collect_count": r.Intn(1000) + 1,
			"play_count": r.Intn(900000) + 1, "create_time": 1750000000, "duration": 15,
			"cover":  "https://provider-mock.local/c/" + contentID + ".jpg",
			"origin_cover": "https://provider-mock.local/oc/" + contentID + ".jpg",
			"play":   "https://provider-mock.local/p/" + contentID + ".mp4",
			"wmplay": "https://provider-mock.local/w/" + contentID + ".mp4",
			"images": []any{},
			"author": map[string]any{
				"id": "1", "unique_id": "mockuser", "nickname": "Mock User",
				"avatar": "https://provider-mock.local/a.jpg",
			},
		},
	})
	s.record(s.stats.RapidAPI, true, "", latency)
}

// setQuotaHeaders emits what Template::captureRapidApiHeaderMeta harvests into error_meta,
// so quota tracking and Retry-After handling can be exercised end to end.
func setQuotaHeaders(w http.ResponseWriter, remaining int) {
	h := w.Header()
	h.Set("x-rapidapi-region", "AWS - ap-southeast-1")
	h.Set("x-rapidapi-request-id", strconv.FormatInt(time.Now().UnixNano(), 36))
	h.Set("x-ratelimit-requests-limit", "2166666")
	h.Set("x-ratelimit-requests-remaining", strconv.Itoa(remaining))
	h.Set("x-ratelimit-requests-reset", "2592000")
	h.Set("cf-ray", "mock-"+strconv.FormatInt(time.Now().UnixNano()%1e9, 16))
}

func writeJSON(w http.ResponseWriter, v any) {
	w.Header().Set("Content-Type", "application/json")
	_ = json.NewEncoder(w).Encode(v)
}

// ---------------------------------------------------------------- control plane

func percentile(sorted []int, p float64) int {
	if len(sorted) == 0 {
		return 0
	}
	i := int(math.Ceil(p*float64(len(sorted)))) - 1
	if i < 0 {
		i = 0
	}
	if i >= len(sorted) {
		i = len(sorted) - 1
	}
	return sorted[i]
}

func (s *server) handleControl(w http.ResponseWriter, req *http.Request) {
	switch {
	case strings.HasSuffix(req.URL.Path, "/profile") && req.Method == http.MethodPost:
		var p Profile
		if err := json.NewDecoder(req.Body).Decode(&p); err != nil {
			http.Error(w, "bad profile: "+err.Error(), http.StatusBadRequest)
			return
		}
		if p.Seed == "" {
			p.Seed = "loadtest"
		}
		if p.QuotaRemaining == 0 {
			p.QuotaRemaining = 2166666
		}
		s.mu.Lock()
		s.profile = p
		// A new profile means a new experiment: clear the correlation memory so leg-1
		// outcomes from the previous scenario cannot bias this one.
		s.scrapeFailed = map[string]bool{}
		s.mu.Unlock()
		writeJSON(w, map[string]any{"ok": true})

	case strings.HasSuffix(req.URL.Path, "/stats"):
		s.mu.Lock()
		out := map[string]any{
			"started_at": s.stats.StartedAt,
			"seed":       s.profile.Seed,
			"scrape":     legSummary(s.stats.Scrape),
			"rapidapi":   legSummary(s.stats.RapidAPI),
		}
		s.mu.Unlock()
		writeJSON(w, out)

	case strings.HasSuffix(req.URL.Path, "/reset") && req.Method == http.MethodPost:
		s.mu.Lock()
		s.stats = newStats()
		s.scrapeFailed = map[string]bool{}
		s.mu.Unlock()
		writeJSON(w, map[string]any{"ok": true})

	default:
		http.NotFound(w, req)
	}
}

func legSummary(l *legStats) map[string]any {
	lat := append([]int(nil), l.LatencyMs...)
	sort.Ints(lat)
	return map[string]any{
		"requests": l.Requests, "successes": l.Successes, "failures": l.Failures,
		"p50_ms": percentile(lat, 0.50), "p95_ms": percentile(lat, 0.95), "p99_ms": percentile(lat, 0.99),
	}
}

// ---------------------------------------------------------------- wiring

func (s *server) routeProvider(w http.ResponseWriter, req *http.Request) {
	if strings.HasPrefix(req.URL.Path, "/_control") {
		s.handleControl(w, req)
		return
	}
	if strings.HasPrefix(req.URL.Path, "/index/Tiktok/") {
		s.handleRapidAPI(w, req)
		return
	}
	s.handleScrape(w, req)
}

func envOr(key, fallback string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return fallback
}

func main() {
	healthcheck := flag.Bool("healthcheck", false, "probe the local control port and exit")
	flag.Parse()

	httpPort := envOr("MOCK_HTTP_PORT", "8080")

	if *healthcheck {
		c, err := net.DialTimeout("tcp", "127.0.0.1:"+httpPort, 2*time.Second)
		if err != nil {
			os.Exit(1)
		}
		_ = c.Close()
		return
	}

	s := newServer()
	mux := http.NewServeMux()
	mux.HandleFunc("/", s.routeProvider)

	// Generous timeouts: the point of this service is to serve deliberately slow requests
	// (a 35s scrape timeout injection) without the server itself cutting them off.
	mk := func(addr string) *http.Server {
		return &http.Server{
			Addr: addr, Handler: mux,
			ReadHeaderTimeout: 10 * time.Second,
			WriteTimeout:      90 * time.Second,
			IdleTimeout:       120 * time.Second,
		}
	}

	go func() {
		cert, key := os.Getenv("MOCK_TLS_CERT"), os.Getenv("MOCK_TLS_KEY")
		if cert == "" || key == "" {
			log.Println("TLS disabled (MOCK_TLS_CERT/MOCK_TLS_KEY unset)")
			return
		}
		addr := ":" + envOr("MOCK_HTTPS_PORT", "443")
		log.Printf("provider-mock https listening on %s", addr)
		if err := mk(addr).ListenAndServeTLS(cert, key); err != nil {
			log.Fatalf("https: %v", err)
		}
	}()

	log.Printf("provider-mock http listening on :%s", httpPort)
	if err := mk(":" + httpPort).ListenAndServe(); err != nil {
		log.Fatalf("http: %v", err)
	}
}
