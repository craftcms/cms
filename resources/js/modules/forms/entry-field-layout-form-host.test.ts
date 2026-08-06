import {defineComponent, h, nextTick} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FieldNode from './FieldNode.vue';
import TextControl from './TextControl.vue';
import TabNode from './TabNode.vue';
import CraftInput from '@craftcms/ui/components/input/input';
import {serializeFormInputsAsObject} from '@craftcms/ui';
import {defineEntryFieldLayoutFormHost} from './entry-field-layout-form-host';
import type {FormPayload} from './types';

afterEach(() => {
  vi.useRealTimers();
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
  document.body.replaceChildren();
});

it('submits Entry Form values and preserves refresh context', async () => {
  vi.useFakeTimers();
  const components = createCpComponentRegistry();
  components.register('craft:field', FieldNode);
  components.register('craft:text', TextControl);
  components.register('craft:tab', TabNode);
  components.register(
    'test:nested-refresh',
    defineComponent({
      emits: ['change'],
      setup:
        (_, {emit}) =>
        () =>
          h(
            'button',
            {
              type: 'button',
              'data-nested-refresh': '',
              onClick: () =>
                emit('change', {
                  kind: 'discrete',
                  path: ['editor', 'matrix'],
                  scope: ['editor', 'matrix', 'entries', 'block-a'],
                  refreshable: true,
                }),
            },
            'Refresh nested form'
          ),
    })
  );
  defineEntryFieldLayoutFormHost(components);

  const form = document.createElement('form');
  const host = document.createElement('craft-entry-field-layout-form');
  const payload = {
    scope: ['editor'],
    refreshable: true,
    nodes: [
      {
        type: 'Tab',
        component: 'craft:tab',
        props: {label: 'Content'},
        uid: 'entry-content',
        children: [
          {
            type: 'Field',
            component: 'craft:field',
            props: {label: 'Title', required: true},
            control: {
              type: 'Text',
              component: 'craft:text',
              props: {},
              path: ['editor', 'title'],
              mode: 'editable',
              deltaGroup: ['editor', 'title'],
            },
          },
          {
            type: 'Nested',
            component: 'test:nested-refresh',
            props: {},
            control: {
              type: 'Nested',
              component: 'test:nested-refresh',
              props: {},
              path: ['editor', 'matrix'],
              mode: 'editable',
              deltaGroup: ['editor', 'matrix'],
              forms: [
                {
                  scope: ['editor', 'matrix', 'entries', 'block-a'],
                  refreshable: true,
                  nodes: [],
                },
              ],
            },
          },
        ],
      },
    ],
    values: {
      editor: {
        title: 'Original',
        matrix: {entries: {'block-a': {}}, sortOrder: ['block-a']},
      },
    },
    errors: [],
    globalErrors: [],
  } satisfies FormPayload;
  const sendActionRequest = vi.fn().mockResolvedValue({
    data: {
      form: {
        scope: ['editor', 'matrix', 'entries', 'block-a'],
        refreshable: true,
        nodes: [],
        values: payload.values,
        errors: [],
        globalErrors: [],
      },
      tabs: null,
    },
  });
  vi.stubGlobal('Craft', {
    namespaceId: (id: string, namespace?: string) =>
      namespace ? `${namespace}-${id}` : id,
    sendActionRequest,
  });
  host.dataset.payload = JSON.stringify(payload);
  form.append(host);
  document.body.append(form);
  const editor = {
    settings: {
      elementType: 'Entry',
      elementId: 42,
      siteId: 1,
      updateTabs: vi.fn(),
    },
  };
  vi.stubGlobal('$', () => ({
    data: () => editor,
    serialize: () =>
      new URLSearchParams(
        serializeFormInputsAsObject(form) as Record<string, string>
      ).toString(),
  }));
  await nextTick();

  expect(serializeFormInputsAsObject(form)).toEqual({
    'editor[title]': 'Original',
  });

  host.querySelector<HTMLButtonElement>('[data-nested-refresh]')!.click();
  await vi.advanceTimersByTimeAsync(100);

  const input = host.querySelector<HTMLElement>('craft-input')!;
  (input as CraftInput).modelValue = 'Edited';
  input.dispatchEvent(new CustomEvent('model-value-changed', {bubbles: true}));
  await nextTick();

  expect(serializeFormInputsAsObject(form)).toEqual({
    'editor[title]': 'Edited',
  });
  expect(sendActionRequest).toHaveBeenCalledOnce();
  const options = sendActionRequest.mock.calls[0]![2] as {
    data: string;
    headers: Record<string, string>;
  };
  expect(new URLSearchParams(options.data).get('editor[selectedTab]')).toBe(
    'editor-form-tab-entry-content'
  );
  expect(options.headers).toMatchObject({
    'X-Craft-Namespace': 'editor',
    'X-Craft-Form-Root-Scope': '["editor"]',
    'X-Craft-Form-Scope': '["editor","matrix","entries","block-a"]',
  });
});
