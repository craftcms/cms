import {createApp, defineComponent, h, nextTick, reactive} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {stubUiRenderer} from './renderer/context.test-support';
import ActionMenuNode from './ActionMenuNode.vue';
import FieldNode from './FieldNode.vue';
import {clearCopiedFieldValue, fieldValueFormats} from './copiedFieldValue';
import type {UiChange, UiNodePayload, UiValues} from './types';

const STORAGE_KEY = 'Craft-test.copiedFieldValue';

const ElementsControl = defineComponent({
  props: ['value'],
  render() {
    return h('div', [
      h('output', {'data-elements-control': ''}, String(this.value)),
      // Something text is typed into, such as a search box
      h('input', {type: 'search'}),
    ]);
  },
});

function fieldNode(
  handle: string,
  valueScope: string,
  elements: {id: number; label: string}[] = []
): UiNodePayload {
  return {
    type: 'Field',
    component: 'craft:field',
    props: {label: handle},
    control: {
      type: 'ElementSelect',
      component: 'craft:element-select',
      path: ['fields', handle],
      deltaGroup: ['fields', handle],
      mode: 'editable',
      props: {elements, elementType: 'Entry'},
      readable: true,
      writable: true,
      valueProps: ['elements'],
      valueScope,
    },
    children: [
      {
        type: 'ActionMenu',
        component: 'craft:action-menu',
        uid: `field-actions:fields.${handle}:menu`,
        props: {
          label: 'Field actions',
          items: [
            {
              type: 'button',
              label: 'Copy value',
              action: {type: 'event', name: 'craft:copy-field-value'},
            },
            {
              type: 'button',
              label: 'Paste value',
              action: {type: 'event', name: 'craft:paste-field-value'},
              hidden: true,
            },
          ],
        },
      },
    ],
  } as UiNodePayload;
}

