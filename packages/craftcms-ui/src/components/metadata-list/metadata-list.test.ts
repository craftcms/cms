import {beforeEach, expect, it} from 'vite-plus/test';
import './metadata-list.js';
import type CraftMetadataList from './metadata-list.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function createList(layout?: string): Promise<CraftMetadataList> {
  const list = document.createElement('craft-metadata-list');
  if (layout) {
    list.setAttribute('layout', layout);
  }
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

/** The terms only mean anything to assistive technology inside a native list. */
it('keeps its description list in the light DOM', async () => {
  const list = await createList();

  expect(list.shadowRoot).toBeNull();
  expect(list.querySelectorAll(':scope > dl > div > dt')).toHaveLength(2);
  expect(list.querySelectorAll(':scope > dl > div > dd')).toHaveLength(2);
});

it('lays out automatically by default', async () => {
  const list = await createList();

  expect(list.layout).toBe('auto');
  expect(list.getAttribute('layout')).toBe('auto');
});
