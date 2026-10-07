import $ from 'jquery';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {EditableTable, Row} from './editable-table';
import type {EditableTableRow} from './types';

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

it.each([
  {
    kind: 'flat',
    options: [{label: 'Environment', value: '$SYSTEM_EMAIL'}],
    expectedOptions: [{label: 'Environment', value: '$SYSTEM_EMAIL'}],
  },
  {
    kind: 'grouped',
    options: [
      {
        label: 'Environment',
        options: [{label: 'System email', value: '$SYSTEM_EMAIL'}, {value: 0}],
      },
      {label: 'Literal address', value: 'admin@example.com'},
    ],
    expectedOptions: [
      {
        type: 'optgroup',
        label: 'Environment',
        options: [
          {label: 'System email', value: '$SYSTEM_EMAIL'},
          {label: '0', value: '0'},
        ],
      },
      {label: 'Literal address', value: 'admin@example.com'},
    ],
  },
])(
  'renders autosuggest cells with $kind combobox options',
  async ({options, expectedOptions}) => {
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
          options,
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
    expect(combobox.options).toEqual(expectedOptions);
    expect(combobox.modelValue).toBe('$SYSTEM_EMAIL');
    expect(combobox.showAllOnEmpty).toBe(true);
  }
);

it.each<{
  kind: string;
  values: EditableTableRow;
  expectedValue: string;
  expectedLocale: string;
}>([
  {
    kind: 'saved',
    values: {amount: {value: 12.5, locale: 'fr-BE'}},
    expectedValue: '12.5',
    expectedLocale: 'fr-BE',
  },
  {
    kind: 'new',
    values: {amount: ''},
    expectedValue: '',
    expectedLocale: 'nl-BE',
  },
])(
  'renders a $kind money cell with its amount and submission locale',
  async ({values, expectedValue, expectedLocale}) => {
    vi.stubGlobal('$', $);
    vi.stubGlobal('Craft', {
      inArray: (value: unknown, values: unknown[]) => values.includes(value),
    });

    const row = EditableTable.createRow(
      '0',
      {
        amount: {
          type: 'money',
          heading: 'Amount',
          currency: 'EUR',
          locale: 'nl-BE',
          decimals: 3,
          decimalSeparator: ',',
          groupSeparator: '.',
          showCurrency: false,
          clearable: false,
        },
      },
      'prices',
      values
    );
    document.body.append(row[0]);
    const money = document.querySelector('craft-input-money');
    if (!money) throw new Error('Expected the money input.');
    await money.updateComplete;

    expect(money.name).toBe('prices[0][amount][value]');
    expect(money.modelValue).toBe(expectedValue);
    expect(money.currency).toBe('EUR');
    expect(money.locale).toBe(expectedLocale);
    expect(money.decimals).toBe(3);
    expect(money.decimalSeparator).toBe(',');
    expect(money.groupSeparator).toBe('.');
    expect(money.showCurrency).toBe(false);
    expect(money.clearable).toBe(false);
    expect(money.label).toBe('Amount');
    expect(money.hasAttribute('label-sr-only')).toBe(true);
    const locale = row[0].querySelector('input[type="hidden"]');
    expect(locale?.name).toBe('prices[0][amount][locale]');
    expect(locale?.value).toBe(expectedLocale);
  }
);

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
