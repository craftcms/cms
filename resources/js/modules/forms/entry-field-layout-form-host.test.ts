import {defineComponent, h, inject, nextTick} from 'vue';
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
import {
  NestedOwnerEditorKey,
  type NestedOwnerContext,
} from '@/modules/elements/nested-owner';

function deferred<T>() {
  let resolve!: (value: T) => void;
  const promise = new Promise<T>((resolvePromise) => {
    resolve = resolvePromise;
  });

  return {promise, resolve};
}

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
  let prepareOwner: (() => Promise<NestedOwnerContext | null>) | undefined;
  let prepareOwnerAt:
    | ((path: string[]) => Promise<NestedOwnerContext | null>)
    | undefined;
  let refreshOwner: (() => Promise<void>) | undefined;
  let nestedRefreshScope = ['editor', 'matrix', 'entries', 'block-a'];
  components.register(
    'test:owner-prepare',
    defineComponent({
      setup: () => {
        const owner = inject(NestedOwnerEditorKey);
        prepareOwnerAt = (path) => owner!.prepare(path);
        prepareOwner = () => prepareOwnerAt!(['editor', 'cards']);
        refreshOwner = () => owner!.refresh!();
        return () => h('span', 'Nested owner');
      },
    })
  );
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
                  scope: nestedRefreshScope,
                  refreshable: true,
                }),
            },
            'Refresh nested form'
          ),
    })
  );
  components.register(
    'test:nested-refresh-b',
    defineComponent({
      emits: ['change'],
      setup:
        (_, {emit}) =>
        () =>
          h(
            'button',
            {
              type: 'button',
              'data-nested-refresh-b': '',
              onClick: () =>
                emit('change', {
                  kind: 'discrete',
                  path: ['editor', 'matrix'],
                  scope: ['editor', 'matrix', 'entries', 'block-b'],
                  refreshable: true,
                }),
            },
            'Refresh second nested form'
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
            component: 'test:owner-prepare',
            props: {},
            control: {
              type: 'NestedElements',
              component: 'craft:nested-elements',
              mode: 'editable',
              path: ['editor', 'cards'],
              deltaGroup: ['editor', 'cards'],
              props: {
                manager: {
                  ownerId: 42,
                  ownerIsDerivative: false,
                  ownerIsInDerivativeTree: false,
                },
              },
            },
          },
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
                {
                  scope: ['editor', 'matrix', 'entries', 'block-c'],
                  refreshable: true,
                  nodes: [],
                },
                {
                  scope: ['editor', 'matrix', 'entries', 'block-d'],
                  refreshable: true,
                  nodes: [],
                },
              ],
            },
          },
          {
            type: 'Nested',
            component: 'test:nested-refresh-b',
            props: {},
            control: {
              type: 'Nested',
              component: 'test:nested-refresh-b',
              props: {},
              path: ['editor', 'matrix'],
              mode: 'editable',
              deltaGroup: ['editor', 'matrix'],
              forms: [
                {
                  scope: ['editor', 'matrix', 'entries', 'block-b'],
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
        matrix: {
          entries: {
            'block-a': {},
            'block-b': {},
            'block-c': {},
            'block-d': {},
          },
          sortOrder: ['block-a', 'block-b', 'block-c', 'block-d'],
        },
      },
    },
    errors: [],
    globalErrors: [],
  } satisfies FormPayload;
  const actionRequest = vi
    .spyOn(craftUi.actionClient, 'post')
    .mockImplementation(async (_action, _data, options) => ({
      data: {
        form:
          options?.headers?.['X-Craft-Form-Scope'] === '["editor"]'
            ? payload
            : {
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
    }));
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
      draftId: null as number | null,
      revisionId: null,
      fieldId: null,
      ownerId: null,
      siteId: 1,
      isProvisionalDraft: false,
      canCreateDrafts: true,
      updateTabs: vi.fn(),
    },
    saveDraft: vi.fn(async () => {
      editor.settings.draftId = 4;
    }),
    getDraftElementId: vi.fn((id: number) => (id === 42 ? 91 : id)),
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

  expect(await prepareOwner?.()).toMatchObject({
    ownerId: 91,
    ownerIsDerivative: true,
    ownerIsInDerivativeTree: true,
    requiresDerivative: true,
  });
  expect(editor.saveDraft).toHaveBeenCalledOnce();

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

  await refreshOwner?.();
  await nextTick();

  expect(craftUi.serializeFormInputsAsObject(form)).toEqual({
    'editor[title]': 'Edited',
  });
  expect(actionRequest).toHaveBeenCalledTimes(2);
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

  const basePayload = host.payload;
  if (!basePayload) throw new Error('Expected the host payload.');

  const refreshedPayload = (title: string): FormPayload => ({
    ...basePayload,
    values: {
      editor: {
        title,
        matrix: {entries: {'block-a': {}}, sortOrder: ['block-a']},
      },
    },
  });
  const refreshResponse = (title: string) => ({
    data: {
      form: refreshedPayload(title),
      headHtml: '',
      bodyHtml: '',
    },
  });
  const firstRefresh = deferred<ReturnType<typeof refreshResponse>>();
  const secondRefresh = deferred<ReturnType<typeof refreshResponse>>();
  actionRequest
    .mockImplementationOnce(() => firstRefresh.promise)
    .mockImplementationOnce(() => secondRefresh.promise);

  const firstRefreshPromise = refreshOwner!();
  const secondRefreshPromise = refreshOwner!();
  secondRefresh.resolve(refreshResponse('Latest'));
  await secondRefreshPromise;
  firstRefresh.resolve(refreshResponse('Stale'));
  await firstRefreshPromise;

  expect(host.payload?.values).toMatchObject({editor: {title: 'Latest'}});

  const staleRefresh = deferred<ReturnType<typeof refreshResponse>>();
  actionRequest.mockImplementationOnce(() => staleRefresh.promise);
  const staleRefreshPromise = refreshOwner!();
  host.payload = refreshedPayload('Authoritative');
  staleRefresh.resolve(refreshResponse('Obsolete'));
  await staleRefreshPromise;

  expect(host.payload?.values).toMatchObject({
    editor: {title: 'Authoritative'},
  });

  const firstScope = deferred<ReturnType<typeof refreshResponse>>();
  const secondScope = deferred<ReturnType<typeof refreshResponse>>();
  actionRequest
    .mockImplementationOnce(() => firstScope.promise)
    .mockImplementationOnce(() => secondScope.promise);
  appendBodyHtmlSpy.mockClear();

  nestedRefreshScope = ['editor', 'matrix', 'entries', 'block-c'];
  host.querySelector<HTMLButtonElement>('[data-nested-refresh]')!.click();
  host.querySelector<HTMLButtonElement>('[data-nested-refresh-b]')!.click();
  expect(
    actionRequest.mock.calls
      .slice(-2)
      .map((call) => call[2]?.headers?.['X-Craft-Form-Scope'])
  ).toEqual([
    '["editor","matrix","entries","block-c"]',
    '["editor","matrix","entries","block-b"]',
  ]);
  secondScope.resolve({
    data: {
      form: {
        ...refreshedPayload('Authoritative'),
        scope: ['editor', 'matrix', 'entries', 'block-b'],
      },
      headHtml: '',
      bodyHtml: '<script>block-b</script>',
    },
  });
  await vi.advanceTimersByTimeAsync(0);
  firstScope.resolve({
    data: {
      form: {
        ...refreshedPayload('Authoritative'),
        scope: ['editor', 'matrix', 'entries', 'block-c'],
        nodes: [
          {
            type: 'Conditional cards',
            component: 'test:owner-prepare',
            props: {},
            control: {
              type: 'NestedElements',
              component: 'craft:nested-elements',
              mode: 'editable',
              path: ['editor', 'matrix', 'entries', 'block-c', 'cards'],
              deltaGroup: ['editor', 'matrix', 'entries', 'block-c', 'cards'],
              props: {
                manager: {
                  ownerId: 42,
                  ownerIsDerivative: false,
                  ownerIsInDerivativeTree: false,
                },
              },
            },
          },
        ],
      },
      headHtml: '',
      bodyHtml: '<script>block-c</script>',
    },
  });
  await vi.advanceTimersByTimeAsync(0);
  await nextTick();

  expect(appendBodyHtmlSpy).toHaveBeenCalledWith('<script>block-c</script>');
  expect(appendBodyHtmlSpy).toHaveBeenCalledWith('<script>block-b</script>');
  expect(
    await prepareOwnerAt?.(['editor', 'matrix', 'entries', 'block-c', 'cards'])
  ).toMatchObject({
    ownerId: 91,
    ownerIsDerivative: true,
    ownerIsInDerivativeTree: true,
  });

  const olderRoot = deferred<ReturnType<typeof refreshResponse>>();
  const newerDescendant = deferred<ReturnType<typeof refreshResponse>>();
  actionRequest
    .mockImplementationOnce(() => olderRoot.promise)
    .mockImplementationOnce(() => newerDescendant.promise);

  const olderRootPromise = refreshOwner!();
  nestedRefreshScope = ['editor', 'matrix', 'entries', 'block-d'];
  host.querySelector<HTMLButtonElement>('[data-nested-refresh]')!.click();
  newerDescendant.resolve({
    data: {
      form: {
        ...refreshedPayload('Newer descendant'),
        scope: ['editor', 'matrix', 'entries', 'block-d'],
        nodes: [],
      },
      headHtml: '',
      bodyHtml: '<script>newer-descendant</script>',
    },
  });
  await vi.advanceTimersByTimeAsync(0);
  expect(appendBodyHtmlSpy).toHaveBeenCalledWith(
    '<script>newer-descendant</script>'
  );

  olderRoot.resolve({
    data: {
      form: refreshedPayload('Older root'),
      headHtml: '',
      bodyHtml: '<script>older-root</script>',
    },
  });
  await olderRootPromise;

  expect(appendBodyHtmlSpy).not.toHaveBeenCalledWith(
    '<script>older-root</script>'
  );
});
