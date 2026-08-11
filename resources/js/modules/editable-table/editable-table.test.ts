import $ from 'jquery';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {type EditableTable, Row} from './editable-table';

afterEach(() => {
  vi.unstubAllGlobals();
  document.body.replaceChildren();
});

it('initializes text cells without the legacy NiceText behavior', () => {
  vi.stubGlobal('$', $);
  vi.stubGlobal('Garnish', {});
  vi.stubGlobal('Craft', {
    hasMousePointerEvents: () => true,
    inArray: (value: unknown, values: unknown[]) => values.includes(value),
  });

  const table = {
    biggestId: -1,
    columns: {label: {type: 'singleline'}},
    radioCheckboxes: {},
    settings: {rowIdPrefix: ''},
  } as unknown as EditableTable;
  const row = document.createElement('tr');
  row.dataset.id = '0';
  row.innerHTML = '<td><textarea name="options[0][label]"></textarea></td>';
  document.body.append(row);

  const instance = new Row(table, row);

  expect(instance.niceTexts).toEqual([]);

  instance.destroy();
});
