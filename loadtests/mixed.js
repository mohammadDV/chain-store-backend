import browse from './browse.js';
import search from './search.js';
import checkout from './checkout.js';
import { defaultThresholds, stages } from './lib/helpers.js';

export const options = {
  stages: stages(),
  thresholds: defaultThresholds,
  tags: { scenario: 'mixed' },
};

export default function mixed() {
  const roll = Math.random();
  if (roll < 0.7) {
    browse();
  } else if (roll < 0.9) {
    search();
  } else {
    checkout();
  }
}
