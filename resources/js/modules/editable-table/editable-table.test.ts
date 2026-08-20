import $ from 'jquery';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {EditableTable, Row} from './editable-table';

afterEach(() => {
  vi.unstubAllGlobals();
  document.body.replaceChildren();
});

it('initializes text cells without the legacy NiceText behavior', () => {
  vi.stubGlobal('$', $);
  vi.stubGlobal('Garnish', {});
  vi.stubGlobal('Craft', {
    hasMousePointerEvents: () => true,
    inArray: <T>(value: T, values: T[]) => values.includes(value),
  });

  const table = Object.assign(Object.create(EditableTable.prototype), {
    biggestId: -1,
    columns: {label: {type: 'singleline'}},
    radioCheckboxes: {},
    settings: {rowIdPrefix: ''},
  });
  const row = document.createElement('tr');
  row.dataset.id = '0';
  row.innerHTML = '<td><textarea name="options[0][label]"></textarea></td>';
  document.body.append(row);

  const instance = new Row(table, row);

  expect(instance.niceTexts).toEqual([]);

  instance.destroy();
});

it('renders autosuggest cells as comboboxes', async () => {
  vi.stubGlobal('$', $);
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ok: false}));
  vi.stubGlobal('Craft', {
    hasMousePointerEvents: () => true,
    inArray: <T>(value: T, values: T[]) => values.includes(value),
  });

  const row = EditableTable.createRow(
    'site-uid',
    {
      fromEmail: {
        type: 'autosuggest',
        heading: 'System Email Address',
        options: [{label: 'Environment', value: '$SYSTEM_EMAIL'}],
      },
    },
    'siteOverrides',
    {fromEmail: '$SYSTEM_EMAIL'}
  );
  document.body.append(row[0]);
  const combobox = row[0]?.querySelector('craft-combobox');
  if (!combobox) throw new Error('Expected the autosuggest combobox.');
  await combobox.updateComplete;

  expect(combobox.name).toBe('siteOverrides[site-uid][fromEmail]');
  expect(combobox.label).toBe('System Email Address');
  expect(combobox.modelValue).toBe('$SYSTEM_EMAIL');
  expect(combobox.options).toEqual([
    {label: 'Environment', value: '$SYSTEM_EMAIL'},
  ]);
  expect(combobox.showAllOnEmpty).toBe(true);
});
