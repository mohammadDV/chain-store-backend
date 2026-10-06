# API Capacity Scorecard — Boofstore Laravel Backend

**Date:** 2026-10-06  
**Stack assumptions:** 1× PHP-FPM (`pm.max_children=20`), 1× MariaDB, Redis cache/queue, Horizon ≤10 workers, Next.js ISR storefront.

## Concurrent-user verdict

| Target | Fit | Notes |
|--------|-----|-------|
| ~200–500 concurrent browsers | Yes | Redis + Next ISR absorb light catalog reads |
| ~1,000 concurrent | Achievable after Phase 1–2 | Search/sitemap/session fixes + modest FPM/DB headroom |
| 1,000,000 concurrent | No | Needs CDN, multi-node, search engine, replicas — not this monolith alone |

**Rough single-node math:** capacity ≈ `FPM_workers / avg_latency`. With 20 workers and 100–300ms latency → ~60–200 RPS → roughly **50–200 truly concurrent API users** before queues form. Next.js ISR raises *perceived* browser concurrency.

## Bottleneck scorecard

| Area | Impact | Status after this work |
|------|--------|------------------------|
| Product search LIKE + no cache | Critical | Cached 5m; title-only LIKE; throttled |
| Sitemap / redirects full dump | Critical | Redis-cached; throttled |
| Unbounded category/brand trees | High | Redis-cached |
| Soft stock = pending SUM under lock | High | Uses `stocks.reserved` |
| Wallet transfer without row lock | High | `lockForUpdate` on transfer/withdraw/top-up |
| MySQL sessions | Medium | Default → Redis in `.env.example` |
| Home featured/banners uncached POSTs | Medium | Backend cache + frontend ISR on POSTs |
| Horizon scraper starving short jobs | Medium | Split supervisors |
| Public catalog unthrottled | Low–Med | Search/sitemap throttles added |

## Measurement method

k6 scenarios in this folder (`browse`, `search`, `checkout`, `mixed`). Ramp 50 → 200 → 500 → 1000 VUs; stop when error rate >1% or p95 >1s.

Fallback without k6: `php smoke.php http://localhost/api 15 25`.

See [RESULTS.md](./RESULTS.md) for baseline and post-fix runs.

## Measured outcome (local Docker, 2026-10-06)

| | Baseline 15 VU | After fixes 15 VU | After fixes 40 VU |
|--|----------------|-------------------|-------------------|
| RPS | 68 | **93** | **89** |
| p95 | 385 ms | **222 ms** | **573 ms** |
| Hard errors | 0% | **0%** | **0%** |

**Verdict unchanged at architecture scale:** hundreds of concurrent users today; ~1k with infra headroom; not 1M.
