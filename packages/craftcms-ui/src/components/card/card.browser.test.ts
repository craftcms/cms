import {beforeEach, expect, it} from 'vite-plus/test';
import '../../styles/cp.css';
import './card.js';
import '../chip/chip.js';
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

it('gives a white card a lighter shadow than other cards', async () => {
  document.body.innerHTML = `
    <span class="xs" style="box-shadow: var(--c-shadow-xs)"></span>
    <span class="sm" style="box-shadow: var(--c-shadow-sm)"></span>
    <craft-card data-color="white">White</craft-card>
    <craft-card>Plain</craft-card>`;
  const [white, plain] = Array.from(
    document.querySelectorAll<CraftCard>('craft-card')
  );
  await Promise.all([white!.updateComplete, plain!.updateComplete]);

  const shadow = (card: CraftCard) =>
    getComputedStyle(card.shadowRoot!.querySelector('.card')!).boxShadow;

  expect(shadow(white!)).toBe(
    getComputedStyle(document.querySelector('.xs')!).boxShadow
  );
  expect(shadow(plain!)).toBe(
    getComputedStyle(document.querySelector('.sm')!).boxShadow
  );
});

it('gives a white card the same border color and shadow as a white chip', async () => {
  document.body.innerHTML = `
    <craft-card data-color="white">Card</craft-card>
    <craft-chip data-color="white">Chip</craft-chip>`;
  const card = document.querySelector<CraftCard>('craft-card')!;
  const chip = document.querySelector('craft-chip')!;
  await Promise.all([card.updateComplete, chip.updateComplete]);

  const cardStyle = getComputedStyle(card.shadowRoot!.querySelector('.card')!);
  const chipStyle = getComputedStyle(
    chip.shadowRoot!.querySelector('.cp-chip')!
  );

  expect(cardStyle.borderTopColor).toBe(chipStyle.borderTopColor);
  expect(cardStyle.boxShadow).toBe(chipStyle.boxShadow);
});
