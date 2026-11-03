import {beforeEach, expect, it} from 'vite-plus/test';
import type CraftSlidePicker from './slide-picker.js';
import './slide-picker.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function render(attributes = '', value = 100): Promise<CraftSlidePicker> {
  await import('../../styles/cp.css');
  document.body.innerHTML = `<craft-slide-picker min="25" max="100" step="25" model-value="${value}" ${attributes}></craft-slide-picker>`;
  const picker =
    document.querySelector<CraftSlidePicker>('craft-slide-picker')!;
  await picker.updateComplete;
  await track(picker).updateComplete;
  return picker;
}

function track(picker: CraftSlidePicker) {
  return picker.querySelector('craft-slide-picker-track')!;
}

function segments(picker: CraftSlidePicker): HTMLElement[] {
  return [
    ...track(picker).shadowRoot!.querySelectorAll<HTMLElement>(
      '.slide-picker__segment'
    ),
  ];
}

function fills(picker: CraftSlidePicker): string[] {
  return segments(picker).map(
    (segment) => getComputedStyle(segment).backgroundColor
  );
}

it('fills only the segments up to the hovered one', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const picker = await render();
  const selected = fills(picker)[0];

  await userEvent.hover(segments(picker)[1]!);
  const [first, second, third, fourth] = fills(picker);

  expect(first).not.toBe('rgba(0, 0, 0, 0)');
  expect(second).toBe(first);
  expect(first).not.toBe(selected);
  expect(third).toBe('rgba(0, 0, 0, 0)');
  expect(fourth).toBe('rgba(0, 0, 0, 0)');
});

it('draws every segment at one height, with the selected ones filled darker', async () => {
  const picker = await render('', 50);
  const heights = segments(picker).map(
    (segment) => segment.getBoundingClientRect().height
  );
  const [first, second, third] = fills(picker);

  expect(new Set(heights).size).toBe(1);
  expect(second).toBe(first);
  expect(third).not.toBe(first);
  // The divider between selected segments is as light as unselected
  // segments' borders, not their dark outline.
  const firstStyle = getComputedStyle(segments(picker)[0]!);
  expect(firstStyle.borderInlineEndColor).not.toBe(firstStyle.borderTopColor);
  expect(firstStyle.borderInlineEndColor).toBe(
    getComputedStyle(segments(picker)[2]!).borderTopColor
  );
});

it('leaves a read-only picker’s fills alone while hovered', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const picker = await render('readonly');
  const before = fills(picker);

  await userEvent.hover(segments(picker)[1]!);

  expect(fills(picker)).toEqual(before);
});

it('previews the current value like any other', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const picker = await render('', 50);
  const selected = fills(picker)[0];

  await userEvent.hover(segments(picker)[1]!);
  const [first, second, third] = fills(picker);

  expect(first).not.toBe(selected);
  expect(second).toBe(first);
  expect(third).toBe('rgba(0, 0, 0, 0)');
  expect(getComputedStyle(segments(picker)[1]!).cursor).toBe('pointer');
});

it('is one Tab stop, on the slider', async () => {
  const {page, userEvent} = await import('vite-plus/test/browser');
  await render();

  await userEvent.tab();

  await expect.element(page.getByRole('slider')).toHaveFocus();
});

it('draws one focus ring, around the current segment', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const picker = await render('', 50);

  await userEvent.tab();

  const current = track(picker).shadowRoot!.querySelector('.is-last-active')!;
  expect(getComputedStyle(track(picker)).outlineStyle).toBe('none');
  expect(getComputedStyle(current).outlineStyle).not.toBe('none');
});

it('is named by its label, and focused when the label is clicked', async () => {
  const {page} = await import('vite-plus/test/browser');
  await render('label="Columns"');

  await page.getByText('Columns', {exact: true}).click();

  const slider = page.getByRole('slider');
  await expect.element(slider).toHaveAccessibleName('Columns');
  await expect.element(slider).toHaveFocus();
});

it('takes its name and description from a surrounding field', async () => {
  const {page} = await import('vite-plus/test/browser');
  await import('../field/field.js');
  await import('../../styles/cp.css');
  const label = 'Card width';
  const helpText = 'How many columns the card spans.';
  const error = 'Card width must be at least 2 columns.';
  document.body.innerHTML = `<craft-field label="${label}" help-text="${helpText}"><craft-slide-picker slot="input" min="1" max="4" step="1" model-value="1"></craft-slide-picker><ul slot="feedback" class="errors"><li>${error}</li></ul></craft-field>`;

  const slider = page.getByRole('slider');

  await expect.element(slider).toHaveAccessibleName(label);
  await expect
    .element(slider)
    .toHaveAccessibleDescription(`${helpText} ${error}`);
});

it('has no name of its own until one is given', async () => {
  const {page} = await import('vite-plus/test/browser');
  await render();

  await expect.element(page.getByRole('slider')).toHaveAccessibleName('');
});
