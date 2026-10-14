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

it('fits its border inside a stretched grid cell', async () => {
  document.body.innerHTML = `
    <div style="display: grid; grid-template-rows: 120px">
      <craft-card>Body</craft-card>
    </div>`;
  const card = document.querySelector<CraftCard>('craft-card')!;
  await card.updateComplete;

  const host = card.getBoundingClientRect();
  const inner = card
    .shadowRoot!.querySelector('.card')!
    .getBoundingClientRect();

  expect(inner.height).toBe(host.height);
  expect(inner.bottom).toBe(host.bottom);
});

it('divides a white card’s header and footer with the table border color', async () => {
  document.body.innerHTML = `
    <span class="probe" style="border: 1px solid var(--c-table-border-color)"></span>
    <craft-card data-color="white">
      <span slot="label">Header</span>
      Body
      <span slot="footer">Footer</span>
    </craft-card>`;
  const card = document.querySelector<CraftCard>('craft-card')!;
  await card.updateComplete;

  const expected = getComputedStyle(
    document.querySelector('.probe')!
  ).borderTopColor;
  const part = (selector: string) =>
    getComputedStyle(card.shadowRoot!.querySelector(selector)!);

  expect(part('.card__header').borderBottomColor).toBe(expected);
  expect(part('.card__footer').borderTopColor).toBe(expected);
  expect(part('.card').borderTopColor).not.toBe(expected);
});

it('styles a selected white card like a selected chip', async () => {
  document.body.innerHTML = `
    <craft-card data-color="white" active>
      <span slot="label">Header</span>
      Body
      <span slot="footer">Footer</span>
    </craft-card>
    <craft-chip data-color="white" selected>Chip</craft-chip>
    <span class="divider" style="border: 1px solid var(--c-color-accent-border-quiet)"></span>`;
  const card = document.querySelector<CraftCard>('craft-card')!;
  const chip = document.querySelector('craft-chip')!;
  await Promise.all([card.updateComplete, chip.updateComplete]);

  const part = (selector: string) =>
    getComputedStyle(card.shadowRoot!.querySelector(selector)!);
  const chipStyle = getComputedStyle(
    chip.shadowRoot!.querySelector('.cp-chip')!
  );
  const divider = getComputedStyle(
    document.querySelector('.divider')!
  ).borderTopColor;

  expect(part('.card').backgroundColor).toBe(chipStyle.backgroundColor);
  expect(part('.card').borderTopColor).toBe(chipStyle.borderTopColor);
  expect(part('.card__header').backgroundColor).toBe('rgba(0, 0, 0, 0)');
  expect(part('.card__header').borderBottomColor).toBe(divider);
  expect(part('.card__footer').borderTopColor).toBe(divider);
});
