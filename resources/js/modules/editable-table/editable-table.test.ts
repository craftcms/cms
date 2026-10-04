import $ from 'jquery';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {EditableTable, Row} from './editable-table';

afterEach(() => {
  vi.useRealTimers();
  vi.unstubAllGlobals();
  document.body.replaceChildren();
});

it.each([1, 3])('populates a hidden table with %i minimum rows', (minRows) => {
  vi.useFakeTimers();
  vi.stubGlobal('$', $);
  vi.stubGlobal('Garnish', {});
  vi.stubGlobal('Craft', {
    hasMousePointerEvents: () => false,
    inArray: <T>(value: T, values: T[]) => values.includes(value),
  });

  document.body.innerHTML = `
    <div class="input" style="display: none">
      <table id="things"><tbody></tbody></table>
      <button type="button" command="--add-row">Add a row</button>
    </div>
  `;

  const table = new EditableTable(
    'things',
    'settings[things]',
    {label: {type: 'singleline', heading: 'Label'}},
    {allowAdd: true, minRows, maxRows: minRows, defaultValues: {label: 'Thing'}}
  );

  expect(document.querySelectorAll('tbody tr')).toHaveLength(minRows);
  const values = Array.from(document.querySelectorAll('textarea')).map(
    (input) => [input.name, input.value]
  );
  expect(values).toEqual(
    Array.from({length: minRows}, (_, index) => [
      `settings[things][${index}][label]`,
      'Thing',
    ])
  );
  expect(document.querySelector('button')?.getAttribute('aria-disabled')).toBe(
    'true'
  );

  table.initialize();
  expect(document.querySelectorAll('tbody tr')).toHaveLength(minRows);
  table.destroy();
  vi.clearAllTimers();
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

it.each(['autosuggest', 'template', 'singleline', 'multiline'])(
  'renders %s cells with accessible text expanders when configured',
  (type) => {
    vi.stubGlobal('$', $);
    vi.stubGlobal('Craft', {
      hasMousePointerEvents: () => true,
      inArray: (value: unknown, values: unknown[]) => values.includes(value),
      ui: {
        createTextInput: (config: Record<string, string>) =>
          $('<input>', {
            id: config.id,
            name: config.name,
            value: config.value,
          }),
      },
    });

    const triggers = [
      {
        trigger: '$',
        boundary: 'start' as const,
        options: [{label: '$SYSTEM_EMAIL', value: '$SYSTEM_EMAIL'}],
      },
    ];
    const row = EditableTable.createRow(
      'site-uid',
      {
        fromEmail: {
          type,
          heading: 'System Email Address',
          textExpanderTriggers: triggers,
        },
      },
      'siteOverrides',
      {fromEmail: '$SYSTEM_EMAIL'}
    );
    document.body.append(row[0]);
    const input = row.find('input, textarea')[0] as
      | HTMLInputElement
      | HTMLTextAreaElement;
    const expander = row.find('craft-text-expander')[0];

    expect(row.find('craft-combobox')).toHaveLength(0);
    expect(input.name).toBe('siteOverrides[site-uid][fromEmail]');
    expect(input.value).toBe('$SYSTEM_EMAIL');
    expect(input.getAttribute('aria-label')).toBe('System Email Address');
    expect(expander.for).toBe(input.id);
    expect(expander.triggers).toEqual(triggers);
  }
);
