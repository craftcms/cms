import {afterEach, expect, it} from 'vite-plus/test';
import './index';
import type CraftEditableTable from './editable-table.ce';

afterEach(() => {
  document.body.innerHTML = '';
  delete (window as any).Craft;
});

it('serializes keyed rows in DOM order and normalizes column values', () => {
  (window as any).Craft = {
    expandPostArray: () => ({
      rows: {
        first: {enabled: '1', kind: '2'},
        second: {kind: '1'},
      },
    }),
  };
  document.body.innerHTML = `
    <craft-editable-table
      name="rows"
      keyed
      cols='{"enabled":{"type":"checkbox"},"kind":{"type":"select","options":[{"value":1},{"value":2}]}}'
      settings='{"staticRows":true}'
    >
      <table><tbody><tr data-id="second"></tr><tr data-id="first"></tr></tbody></table>
    </craft-editable-table>
  `;

  const table = document.querySelector<CraftEditableTable>(
    'craft-editable-table'
  )!;

  expect(table.serialize()).toEqual({
    second: {enabled: false, kind: 1},
    first: {enabled: true, kind: 2},
  });
});
