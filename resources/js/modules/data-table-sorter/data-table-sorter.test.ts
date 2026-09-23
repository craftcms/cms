import $ from 'jquery';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {DataTableSorter} from './data-table-sorter';

afterEach(() => {
  vi.unstubAllGlobals();
  document.body.replaceChildren();
});

it('builds a drag helper from the native row supplied by Garnish', () => {
  vi.stubGlobal('$', $);

  const table = document.createElement('table');
  table.className = 'data fullwidth';
  const body = table.createTBody();
  const row = body.insertRow();
  row.insertCell().textContent = 'First';
  document.body.append(table);
  const sorter = Object.assign(Object.create(DataTableSorter.prototype), {
    settings: {helperClass: 'datatablesorthelper'},
    $table: $(table),
  }) as DataTableSorter;

  const helper = sorter.getHelper(row.cloneNode(true) as HTMLTableRowElement);

  expect(helper).toBeInstanceOf(HTMLDivElement);
  expect(helper.className).toBe('datatablesorthelper');
  expect(helper.querySelector('table')?.className).toBe('data fullwidth');
  expect(helper.querySelector('tr')?.textContent).toBe('First');
});
