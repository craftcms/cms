import {beforeEach, expect, it} from 'vite-plus/test';
import type CraftSlidePicker from './slide-picker.js';
import './slide-picker.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function render(attributes = '', value = 100): Promise<CraftSlidePicker> {
  await import('../../styles/cp.css');
  document.body.innerHTML = `<craft-slide-picker min="25" max="100" step="25" value="${value}" ${attributes}></craft-slide-picker>`;
  const picker =
    document.querySelector<CraftSlidePicker>('craft-slide-picker')!;
  await picker.updateComplete;
  return picker;
}

function fills(picker: CraftSlidePicker): string[] {
  return [...picker.shadowRoot!.querySelectorAll('.slide-picker__segment')].map(
    (segment) => getComputedStyle(segment).backgroundColor
  );
}

it('fills only the segments up to the hovered one', async () => {
  const {userEvent} = await import('@vitest/browser/context');
  const picker = await render();
  const segments = picker.shadowRoot!.querySelectorAll<HTMLElement>(
    '.slide-picker__segment'
  );
  const selected = fills(picker)[0];

  await userEvent.hover(segments[1]!);
  const [first, second, third, fourth] = fills(picker);

  expect(first).not.toBe('rgba(0, 0, 0, 0)');
  expect(second).toBe(first);
  expect(first).not.toBe(selected);
  expect(third).toBe('rgba(0, 0, 0, 0)');
  expect(fourth).toBe('rgba(0, 0, 0, 0)');
});

it('draws every segment at one height, with the selected ones filled darker', async () => {
  const picker = await render('', 50);
  const segments = [
    ...picker.shadowRoot!.querySelectorAll<HTMLElement>(
      '.slide-picker__segment'
    ),
  ];
  const heights = segments.map(
    (segment) => segment.getBoundingClientRect().height
  );
  const [first, second, third] = fills(picker);

  expect(new Set(heights).size).toBe(1);
  expect(second).toBe(first);
  expect(third).not.toBe(first);
  // The divider between selected segments is as light as unselected
  // segments' borders, not their dark outline.
  const firstStyle = getComputedStyle(segments[0]!);
  expect(firstStyle.borderInlineEndColor).not.toBe(firstStyle.borderTopColor);
  expect(firstStyle.borderInlineEndColor).toBe(
    getComputedStyle(segments[2]!).borderTopColor
  );
});

it('leaves a read-only picker’s fills alone while hovered', async () => {
  const {userEvent} = await import('@vitest/browser/context');
  const picker = await render('readonly');
  const before = fills(picker);

  await userEvent.hover(
    picker.shadowRoot!.querySelectorAll<HTMLElement>(
      '.slide-picker__segment'
    )[1]!
  );

  expect(fills(picker)).toEqual(before);
});

it('previews the current value like any other', async () => {
  const {userEvent} = await import('@vitest/browser/context');
  const picker = await render('', 50);
  const segments = picker.shadowRoot!.querySelectorAll<HTMLElement>(
    '.slide-picker__segment'
  );
  const selected = fills(picker)[0];

  await userEvent.hover(segments[1]!);
  const [first, second, third] = fills(picker);

  expect(first).not.toBe(selected);
  expect(second).toBe(first);
  expect(third).toBe('rgba(0, 0, 0, 0)');
  expect(getComputedStyle(segments[1]!).cursor).toBe('pointer');
});
