import {beforeEach, expect, it} from 'vite-plus/test';
import '../../styles/cp.css';
import './card.js';
import type CraftCard from './card.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('gives a white card a body as white as its header', async () => {
  document.body.innerHTML = `
    <div style="background: var(--c-surface-sunken)">
      <craft-card data-color="white">
        <span slot="label">Header</span>
        Body
      </craft-card>
    </div>`;
  const card = document.querySelector<CraftCard>('craft-card')!;
  await card.updateComplete;

  const root = card.shadowRoot!.querySelector<HTMLElement>('.card')!;
  const header = card.shadowRoot!.querySelector<HTMLElement>('.card__header')!;
  const opaque = (color: string) =>
    !/rgba\(.*, 0\)|\/ 0(\.\d+)?\)|transparent/.test(color);

  // Whichever element carries the fill, the body and header read the same.
  const body = getComputedStyle(root).backgroundColor;
  const headerFill = getComputedStyle(header).backgroundColor;
  expect(opaque(body)).toBe(true);
  expect(opaque(headerFill) ? headerFill : body).toBe(body);
});
