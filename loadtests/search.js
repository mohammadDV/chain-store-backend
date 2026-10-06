import http from 'k6/http';
import { check, sleep } from 'k6';
import { BASE_URL, defaultThresholds, jsonHeaders, stages } from './lib/helpers.js';

export const options = {
  stages: stages(),
  thresholds: defaultThresholds,
  tags: { scenario: 'search' },
};

const queries = ['nike', 'adidas', 'کفش', 'توپ', 'ball'];

export default function search() {
  const headers = jsonHeaders();
  const q = queries[Math.floor(Math.random() * queries.length)];

  const suggestions = http.post(
    `${BASE_URL}/products/search-suggestions`,
    JSON.stringify({ query: q }),
    { headers, tags: { name: 'POST /products/search-suggestions' } }
  );
  check(suggestions, { 'suggestions ok': (r) => r.status === 200 || r.status === 429 });

  const results = http.post(
    `${BASE_URL}/products/search`,
    JSON.stringify({ query: q, count: 25, page: 1 }),
    { headers, tags: { name: 'POST /products/search' } }
  );
  check(results, { 'search ok': (r) => r.status === 200 || r.status === 429 });

  sleep(0.5);
}