describe('FieldNode value clipboard', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;

  beforeEach(() => {
    vi.stubGlobal('Craft', {systemUid: 'test', cp: {}});
    // `craft-icon`s fetch their SVGs; left in flight they're aborted at
    // teardown and reported as unhandled errors.
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => new Response('<svg></svg>'))
    );
    container = document.createElement('div');
    document.body.append(container);
  });

  afterEach(() => {
    clearCopiedFieldValue();
    app?.unmount();
    container.remove();
    vi.unstubAllGlobals();
  });

  function mount(
    values: UiValues,
    nodes: UiNodePayload[],
    onChange: (change: UiChange) => void
  ) {
    app = createApp({
      setup() {
        return () =>
          nodes.map((node) =>
            h(FieldNode, {
              node,
              values,
              errors: [],
              touchedPaths: new Set<string>(),
              scope: [],
              refreshable: false,
              onChange,
            })
          );
      },
    });
    app.component('craft:element-select', ElementsControl);
    app.component('craft:action-menu', ActionMenuNode);
    app.provide(...stubUiRenderer(values));
    app.mount(container);
  }

  function field(handle: string): Element {
    return [...container.querySelectorAll('craft-field')].find((candidate) =>
      candidate.textContent?.includes(handle)
    )!;
  }

  function actionItem(handle: string, label: string): HTMLElement {
    return [
      ...field(handle).querySelectorAll<HTMLElement>('craft-action-item'),
    ].find((item) => item.textContent?.trim() === label)!;
  }

  it('pastes a copied value alone, without the props it was stored with, into fields in the same value scope', async () => {
    const values = reactive<UiValues>({
      fields: {source: [7, 9], target: [], other: []},
    });
    const changed = vi.fn<(change: UiChange) => void>();
    mount(
      values,
      [
        fieldNode('source', 'related-entries', [
          {id: 7, label: 'Apples'},
          {id: 9, label: 'Pears'},
        ]),
        fieldNode('target', 'related-entries'),
        fieldNode('other', 'featured-entries'),
      ],
      changed
    );
    await vi.waitFor(() =>
      expect(actionItem('source', 'Copy value')).toBeTruthy()
    );

    // Blank fields have nothing to copy, and with nothing to paste either,
    // no menu that would open empty
    expect(field('target').querySelector('craft-action-menu')).toBeNull();

    actionItem('source', 'Copy value').click();
    await nextTick();

    const stored = JSON.parse(localStorage.getItem(STORAGE_KEY)!);
    expect(stored).toMatchObject({
      value: [7, 9],
      component: 'craft:element-select',
      valueScope: 'related-entries',
      label: 'source',
      text: 'Apples\nPears',
    });
    await vi.waitFor(() =>
      expect(actionItem('target', 'Paste value').hidden).toBe(false)
    );
    expect(actionItem('target', 'Copy value')).toBeUndefined();
    expect(field('other').querySelector('craft-action-menu')).toBeNull();

    actionItem('target', 'Paste value').click();
    await nextTick();

    expect(values.fields).toEqual({source: [7, 9], target: [7, 9], other: []});
    expect(changed).toHaveBeenCalledOnce();
    const change = changed.mock.calls[0]![0];
    expect(change.path).toEqual(['fields', 'target']);
    // Any script on the site could have changed the stored props
    expect(change.control).toBeUndefined();

    // Each field gets its own copy of the value
    (values.fields as {target: number[]}).target.push(11);
    expect((values.fields as {source: number[]}).source).toEqual([7, 9]);
  });

  it('copies relations as they’re selected now, not as the server last drew them', async () => {
    // Since the server drew it, Apples was removed and Plums moved first
    const values = reactive<UiValues>({fields: {source: [11, 9]}});
    mount(
      values,
      [
        fieldNode('source', 'related-entries', [
          {id: 7, label: 'Apples'},
          {id: 9, label: 'Pears'},
          {id: 11, label: 'Plums'},
        ]),
      ],
      vi.fn()
    );
    await vi.waitFor(() =>
      expect(actionItem('source', 'Copy value')).toBeTruthy()
    );

    actionItem('source', 'Copy value').click();
    await nextTick();

    const stored = JSON.parse(localStorage.getItem(STORAGE_KEY)!);
    expect(stored.text).toBe('Plums\nPears');
    expect(
      stored.props.elements.map((element: {id: number}) => element.id)
    ).toEqual([11, 9]);
  });

  it('pastes the copied value with Ctrl/Cmd+V while the clipboard still holds its text', async () => {
    const values = reactive<UiValues>({fields: {source: [7, 9], target: []}});
    const changed = vi.fn<(change: UiChange) => void>();
    mount(
      values,
      [
        fieldNode('source', 'related-entries', [
          {id: 7, label: 'Apples'},
          {id: 9, label: 'Pears'},
        ]),
        fieldNode('target', 'related-entries'),
      ],
      changed
    );
    await vi.waitFor(() =>
      expect(actionItem('source', 'Copy value')).toBeTruthy()
    );
    actionItem('source', 'Copy value').click();
    await nextTick();

    const target = [...container.querySelectorAll('craft-field')][1]!;
    function paste(on: Element, text: string): Event {
      const event = new Event('paste', {
        bubbles: true,
        cancelable: true,
        composed: true,
      });
      Object.defineProperty(event, 'clipboardData', {
        value: {getData: (type: string) => (type === 'text/plain' ? text : '')},
      });
      on.dispatchEvent(event);

      return event;
    }

    // Something else was copied since
    expect(
      paste(target.querySelector('output')!, 'Apples').defaultPrevented
    ).toBe(false);
    // Text typed into the field pastes as text
    expect(
      paste(target.querySelector('input')!, 'Apples\nPears').defaultPrevented
    ).toBe(false);
    expect(changed).not.toHaveBeenCalled();

    // Line endings can change on the way through the clipboard
    expect(
      paste(target.querySelector('output')!, 'Apples\r\nPears\r\n')
        .defaultPrevented
    ).toBe(true);
    expect(values.fields).toEqual({source: [7, 9], target: [7, 9]});
    expect(changed).toHaveBeenCalledOnce();
  });
});

