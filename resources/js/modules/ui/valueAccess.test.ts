import {describe, expect, it} from 'vite-plus/test';
import type {UiControlPayload, UiValue} from './types';
import {acceptsValue} from './valueAccess';

function control(
  component: string,
  props: Record<string, unknown> = {},
  extra: Partial<UiControlPayload<object>> = {}
): UiControlPayload<object> {
  return {
    type: 'Control',
    component,
    path: ['fields', 'target'],
    deltaGroup: ['fields', 'target'],
    mode: 'editable',
    props,
    readable: true,
    writable: true,
    ...extra,
  } as UiControlPayload<object>;
}

function takes(
  target: UiControlPayload<object>,
  value: UiValue,
  origin: Partial<UiControlPayload<object>> = {}
): boolean {
  return acceptsValue(
    target,
    {component: target.component, valueScope: target.valueScope, ...origin},
    {value}
  );
}

describe('acceptsValue', () => {
  it('takes values only from the same kind of control, in the same value scope', () => {
    const relations = control('craft:element-select', {}, {valueScope: 'a'});

    expect(takes(relations, [1])).toBe(true);
    expect(takes(relations, [1], {valueScope: 'b'})).toBe(false);
    expect(takes(relations, [1], {valueScope: undefined})).toBe(false);
    expect(takes(relations, [1], {component: 'craft:combobox'})).toBe(false);
    expect(
      takes({...relations, mode: 'readOnly'} as UiControlPayload<object>, [1])
    ).toBe(false);
    expect(takes({...relations, writable: undefined}, [1])).toBe(false);
  });

  it('keeps line breaks and overlong text out of text inputs', () => {
    const text = control('craft:text', {maxLength: 3});

    expect(takes(text, 'abc')).toBe(true);
    // Counted in characters, not UTF-16 code units
    expect(takes(text, 'ab😀')).toBe(true);
    expect(takes(text, 'abcd')).toBe(false);
    expect(takes(control('craft:text'), 'one\ntwo')).toBe(false);
    expect(takes(control('craft:text', {inputType: 'email'}), 'ada')).toBe(
      false
    );
    expect(
      takes(control('craft:text', {inputType: 'email'}), 'ada@example.com')
    ).toBe(true);
    expect(takes(control('craft:textarea'), 'one\ntwo')).toBe(true);
    expect(
      takes(control('craft:textarea', {singleLine: true}), 'one\ntwo')
    ).toBe(false);
    expect(takes(control('craft:textarea', {maxLength: 3}), 'abcd')).toBe(
      false
    );
  });

  it('keeps numbers within their bounds', () => {
    const number = control('craft:number', {min: 1, max: 10});

    expect(takes(number, '10')).toBe(true);
    expect(takes(number, 11)).toBe(false);
    expect(takes(number, 0)).toBe(false);
    expect(takes(number, 'ten')).toBe(false);
    expect(takes(number, '')).toBe(true);
    // Bounds set as strings are still numbers
    const stringBounds = control('craft:number', {min: '1', max: '10'});
    expect(takes(stringBounds, 5)).toBe(true);
    expect(takes(stringBounds, '20')).toBe(false);
    expect(takes(control('craft:money', {min: 5}), {value: '4'})).toBe(false);
    expect(takes(control('craft:money', {min: 5}), {value: '5'})).toBe(true);
    // Amounts are formatted for their locale.
    const money = control('craft:money', {min: 1000, max: 2000});
    expect(takes(money, {value: '1,999.50', locale: 'en-US'})).toBe(true);
    expect(takes(money, {value: '1.999,50', locale: 'de-DE'})).toBe(true);
    // With the locale's own digits, minus signs, and direction marks
    expect(takes(money, {value: '\u061c١٬٩٩٩٫٥٠', locale: 'ar-EG'})).toBe(true);
    expect(
      takes(control('craft:money', {max: -1000}), {
        value: '\u22121\u00a0999,50',
        locale: 'sv-SE',
      })
    ).toBe(true);
    expect(takes(money, {value: '2,000.01', locale: 'en-US'})).toBe(false);
    expect(takes(money, {value: '1,9x9', locale: 'en-US'})).toBe(false);
  });

  it('takes only the options a choice offers', () => {
    const options = [
      {label: 'Red', value: 'red'},
      {label: 'Blue', value: 'blue'},
    ];

    expect(takes(control('craft:choice', {options}), 'red')).toBe(true);
    expect(takes(control('craft:choice', {options}), 'green')).toBe(false);
    expect(takes(control('craft:choice', {options}), ['red', 'blue'])).toBe(
      false
    );
    expect(
      takes(control('craft:choice', {options, multiple: true}), ['red', 'blue'])
    ).toBe(true);
    expect(
      takes(control('craft:choice', {options, allowCustom: true}), 'green')
    ).toBe(true);
    // One option isn't a list of them, and the reverse
    expect(
      takes(control('craft:choice', {options, multiple: true}), 'red')
    ).toBe(false);
    expect(takes(control('craft:choice', {options}), ['red'])).toBe(false);
    expect(
      takes(
        control('craft:choice', {options, multiple: true, allValue: '*'}),
        '*'
      )
    ).toBe(true);
    // Options can be true or false, but disabled ones can't be chosen
    const toggles = [
      {label: 'On', value: true},
      {label: 'Off', value: false, disabled: true},
    ];
    expect(takes(control('craft:choice', {options: toggles}), true)).toBe(true);
    expect(takes(control('craft:choice', {options: toggles}), false)).toBe(
      false
    );
    expect(
      takes(
        control('craft:choice', {
          options: [...options, {label: 'Gray', value: 'gray', disabled: true}],
          multiple: true,
        }),
        ['red', 'gray']
      )
    ).toBe(false);
  });

  it('takes as many combobox choices as there are, in the shape it holds', () => {
    const options = [
      {label: 'Red', value: 'red'},
      {label: 'Blue', value: 'blue'},
    ];
    // `limit` caps the options listed at once, not how many can be chosen.
    const multiple = control('craft:combobox', {
      options,
      multiple: true,
      limit: 1,
      requireOptionMatch: true,
    });

    expect(takes(multiple, ['red', 'blue'])).toBe(true);
    expect(takes(multiple, 'red')).toBe(false);
    expect(takes(multiple, ['green'])).toBe(false);
    expect(takes(control('craft:combobox', {options}), ['red'])).toBe(false);
  });

  it('keeps dates within their bounds', () => {
    const date = control('craft:date-time', {
      min: '2026-01-01',
      max: '2026-12-31',
    });

    expect(takes(date, {date: '2026-11-13', time: '14:30'})).toBe(true);
    expect(takes(date, {date: '2027-01-01', time: '14:30'})).toBe(false);
    expect(takes(date, {date: '', time: ''})).toBe(true);
  });

  it('keeps relations within their limit', () => {
    expect(takes(control('craft:element-select', {limit: 1}), [7])).toBe(true);
    expect(takes(control('craft:element-select', {limit: 1}), [7, 9])).toBe(
      false
    );
  });

  it('takes only rows that fit the table’s columns', () => {
    const table = control('craft:table', {
      columns: {
        col1: {type: 'singleline', handle: 'event'},
        col2: {type: 'checkbox'},
        col3: {
          type: 'select',
          options: [{label: 'Small', value: 's'}],
        },
        col4: {type: 'money'},
        col5: {type: 'date'},
        col6: {type: 'time'},
        col8: {type: 'number', min: 0, max: 10},
        col9: {type: 'email'},
        col10: {type: 'multiline'},
        col11: {type: 'html'},
        col12: {type: 'link', types: ['url']},
        col13: {type: 'color'},
        col14: {heading: 'Custom'},
        col15: {type: 'link'},
        col16: {type: 'money', min: 0, max: 100},
        col17: {type: 'checkbox', radioMode: true},
      },
      maxRows: 2,
    });

    expect(takes(table, [{col1: 'Apples', col2: true, col3: 's'}])).toBe(true);
    // As a Table field holds them, with each cell under its column's handle too
    expect(takes(table, [{col1: 'Apples', event: 'Apples'}])).toBe(true);
    expect(takes(table, [{col1: 'Apples', col2: 'yes'}])).toBe(false);
    // Amounts are held with their locale
    expect(
      takes(table, [
        {col4: {value: '1.999,50', locale: 'de-DE'}},
        {col4: {value: null, locale: 'en-US'}},
      ])
    ).toBe(true);
    // Within their column's bounds
    expect(
      takes(table, [
        {col16: {value: '99.50', locale: 'en-US'}},
        {col16: {value: null, locale: 'en-US'}},
      ])
    ).toBe(true);
    expect(takes(table, [{col16: {value: '150', locale: 'en-US'}}])).toBe(
      false
    );
    // Amounts too long to be a finite number
    expect(
      takes(table, [{col4: {value: '9'.repeat(400), locale: 'en-US'}}])
    ).toBe(false);
    expect(takes(table, [{col4: {value: 'lots', locale: 'en-US'}}])).toBe(
      false
    );
    expect(takes(table, [{col3: 'm'}])).toBe(false);
    // Dates and times as their inputs hold them
    expect(takes(table, [{col5: '2026-11-13', col6: '14:30'}])).toBe(true);
    expect(takes(table, [{col5: 'not-a-date'}])).toBe(false);
    expect(takes(table, [{col6: '2:30 PM'}])).toBe(false);
    expect(takes(table, [{col5: '2024-02-29', col6: '23:59:59'}])).toBe(true);
    expect(takes(table, [{col5: '2026-02-29'}])).toBe(false);
    expect(takes(table, [{col5: '2026-13-01'}])).toBe(false);
    expect(takes(table, [{col6: '99:99'}])).toBe(false);
    expect(takes(table, [{col7: 'Pears'}])).toBe(false);
    // Numbers within their column's bounds, emails as emails, and text as text
    expect(takes(table, [{col8: 10, col9: 'hi@example.com'}])).toBe(true);
    expect(takes(table, [{col8: 20}])).toBe(false);
    expect(takes(table, [{col8: '1.5e0'}, {col8: '.5'}])).toBe(true);
    for (const number of ['0x10', 'Infinity', '1e309', ' 5']) {
      expect(takes(table, [{col8: number}])).toBe(false);
    }
    expect(takes(table, [{col9: 'not-an-email'}])).toBe(false);
    expect(takes(table, [{col10: 'Two\nlines'}])).toBe(true);
    expect(takes(table, [{col10: {value: '1,999', locale: 'en-US'}}])).toBe(
      false
    );
    // Markup isn't pasted, links are links of the types offered, colors are
    // opaque without an alpha channel, and custom controls' values are left
    expect(takes(table, [{col11: '<img src=x onerror=alert(1)>'}])).toBe(false);
    expect(
      takes(table, [{col12: {type: 'url', value: 'https://craftcms.com'}}])
    ).toBe(true);
    expect(takes(table, [{col12: 'Hello'}])).toBe(false);
    expect(takes(table, [{col12: {type: 'entry', value: '{entry:1}'}}])).toBe(
      false
    );
    expect(takes(table, [{col13: '#ff0000'}])).toBe(true);
    expect(takes(table, [{col13: '#ff000080'}])).toBe(false);
    expect(takes(table, [{col13: '#f00'}, {col13: '#ff0000ff'}])).toBe(true);
    expect(takes(table, [{col13: 'red'}])).toBe(false);
    expect(takes(table, [{col14: 'Hello'}])).toBe(false);
    // A link column that offers no types takes no links
    expect(
      takes(table, [{col15: {type: 'url', value: 'https://craftcms.com'}}])
    ).toBe(false);
    // A radio-mode column is checked in one row at most
    expect(takes(table, [{col17: true}, {col17: false}])).toBe(true);
    expect(takes(table, [{col17: true}, {col17: '1'}])).toBe(false);
    expect(takes(table, [{}, {}, {}])).toBe(false);
  });

  it('takes no keyed rows whose order an object can’t keep', () => {
    const table = control('craft:table', {
      columns: {title: {type: 'singleline'}},
      keyed: true,
    });

    expect(takes(table, {row2: {title: 'Tue'}, row0: {title: 'Sun'}})).toBe(
      true
    );
    // Listed 0, 2 whatever order they were copied in
    expect(takes(table, {2: {title: 'Tue'}, 0: {title: 'Sun'}})).toBe(false);
  });

  it('takes no fewer rows than a table must have', () => {
    const table = control('craft:table', {
      columns: {col1: {type: 'singleline'}},
      minRows: 2,
    });

    expect(takes(table, [{col1: 'A'}, {col1: 'B'}])).toBe(true);
    expect(takes(table, [{col1: 'A'}])).toBe(false);
    expect(takes(table, [])).toBe(false);
    expect(takes(table, null)).toBe(false);
  });
});
