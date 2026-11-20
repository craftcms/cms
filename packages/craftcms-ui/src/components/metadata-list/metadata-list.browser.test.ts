import {beforeEach, expect, it} from 'vite-plus/test';
import './metadata-list.js';
import type CraftMetadataList from './metadata-list.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function createList(
  width: string,
  layout?: string
): Promise<CraftMetadataList> {
  const list = document.createElement('craft-metadata-list');
  if (layout) {
    list.setAttribute('layout', layout);
  }
  list.style.width = width;
  list.innerHTML = `
    <dl>
      <div><dt>ID</dt><dd>2</dd></div>
      <div><dt>Status</dt><dd>Live</dd></div>
    </dl>
  `;
  document.body.append(list);
  await list.updateComplete;
  return list;
}

function top(list: HTMLElement, selector: string): number {
  return list.querySelector(selector)!.getBoundingClientRect().top;
}

/** Whether the first item's label and value share a line. */
function labelBesideValue(list: HTMLElement): boolean {
  return top(list, 'dt') === top(list, 'dd');
}

it('puts each label beside its value inline, however narrow', async () => {
  const list = await createList('10rem', 'inline');

  expect(labelBesideValue(list)).toBe(true);
});

it('puts each label above its value when stacked, however wide', async () => {
  const list = await createList('40rem', 'stacked');

  expect(labelBesideValue(list)).toBe(false);
});

it('follows its own width in auto', async () => {
  const narrow = await createList('12rem');
  const wide = await createList('24rem');

  expect(labelBesideValue(narrow)).toBe(false);
  expect(labelBesideValue(wide)).toBe(true);
});

it('lays the items side by side in a row', async () => {
  const list = await createList('40rem', 'row');
  const [first, second] = list.querySelectorAll('dt');

  expect(first!.getBoundingClientRect().top).toBe(
    second!.getBoundingClientRect().top
  );
  expect(labelBesideValue(list)).toBe(false);
});