describe('fieldValueFormats', () => {
  it('puts a table on the clipboard as rows spreadsheets split into cells', () => {
    const formats = fieldValueFormats(
      {
        type: 'Table',
        component: 'craft:table',
        path: ['fields', 'programme'],
        deltaGroup: ['fields', 'programme'],
        mode: 'editable',
        props: {
          columns: {
            col1: {heading: 'Event', type: 'singleline'},
            col2: {
              heading: 'Size',
              type: 'select',
              // Grouped, as Table columns keep them
              options: [
                {
                  optgroup: 'Sizes',
                  options: [{label: 'Small', value: 's'}],
                },
              ],
            },
          },
        },
      },
      {
        value: [
          {col1: 'Opening <talk>', col2: 's', event: 'Opening <talk>'},
          {col1: '  Two\tlines "quoted"', col2: ''},
        ],
      }
    );

    expect(formats.text).toBe(
      'Opening <talk>\tSmall\n"  Two\tlines ""quoted"""\t'
    );
    expect(formats.html).toBe(
      '<table><thead><tr><th>Event</th><th>Size</th></tr></thead><tbody>' +
        '<tr><td>Opening &lt;talk&gt;</td><td>Small</td></tr>' +
        '<tr><td>  Two\tlines &quot;quoted&quot;</td><td></td></tr></tbody></table>'
    );
  });

  const control = (component: string, props: object = {}) => ({
    type: 'Control',
    component,
    path: ['fields', 'field'],
    deltaGroup: ['fields', 'field'],
    mode: 'editable' as const,
    props,
  });

  it('puts a link on the clipboard as its URL, linked from its label in HTML', () => {
    const link = control('craft:link');

    expect(
      fieldValueFormats(link, {
        value: {
          value: 'https://craftcms.com/docs',
          urlSuffix: '#intro',
          label: 'Docs & guides',
          type: 'url',
        },
      })
    ).toEqual({
      text: 'https://craftcms.com/docs#intro',
      html: '<a href="https://craftcms.com/docs#intro">Docs &amp; guides</a>',
    });
    // Addresses without their scheme or query, labeled with them when there's no label
    expect(
      fieldValueFormats(link, {
        value: {value: 'mailto:hi@example.com?subject=Hi', type: 'email'},
      })
    ).toEqual({
      text: 'hi@example.com',
      html: '<a href="mailto:hi@example.com?subject=Hi">hi@example.com</a>',
    });
    // Element links are reference tags, so only their labels go on the clipboard
    expect(
      fieldValueFormats(link, {
        value: {
          value: '{entry:12@1:url}',
          type: 'entry',
          defaultLabel: 'About',
        },
      })
    ).toEqual({text: 'About', html: null});
    // Other URLs keep their destination, unlinked
    expect(
      fieldValueFormats(link, {
        value: {
          value: '/about',
          urlSuffix: '#team',
          label: 'About us',
          type: 'url',
        },
      })
    ).toEqual({text: '/about#team', html: null});
    expect(
      fieldValueFormats(link, {
        value: {value: 'javascript:alert(1)', type: 'url', label: 'Run'},
      }).html
    ).toBeNull();
  });

  it('puts an amount on the clipboard with its currency, read in its locale', () => {
    expect(
      fieldValueFormats(
        control('craft:money', {currency: 'USD', locale: 'en-US'}),
        {value: {value: '1,999', locale: 'en-US'}}
      ).text
    ).toBe('$1,999.00');
    expect(
      fieldValueFormats(
        control('craft:money', {currency: 'EUR', locale: 'en-US'}),
        {value: {value: '1.999,5', locale: 'de-DE'}}
      ).text
    ).toBe('1.999,50\u00a0€');
  });

  it('puts a date on the clipboard as the user’s locale writes it, unconverted', () => {
    const date = (props: object, value: UiValues) =>
      fieldValueFormats(control('craft:date-time', props), {value}).text;

    expect(
      date(
        {locale: 'en-US'},
        {date: '2026-11-13', time: '23:30', timezone: 'Pacific/Auckland'}
      )
    ).toMatch(/^Nov 13, 2026, 11:30\sPM$/u);
    expect(
      date(
        {locale: 'en-US', showTimeZone: true},
        {date: '2026-11-13', time: '00:15', timezone: 'Pacific/Auckland'}
      )
    ).toMatch(/^Nov 13, 2026, 12:15\sAM \(Pacific\/Auckland\)$/u);
    expect(date({locale: 'de_DE'}, {date: '2026-01-02', time: null})).toBe(
      '02.01.2026'
    );
    // Only the parts the field shows, though values hold both
    const both = {date: '2026-11-13', time: '23:30', timezone: 'UTC'};
    expect(date({locale: 'en-US', showDate: true, showTime: false}, both)).toBe(
      'Nov 13, 2026'
    );
    expect(
      date({locale: 'en-US', showDate: false, showTime: true}, both)
    ).toMatch(/^11:30\sPM$/u);
    // A cleared field keeps its hidden part, which isn't copied
    expect(
      date(
        {locale: 'en-US', showDate: true, showTime: false},
        {date: '', time: '23:30', timezone: 'UTC'}
      )
    ).toBe('');
  });

  it('keeps options’ labels apart, with their icons and colors', () => {
    const choice = (multiple: boolean) => ({
      type: 'Choice',
      component: 'craft:choice',
      path: ['fields', 'colors'],
      deltaGroup: ['fields', 'colors'],
      mode: 'editable' as const,
      props: {
        multiple,
        options: [
          {label: 'Red, dark', value: 'red', icon: 'fire', color: '#b91c1c'},
          {label: 'Blue', value: 'blue', color: '#1d4ed8'},
        ],
      },
    });
    const formats = fieldValueFormats(choice(true), {
      value: ['red', 'blue', 'teal'],
    });

    expect(formats.text).toBe('Red, dark, Blue, teal');
    expect(formats.items).toEqual([
      {label: 'Red, dark', icon: 'fire', color: '#b91c1c'},
      {label: 'Blue', icon: null, color: '#1d4ed8'},
      {label: 'teal'},
    ]);
    // One selected option is an item too, but text that isn't one isn't
    expect(fieldValueFormats(choice(false), {value: 'blue'}).items).toEqual([
      {label: 'Blue', icon: null, color: '#1d4ed8'},
    ]);
    expect(fieldValueFormats(choice(false), {value: 'teal'}).items).toBeNull();
  });
});
