import {beforeAll, expect, it} from 'vite-plus/test';
import {loadTokens} from '../../../test/contrast';
import './badge-indicator.js';
import type CraftBadgeIndicator from './badge-indicator.js';

beforeAll(loadTokens);

it.each([
  ['a count', 'badge-count="3"'],
  ['a dot', ''],
])('centers %s in a row that centers its items', async (_, attributes) => {
  document.body.innerHTML = `
    <div style="display: flex; align-items: center; block-size: 32px; font: 14px/1.5 sans-serif">
      <craft-badge-indicator ${attributes}></craft-badge-indicator>
    </div>`;
  const indicator = document.querySelector<CraftBadgeIndicator>(
    'craft-badge-indicator'
  )!;
  await indicator.updateComplete;

  const row = indicator.parentElement!.getBoundingClientRect();
  const badge = indicator
    .shadowRoot!.querySelector('.badge-indicator')!
    .getBoundingClientRect();

  expect(badge.top - row.top).toBeCloseTo(row.bottom - badge.bottom, 0);
});
