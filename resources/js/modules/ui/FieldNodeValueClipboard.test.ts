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

  function actionItem(handle: string, label: string): HTMLElement {
    const field = [...container.querySelectorAll('craft-field')].find(
      (candidate) => candidate.textContent?.includes(handle)
    )!;

    return [...field.querySelectorAll<HTMLElement>('craft-action-item')].find(
      (item) => item.textContent?.trim() === label
    )!;
  }

  it('pastes a copied value, with the props it draws it with, into fields in the same value scope', async () => {
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

    expect(actionItem('target', 'Paste value').hidden).toBe(true);

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
    expect(actionItem('other', 'Paste value').hidden).toBe(true);

    actionItem('target', 'Paste value').click();
    await nextTick();

    expect(values.fields).toEqual({source: [7, 9], target: [7, 9], other: []});
    expect(changed).toHaveBeenCalledOnce();
    const change = changed.mock.calls[0]![0];
    expect(change.path).toEqual(['fields', 'target']);
    expect(change.control?.props).toMatchObject({
      elements: [
        {id: 7, label: 'Apples'},
        {id: 9, label: 'Pears'},
      ],
    });

    // Each field gets its own copy of the value
    (values.fields as {target: number[]}).target.push(11);
    expect((values.fields as {source: number[]}).source).toEqual([7, 9]);
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
              options: [{label: 'Small', value: 's'}],
            },
          },
        },
      },
      {
        value: [
          {col1: 'Opening <talk>', col2: 's', event: 'Opening <talk>'},
          {col1: 'Two\tlines "quoted"', col2: ''},
        ],
      }
    );

    expect(formats.text).toBe(
      'Opening <talk>\tSmall\n"Two\tlines ""quoted"""\t'
    );
    expect(formats.html).toBe(
      '<table><thead><tr><th>Event</th><th>Size</th></tr></thead><tbody>' +
        '<tr><td>Opening &lt;talk&gt;</td><td>Small</td></tr>' +
        '<tr><td>Two\tlines &quot;quoted&quot;</td><td></td></tr></tbody></table>'
    );
  });
});
