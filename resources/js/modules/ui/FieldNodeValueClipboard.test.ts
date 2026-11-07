import {createApp, defineComponent, h, nextTick, reactive} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {stubUiRenderer} from './renderer/context.test-support';
import ActionMenuNode from './ActionMenuNode.vue';
import FieldNode from './FieldNode.vue';
import {clearCopiedFieldValue} from './copiedFieldValue';
import type {UiChange, UiNodePayload, UiValues} from './types';

const STORAGE_KEY = 'Craft-test.copiedFieldValue';

const ElementsControl = defineComponent({
  props: ['value'],
  render() {
    return h('output', {'data-elements-control': ''}, String(this.value));
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
      preview: 'Apples, Pears',
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
});
