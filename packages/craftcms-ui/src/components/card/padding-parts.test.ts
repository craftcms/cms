import {expect, test} from 'vitest';
import {html, render} from 'lit';

import './card.js';
import type CraftCard from './card.js';

async function mount(template: unknown): Promise<CraftCard> {
  const host = document.createElement('div');
  document.body.append(host);
  render(template as never, host);
  const card = host.firstElementChild as CraftCard;
  await card.updateComplete;
  return card;
}

function parts(card: CraftCard): string[] {
  return [...(card.shadowRoot?.querySelectorAll('[part]') ?? [])].map((el) =>
    el.getAttribute('part')!
  );
}

test('exposes the same regions craft-pane does', async () => {
  const card = await mount(
    html`<craft-card label="Entry">
      <p>Body</p>
      <div slot="actions">Actions</div>
      <div slot="footer">Footer</div>
    </craft-card>`
  );

  expect(parts(card)).toEqual(
    expect.arrayContaining([
      'base',
      'header',
      'label',
      'actions',
      'body',
      'footer',
    ])
  );
});

test('writes nothing when no padding is set, so the per-region defaults stand', async () => {
  const card = await mount(html`<craft-card label="Entry"></craft-card>`);
  const base = card.shadowRoot!.querySelector<HTMLElement>('[part="base"]')!;

  expect(card.padding).toBeUndefined();
  expect(base.style.getPropertyValue('--c-card-padding-block')).toBe('');
  expect(base.style.getPropertyValue('--c-card-padding-inline')).toBe('');
});

test('an explicit padding overrides both axes', async () => {
  const card = await mount(
    html`<craft-card label="Entry" padding="lg"></craft-card>`
  );
  const base = card.shadowRoot!.querySelector<HTMLElement>('[part="base"]')!;

  expect(base.style.getPropertyValue('--c-card-padding-block')).toBe(
    'var(--c-spacing-lg)'
  );
  expect(base.style.getPropertyValue('--c-card-padding-inline')).toBe(
    'var(--c-spacing-lg)'
  );
});

test('ignores a value off the spacing scale', async () => {
  const card = await mount(
    html`<craft-card label="Entry" padding="3.5rem"></craft-card>`
  );
  const base = card.shadowRoot!.querySelector<HTMLElement>('[part="base"]')!;

  expect(base.style.getPropertyValue('--c-card-padding-block')).toBe('');
});
