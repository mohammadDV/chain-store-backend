export const BASE_URL = (__ENV.BASE_URL || 'http://localhost/api').replace(/\/$/, '');

export const defaultThresholds = {
  http_req_failed: ['rate<0.01'],
  http_req_duration: ['p(95)<1000'],
};

/** Standard capacity ramp: 50 → 200 → 500 → 1000 VUs */
export const capacityStages = [
  { duration: '30s', target: 50 },
  { duration: '45s', target: 200 },
  { duration: '45s', target: 500 },
  { duration: '45s', target: 1000 },
  { duration: '30s', target: 0 },
];

/** Short local smoke when LOADTEST_SMOKE=1 */
export const smokeStages = [
  { duration: '10s', target: 5 },
  { duration: '20s', target: 20 },
  { duration: '10s', target: 0 },
];

export function stages() {
  return __ENV.LOADTEST_SMOKE === '1' ? smokeStages : capacityStages;
}

export function jsonHeaders(token) {
  const headers = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  };
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }
  return headers;
}
