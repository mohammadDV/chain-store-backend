# Load tests (k6)

Measure API capacity for the Boofstore Laravel backend.

## Prerequisites

```bash
# macOS
brew install k6

# or see https://grafana.com/docs/k6/latest/set-up/install-k6/
```

Set the API base URL (include `/api` prefix if your gateway mounts it there):

```bash
export BASE_URL="${BASE_URL:-http://localhost/api}"
# Optional checkout scenario credentials (staging only):
export LOADTEST_EMAIL="loadtest@example.com"
export LOADTEST_PASSWORD="secret"
```

## Scenarios

| Script | What it does |
|--------|----------------|
| `browse.js` | Home-ish reads: brands, categories, featured, product show |
| `search.js` | Product search + suggestions |
| `checkout.js` | Login + create order (staging only; needs credentials) |
| `mixed.js` | 70% browse / 20% search / 10% checkout (checkout skipped if no creds) |

## Run

```bash
cd chain-store-backend/loadtests

# Quick smoke (low VU)
k6 run -e BASE_URL=http://localhost/api browse.js

# Full ramp to 1000 VUs (use staging)
k6 run -e BASE_URL=https://staging.example/api --vus 1 --stage 30s:50,60s:200,60s:500,60s:1000 mixed.js
```

Or use the bundled stages inside each script:

```bash
k6 run browse.js
k6 run search.js
k6 run mixed.js
```

## Interpreting results

Record for each run:

- `http_reqs` (RPS)
- `http_req_duration` p50 / p95 / p99
- `http_req_failed` rate
- First endpoint that breaks thresholds

Thresholds (default): error rate &lt; 1%, p95 &lt; 1s.

Write numbers into [RESULTS.md](./RESULTS.md).
