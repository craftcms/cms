import {createApp, defineComponent, h, nextTick, reactive} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {useAssistantPanel} from '@/modules/ai/assistant/assistant';
import {
  AssistantEditorKey,
  applyAssistantProposal,
  useAssistantContext,
  type AssistantEditor,
  type AssistantProposal,
} from '@/modules/ai/assistant/context';
import {UiFailure} from './runtime';
import FieldNode from './FieldNode.vue';
import type {UiChange, UiNodePayload, UiValues} from './types';

const TextControl = defineComponent({
  props: ['value'],
  render() {
    return h('input', {value: this.value, 'data-text-control': ''});
  },
});

const node = {
  type: 'Field',
  component: 'craft:field',
  props: {label: 'Summary', layoutUid: 'layout-uid'},
  control: {
    type: 'Text',
    component: 'craft:text',
    path: ['editor', 'fields', 'summary'],
    deltaGroup: ['editor', 'fields', 'summary'],
    mode: 'editable',
    props: {},
  },
} as UiNodePayload;

const editor: AssistantEditor = {
  id: 'editor-1',
  params: () => ({
    elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
    elementId: 42,
    draftId: null,
    siteId: 1,
    ownerId: null,
    fieldId: null,
    canonicalId: 42,
  }),
  label: () => 'Lighthouses',
  scope: () => ['editor'],
  values: () => ({}),
  container: () => null,
};

function proposal(path: string[]): AssistantProposal {
  return {
    key: 'e1:summary',
    ref: null,
    label: 'Summary',
    element: 'Lighthouses',
    elementId: 42,
    canonicalId: 42,
    siteId: 1,
    cpEditUrl: null,
    path,
    value: 'New summary',
    diff: {current: 'Old summary', proposed: 'New summary'},
    preview: null,
    node: null,
  };
}

describe('FieldNode and the AI assistant', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;
  const changed = vi.fn<(change: UiChange) => void>();

  beforeEach(async () => {
    window.Craft = {systemUid: 'field-node-test'} as never;
    container = document.createElement('div');
    document.body.append(container);
    const values = reactive<UiValues>({
      editor: {fields: {summary: 'Old summary'}},
    });

    app = createApp({
      setup() {
        return () =>
          h(FieldNode, {
            node,
            values,
            errors: [],
            touchedPaths: new Set<string>(),
            scope: ['editor'],
            refreshable: false,
            onChange: changed,
          });
      },
    });
    app.component('craft:text', TextControl);
    app.provide(UiFailure, vi.fn());
    app.provide(AssistantEditorKey, editor);
    app.mount(container);
    await nextTick();
  });

  afterEach(() => {
    app.unmount();
    container.remove();
    changed.mockReset();
    useAssistantContext().items.value = [];
    delete (window as {Craft?: unknown}).Craft;
  });

  it('applies a proposed change to the field it’s for', async () => {
    expect(
      applyAssistantProposal(
        proposal(['editor', 'fields', 'summary']),
        editor,
        false
      )
    ).toBe(true);
    await nextTick();

    expect(
      container.querySelector<HTMLInputElement>('[data-text-control]')!.value
    ).toBe('New summary');
    expect(changed).toHaveBeenCalledOnce();
  });

  it('leaves the field alone for a change to another field, or another editor', () => {
    const dialog = document.createElement('div');
    dialog.setAttribute('role', 'dialog');

    expect(
      applyAssistantProposal(
        proposal(['editor', 'fields', 'intro']),
        editor,
        false
      )
    ).toBe(false);
    expect(
      applyAssistantProposal(
        proposal(['editor', 'fields', 'summary']),
        {...editor, container: () => dialog},
        false
      )
    ).toBe(false);
    expect(changed).not.toHaveBeenCalled();
  });

  it('adds the field to the assistant’s context, and opens the assistant', () => {
    window.dispatchEvent(
      new CustomEvent('craft:add-to-ai-assistant', {
        detail: {trigger: container.querySelector('craft-field')},
      })
    );

    expect(useAssistantContext().items.value).toEqual([
      expect.objectContaining({
        key: 'craft:element:editor-1',
        meta: expect.objectContaining({
          fields: [{path: 'fields.summary', label: 'Summary'}],
        }),
      }),
    ]);
    expect(useAssistantPanel().opened.value).toBe(true);
  });
});
