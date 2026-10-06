import http from 'k6/http';
import { check, sleep } from 'k6';
import { BASE_URL, defaultThresholds, jsonHeaders, stages } from './lib/helpers.js';

export const options = {
  stages: stages(),
  thresholds: defaultThresholds,
  tags: { scenario: 'checkout' },
};

/**
 * Staging-only. Requires LOADTEST_EMAIL + LOADTEST_PASSWORD and a known product/size.
 * Skips gracefully when credentials are missing.
 */
export default function checkout() {
  const email = __ENV.LOADTEST_EMAIL;
  const password = __ENV.LOADTEST_PASSWORD;
  const productId = Number(__ENV.LOADTEST_PRODUCT_ID || 0);
  const sizeId = Number(__ENV.LOADTEST_SIZE_ID || 0);

  if (!email || !password) {
    sleep(1);
    return;
  }

  const headers = jsonHeaders();
  const login = http.post(
    `${BASE_URL}/auth/login`,
    JSON.stringify({ email, password }),
    { headers, tags: { name: 'POST /auth/login' } }
  );
  check(login, { 'login status': (r) => r.status === 200 || r.status === 422 || r.status === 429 });

  let token = null;
  try {
    token = login.json()?.data?.token ?? login.json()?.token ?? null;
  } catch (_) {
    /* ignore */
  }

  if (!token || !productId || !sizeId) {
    sleep(1);
    return;
  }

  const authHeaders = jsonHeaders(token);
  const order = http.post(
    `${BASE_URL}/profile/orders`,
    JSON.stringify({
      products: [{ id: productId, count: 1, size_id: sizeId }],
    }),
    { headers: authHeaders, tags: { name: 'POST /profile/orders' } }
  );
  check(order, {
    'order accepted or stock fail': (r) =>
      r.status === 200 || r.status === 201 || r.status === 400 || r.status === 422,
  });

  sleep(1);
}
