import {defineComponent, h, nextTick} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FieldNode from './FieldNode.vue';
import TextControl from './TextControl.vue';
import TabNode from './TabNode.vue';
import * as craftUi from '@craftcms/ui';
import {
  defineEntryFieldLayoutFormHost,
  type EntryFieldLayoutFormHost,
} from './entry-field-layout-form-host';
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
  // SAFETY: The definition above registers the exported host API for this tag.
  const host = document.createElement(
    'craft-entry-field-layout-form'
  ) as EntryFieldLayoutFormHost;
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
  const actionRequest = vi
    .spyOn(craftUi.actionClient, 'post')
    .mockResolvedValue({
      data: {
        form: {
          scope: ['editor', 'matrix', 'entries', 'block-a'],
          refreshable: true,
          nodes: [],
          values: payload.values,
          errors: [],
          globalErrors: [],
        },
        headHtml: '<style>nested</style>',
        bodyHtml: '<script>nested</script>',
      },
    });
  const disposeAppendedHtml = vi.fn();
  const appendHeadHtmlSpy = vi
    .spyOn(craftUi, 'appendHeadHtml')
    .mockResolvedValue(disposeAppendedHtml);
  const appendBodyHtmlSpy = vi
    .spyOn(craftUi, 'appendBodyHtml')
    .mockResolvedValue(disposeAppendedHtml);
  vi.stubGlobal('Craft', {
    namespaceId: (id: string, namespace?: string) =>
      namespace ? `${namespace}-${id}` : id,
  });
  host.requestMetadata = () => ({
    elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
    elementId: null,
    elementUid: 'block-a',
    fieldId: 7,
    ownerId: 42,
    typeId: 9,
    sortOrder: 2,
  });
  host.dataset.payload = JSON.stringify(payload);
  form.append(host);
  document.body.append(form);
  const editor = {
    settings: {
      elementType: 'Entry',
      elementId: 42,
      canonicalId: null,
      draftId: null,
      revisionId: null,
      fieldId: null,
      ownerId: null,
      siteId: 1,
      isProvisionalDraft: false,
      updateTabs: vi.fn(),
    },
  };
  vi.stubGlobal('$', () => ({
    data: () => editor,
    serialize: () =>
      new URLSearchParams(
        Object.entries(craftUi.serializeFormInputsAsObject(form)).map(
          ([key, value]) => [key, String(value)]
        )
      ).toString(),
  }));
  await nextTick();

  expect(craftUi.serializeFormInputsAsObject(form)).toEqual({
    'editor[title]': 'Original',
  });

  const refreshButton = host.querySelector<HTMLButtonElement>(
    '[data-nested-refresh]'
  );
  if (!refreshButton) throw new Error('Expected the nested refresh button.');
  refreshButton.click();
  await vi.advanceTimersByTimeAsync(100);

  const input = host.querySelector('craft-input');
  if (!input) throw new Error('Expected the title input.');
  input.modelValue = 'Edited';
  input.dispatchEvent(new CustomEvent('model-value-changed', {bubbles: true}));
  await nextTick();

  expect(craftUi.serializeFormInputsAsObject(form)).toEqual({
    'editor[title]': 'Edited',
  });
  expect(actionRequest).toHaveBeenCalledOnce();
  const requestCall = actionRequest.mock.calls[0];
  if (!requestCall) throw new Error('Expected the nested refresh request.');
  const [, data, options] = requestCall;
  if (Object(data).constructor !== String)
    throw new Error('Expected URL-encoded refresh data.');
  if (!options) throw new Error('Expected nested refresh request options.');
  const encodedData = String(data);
  expect(new URLSearchParams(encodedData).get('editor[selectedTab]')).toBe(
    'editor-form-tab-entry-content'
  );
  expect(Object.fromEntries(new URLSearchParams(encodedData))).toMatchObject({
    'editor[elementType]': 'CraftCms\\Cms\\Entry\\Elements\\Entry',
    'editor[elementUid]': 'block-a',
    'editor[fieldId]': '7',
    'editor[ownerId]': '42',
    'editor[typeId]': '9',
    'editor[sortOrder]': '2',
  });
  expect(new URLSearchParams(encodedData).has('editor[elementId]')).toBe(false);
  expect(options.headers).toMatchObject({
    'X-Craft-Namespace': 'editor',
    'X-Craft-Form-Root-Scope': '["editor"]',
    'X-Craft-Form-Scope': '["editor","matrix","entries","block-a"]',
  });
  expect(appendHeadHtmlSpy).toHaveBeenCalledWith('<style>nested</style>');
  expect(appendBodyHtmlSpy).toHaveBeenCalledWith('<script>nested</script>');
});
