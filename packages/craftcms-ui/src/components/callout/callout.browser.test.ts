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

it.each([
  ['beside its body when they both fit', '36rem', false],
  ['below its body when they don’t both fit', '20rem', true],
])('puts its action %s', async (_case, width, below) => {
  document.body.innerHTML = `
    <div style="inline-size: ${width}">
      <craft-callout icon="info">
        Showing your unsaved changes.
        <div slot="action" style="display: flex; flex-wrap: wrap; gap: 0.5rem">
          <craft-button size="small">Compare changes</craft-button>
          <craft-button size="small">Discard changes</craft-button>
        </div>
      </craft-callout>
    </div>`;
  const callout = document.querySelector<CraftCallout>('craft-callout')!;
  await callout.updateComplete;
  const body = callout
    .shadowRoot!.querySelector('.callout__body')!
    .getBoundingClientRect();
  const action = callout
    .shadowRoot!.querySelector('.callout__action')!
    .getBoundingClientRect();

  expect(action.top >= body.bottom).toBe(below);
});

it('adds no space for an empty action, however narrow', async () => {
  document.body.innerHTML = `
    <div style="inline-size: 8rem">
      <craft-callout icon="info">Showing your unsaved changes.</craft-callout>
    </div>`;
  const callout = document.querySelector<CraftCallout>('craft-callout')!;
  await callout.updateComplete;
  const base = callout
    .shadowRoot!.querySelector('.callout')!
    .getBoundingClientRect();
  const body = callout
    .shadowRoot!.querySelector('.callout__body')!
    .getBoundingClientRect();
  const {paddingBlockEnd, borderBlockEndWidth} = getComputedStyle(
    callout.shadowRoot!.querySelector('.callout')!
  );

  expect(
    Math.round(
      base.bottom -
        body.bottom -
        parseFloat(paddingBlockEnd) -
        parseFloat(borderBlockEndWidth)
    )
  ).toBe(0);
});
