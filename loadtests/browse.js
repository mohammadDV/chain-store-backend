import http from 'k6/http';
import { check, sleep } from 'k6';
import { BASE_URL, defaultThresholds, jsonHeaders, stages } from './lib/helpers.js';

export const options = {
  stages: stages(),
  thresholds: defaultThresholds,
  tags: { scenario: 'browse' },
};

export default function browse() {
  const headers = jsonHeaders();

  const brands = http.get(`${BASE_URL}/brands`, { headers, tags: { name: 'GET /brands' } });
  check(brands, { 'brands ok': (r) => r.status === 200 });

  const categories = http.get(`${BASE_URL}/categories/all`, {
    headers,
    tags: { name: 'GET /categories/all' },
  });
  check(categories, { 'categories ok': (r) => r.status === 200 });

  const featured = http.post(
    `${BASE_URL}/products/featured`,
    JSON.stringify({ column: 'order' }),
    { headers, tags: { name: 'POST /products/featured' } }
  );
  check(featured, { 'featured ok': (r) => r.status === 200 });

  let productPath = `${BASE_URL}/products/1`;
  try {
    const body = featured.json();
    const first = body?.data?.[0] ?? body?.[0];
    if (first?.id) {
      productPath = `${BASE_URL}/products/${first.id}`;
    } else if (first?.slug) {
      productPath = `${BASE_URL}/products/${encodeURIComponent(first.slug)}`;
    }
  } catch (_) {
    /* keep fallback */
  }

  const product = http.get(productPath, { headers, tags: { name: 'GET /products/{id}' } });
  check(product, { 'product ok': (r) => r.status === 200 || r.status === 404 });

  sleep(1);
}
