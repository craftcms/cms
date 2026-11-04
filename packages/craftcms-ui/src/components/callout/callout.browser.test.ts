import {beforeEach, expect, it} from 'vite-plus/test';
import {page} from 'vite-plus/test/browser';
import './callout.js';
import '../button/button.js';
import type CraftCallout from './callout.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('shows its label as the title, with no native tooltip to repeat it', async () => {
  document.body.innerHTML = `
    <craft-callout label="Heads up">Your draft was withdrawn.</craft-callout>
  `;
  const callout = document.querySelector<CraftCallout>('craft-callout')!;
  await callout.updateComplete;

  await expect.element(page.getByText('Heads up')).toBeVisible();
  expect(callout.hasAttribute('title')).toBe(false);
  expect(callout.title).toBe('');
});

it('shows its title as bold text, not a heading', async () => {
  document.body.innerHTML = `
    <craft-callout label="Heads up">Your draft was withdrawn.</craft-callout>
  `;
  const callout = document.querySelector<CraftCallout>('craft-callout')!;
  await callout.updateComplete;

  await expect.element(page.getByText('Heads up')).toBeVisible();
  await expect.element(page.getByRole('heading')).not.toBeInTheDocument();
});

function firstLineCenter(node: Node): number {
  const range = document.createRange();
  range.selectNodeContents(node);
  const [first] = [...range.getClientRects()].sort((a, b) => a.top - b.top);
  return (first!.top + first!.bottom) / 2;
}

it.each([
  [
    'one line of text beside a taller action',
    `<craft-callout icon="info" style="inline-size: 30rem">
      Withdrawn from review.
      <craft-button slot="action" size="small">Resubmit</craft-button>
    </craft-callout>`,
    'description',
  ],
  [
    'text that wraps',
    `<craft-callout icon="info" style="inline-size: 12rem">
      This draft was withdrawn from review. Resubmit it when your edits are
      ready.
    </craft-callout>`,
    'description',
  ],
  [
    'a title',
    `<craft-callout icon="info" label="Heads up" style="inline-size: 12rem">
      This draft was withdrawn from review. Resubmit it when your edits are
      ready.
    </craft-callout>`,
    'title',
  ],
])(
  'centers its icon on the first line of %s',
  async (_case, markup, firstLine) => {
    document.body.innerHTML = markup;
    const callout = document.querySelector<CraftCallout>('craft-callout')!;
    await callout.updateComplete;

    const icon = callout
      .shadowRoot!.querySelector('craft-icon')!
      .getBoundingClientRect();
    const text =
      firstLine === 'title'
        ? callout.shadowRoot!.querySelector('.callout__title')!
        : callout.firstChild!;

    expect(
      Math.abs((icon.top + icon.bottom) / 2 - firstLineCenter(text))
    ).toBeLessThanOrEqual(0.5);
  }
);
