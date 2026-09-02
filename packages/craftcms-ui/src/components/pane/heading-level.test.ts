import {expect, test} from 'vitest';
import {html, render} from 'lit';

import './pane.js';
import type CraftPane from './pane.js';

async function mount(template: unknown): Promise<CraftPane> {
  const host = document.createElement('div');
  document.body.append(host);
  render(template as never, host);
  const pane = host.firstElementChild as CraftPane;
  await pane.updateComplete;
  return pane;
}

function heading(pane: CraftPane): string | undefined {
  return pane.shadowRoot
    ?.querySelector('.cp-pane__title')
    ?.tagName.toLowerCase();
}

test('defaults to h2, below the page its own h1', async () => {
  const pane = await mount(html`<craft-pane label="Settings"></craft-pane>`);
  expect(heading(pane)).toBe('h2');
});

test('honours an explicit level', async () => {
  const pane = await mount(
    html`<craft-pane label="Settings" heading-level="3"></craft-pane>`
  );
  expect(heading(pane)).toBe('h3');
});

test('a pane that is the page heading can ask for h1', async () => {
  const pane = await mount(
    html`<craft-pane label="Settings" heading-level="1"></craft-pane>`
  );
  expect(heading(pane)).toBe('h1');
});

test('clamps a level outside the six real headings', async () => {
  const pane = await mount(
    html`<craft-pane label="Settings" heading-level="9"></craft-pane>`
  );
  expect(heading(pane)).toBe('h6');
});

test('renders the label as the heading text', async () => {
  const pane = await mount(html`<craft-pane label="Settings"></craft-pane>`);
  expect(
    pane.shadowRoot?.querySelector('.cp-pane__title')?.textContent?.trim()
  ).toBe('Settings');
});
