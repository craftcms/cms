import jquery from 'jquery';
import {nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {
  actionClient,
  ConfigService,
  serializeFormInputsAsObject,
} from '@craftcms/ui';
import '../../../../../yii2-adapter/resources/js/native-field-refresh';
import {createCpComponentRegistry} from '@/bootstrap/components';
import {
  defineEntryFieldLayoutUiHost,
  type EntryFieldLayoutUiHost,
} from '../entry-field-layout-ui-host';
import {registerUiComponents} from '../register';
import type {CopiedElementInfo} from '@/modules/matrix/clipboard';
import {fieldId, inputName, valueAt, visitControls} from '../runtime';
import type {UiControlPayload, UiPayload} from '../types';
import {
  stubElementInternals,
  nestedElement,
  standardNestedActions,
  DELETE_ACTION,
} from './nested-index.fixture';

const actions = vi.hoisted(() => ({run: vi.fn()}));
vi.mock('@craftcms/ui/actions.mjs', () => ({runAction: actions.run}));

let restoreInternals = () => {};
let copiedChanged: ((elements: CopiedElementInfo[]) => void) | undefined;
beforeEach(async () => {
  localStorage.clear();
  restoreInternals = stubElementInternals();
  vi.stubGlobal('$', jquery);
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ok: false}));
  vi.stubGlobal('Craft', {
    systemUid: 'test',
    pageTrigger: 'p',
    elementTypeNames: {Entry: ['Entry', 'Entries', 'entry', 'entries']},
    namespaceId: (id: string) => id,
    cp: {
      onCopyElements: vi.fn((callback) => {
        copiedChanged = callback;
      }),
      getCopiedElements: () => [],
    },
  });
  const registry = createCpComponentRegistry();
  registerUiComponents(registry);
  defineEntryFieldLayoutUiHost(registry);
  vi.stubGlobal('Cp', {
    $components: registry,
    registeredAssetBundles: [],
    registeredJsFiles: [],
  });
  ConfigService.getInstance().initialize({
    actionUrl: 'http://localhost/admin/actions',
  });
  vi.resetModules();
  await import('../../../../../packages/craftcms-legacy/cpcompat/src/legacy-html-control.js');
});
afterEach(() => {
  document.body.replaceChildren();
  copiedChanged?.([]);
  localStorage.clear();
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
  vi.useRealTimers();
  actions.run.mockClear();
  restoreInternals();
});

async function mount(
  mode: UiControlPayload['mode'] = 'editable',
  insideBlock = false,
  cards = false,
  minimum = 0,
  captured = false
) {
  const scope = insideBlock
    ? ['editor', 'fields', 'outer', 'entries', 'owner-block']
    : ['editor'];
  const path = [...scope, 'fields', 'blocks'];
  const original = ['fields', 'blocks'];
  const fieldName = inputName(path);
  const text: UiControlPayload = {
    type: 'Text',
    component: 'craft:text',
    props: {inputType: 'text'},
    mode,
    path: [...original, 'entries', 'block-a', 'title'],
    deltaGroup: original,
    reactive: true,
  };
  const control: UiControlPayload = {
    type: 'NestedElementBlocks',
    component: 'craft:nested-element-blocks',
    path: original,
    deltaGroup: original,
    mode,
    nestsUis: true,
    props: {
      entryTypes: [{value: 'text', label: 'Text'}],
      addLabel: 'Add an entry',
      minEntries: minimum,
      ...(minimum
        ? {
            create: {
              fieldId: 9,
              ownerId: 73,
              ownerElementType: 'GlobalSet',
              ownerHasDrafts: false,
              siteId: 1,
              entryTypeIds: {text: 4},
            },
          }
        : {}),
    },
    uis: [
      {
        scope: [...original, 'entries', 'block-a'],
        refreshable: true,
        nodes: [
          {
            type: 'Field',
            component: 'craft:field',
            props: {label: 'Title'},
            control: text,
          },
        ],
      },
    ],
  };
  if (cards) {
    control.uis![0]!.nodes.push({
      type: 'Field',
      component: 'craft:field',
      props: {label: 'Cards'},
      control: {
        type: 'NestedElements',
        component: 'craft:nested-elements',
        mode,
        path: [...original, 'entries', 'block-a', 'fields', 'cards'],
        deltaGroup: original,
        props: JSON.parse(
          JSON.stringify({
            viewMode: 'cards',
            cards: [nestedElement(81)],
            manager: {
              elementType: 'Entry',
              ownerElementType: 'Entry',
              ownerId: 92,
              ownerSiteId: 1,
              ownerHasDrafts: false,
              attribute: 'field:cards',
              fieldId: 9,
              canCreate: false,
              canPaste: false,
              sortable: false,
            },
          })
        ),
      },
    });
  }
  const payload: UiPayload = {
    scope: [],
    refreshable: true,
    errors: [],
    globalErrors: [],
    nodes: [{type: 'Field', component: 'craft:field', props: {}, control}],
    values: {
      fields: {
        blocks: {
          entries: {
            'block-a': {
              type: 'text',
              title: 'Original',
              enabled: true,
              enabledForSite: true,
              collapsed: false,
            },
          },
          sortOrder: ['block-a'],
        },
      },
    },
  };
  const form = document.createElement('form');
  const surrounding = document.createElement('input');
  surrounding.name = 'editor[fields][body]';
  surrounding.value = 'Unsaved owner text';
  form.append(surrounding);
  const host = document.createElement('craft-entry-field-layout-ui');
  host.dataset.payload = JSON.stringify(payload);
  host.dataset.fieldPath = JSON.stringify(original);
  host.dataset.owner = JSON.stringify({
    elementType: insideBlock ? 'Entry' : 'GlobalSet',
    elementId: 73,
    siteId: 1,
  });
  host.innerHTML = `<input type="hidden" name="${fieldName}" disabled data-ui-field-name>`;
  const field = document.createElement('craft-field');
  field.id = fieldId(path);
  const menu = document.createElement('button');
  menu.textContent = 'Collapse all blocks';
  menu.type = 'button';
  const selectionMenu = document.createElement('craft-action-item');
  selectionMenu.hidden = true;
  selectionMenu.textContent = 'Collapse selected blocks';
  selectionMenu.setAttribute(
    'action',
    JSON.stringify({
      type: 'event',
      name: 'craft:matrix-selection-action',
      detail: {action: 'collapse'},
    })
  );
  const legacy = document.createElement(
    'craft-legacy-html-control'
  ) as HTMLElement & {
    values: UiPayload['values'];
    value: unknown;
    control: UiControlPayload;
    ready: Promise<void>;
  };
  const values: UiPayload['values'] = {};
  const legacyControl: UiControlPayload = {
    type: 'LegacyHtmlControl',
    component: 'craft-legacy:html',
    path,
    deltaGroup: path,
    mode,
    props: {
      expandValues: true,
      fragment: {html: host.outerHTML, headHtml: '', bodyHtml: ''},
    },
  };
  if (captured) {
    legacy.values = values;
    legacy.value = [];
    legacy.control = legacyControl;
  }
  field.append(menu, selectionMenu, captured ? legacy : host);
  form.append(field);
  document.body.append(form);
  if (captured) {
    await legacy.ready;
  }
  return {
    form,
    host: captured
      ? field.querySelector<HTMLElement>('craft-entry-field-layout-ui')!
      : host,
    scope,
    path,
    fieldName,
    payload,
    menu,
    selectionMenu,
    legacy,
    legacyControl,
    values,
  };
}

function ownerForm(payload: UiPayload, plugin: boolean): UiPayload {
  const native = structuredClone(payload);
  visitControls(native.nodes, (control) => {
    Object.assign(control, {
      path: ['editor', ...control.path],
      deltaGroup: ['editor', ...control.deltaGroup],
    });
    for (const nested of control.uis ?? []) {
      nested.scope = ['editor', ...nested.scope];
    }
  });
  Object.assign(native, {scope: ['editor']});
  native.values = {editor: native.values};
  if (!plugin) {
    return native;
  }
  const fragment = document.createElement('craft-entry-field-layout-ui');
  fragment.dataset.fieldPath = JSON.stringify(['fields', 'blocks']);
  fragment.dataset.payload = JSON.stringify(payload);
  return {
    ...native,
    nodes: [
      {
        type: 'Field',
        component: 'craft:field',
        props: {},
        control: {
          type: 'LegacyHtmlControl',
          component: 'craft-legacy:html',
          path: ['editor', 'fields', 'blocks'],
          deltaGroup: ['editor', 'fields', 'blocks'],
          mode: 'editable',
          props: {fragment: {html: fragment.outerHTML}},
        },
      },
    ],
  };
}

function blockAction(host: HTMLElement, action: string) {
  window.dispatchEvent(
    new CustomEvent('craft:matrix-block-action', {
      detail: {
        action,
        uid: 'block-a',
        trigger: host.querySelector('[data-matrix-block]'),
      },
    })
  );
}

it.each([
  {insideBlock: false, captured: false},
  {insideBlock: true, captured: false},
  {insideBlock: false, captured: true},
  {insideBlock: true, captured: true},
])(
  'submits inline block edits, state, and removal with the HTML owner, nested=$insideBlock, captured=$captured',
  async ({insideBlock, captured}) => {
    const {form, host, fieldName, menu, selectionMenu} = await mount(
      'editable',
      insideBlock,
      false,
      0,
      captured
    );
    await nextTick();
    expect(serializeFormInputsAsObject(form)).toMatchObject({
      [`${fieldName}[sortOrder][]`]: 'block-a',
      [`${fieldName}[entries][block-a][type]`]: 'text',
      [`${fieldName}[entries][block-a][enabled]`]: '1',
      [`${fieldName}[entries][block-a][title]`]: 'Original',
    });
    expect(selectionMenu.hidden).toBe(true);
    window.dispatchEvent(
      new CustomEvent('craft:matrix-selection-action', {
        detail: {action: 'select', trigger: menu},
      })
    );
    await nextTick();
    expect(selectionMenu.hidden).toBe(false);
    expect(host.querySelector('[role="alert"]')?.textContent ?? '').toBe('');
    const ids = [...form.querySelectorAll('[id]')].map((element) => element.id);
    expect(new Set(ids).size).toBe(ids.length);
    const input = host.querySelector('craft-input')!;
    input.modelValue = 'Unsaved block title';
    input.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    await nextTick();
    blockAction(host, 'disable');
    await nextTick();
    window.dispatchEvent(
      new CustomEvent('craft:matrix-toggle-all', {
        detail: {collapse: false, trigger: menu},
      })
    );
    await nextTick();

    expect(serializeFormInputsAsObject(form)).toMatchObject({
      'editor[fields][body]': 'Unsaved owner text',
      [`${fieldName}[entries][block-a][title]`]: 'Unsaved block title',
      [`${fieldName}[entries][block-a][enabled]`]: '',
      [`${fieldName}[entries][block-a][enabledForSite]`]: '1',
      [`${fieldName}[entries][block-a][collapsed]`]: '',
      [`${fieldName}[sortOrder][]`]: 'block-a',
    });
    expect(
      host
        .querySelector('[data-matrix-block]')
        ?.querySelector('craft-card')
        ?.hasAttribute('collapsed')
    ).toBe(false);
    expect(selectionMenu.textContent).toBe('Collapse selected blocks');

    blockAction(host, 'delete');
    await nextTick();
    expect(host.querySelector('[data-matrix-block]')).toBeNull();
    expect(serializeFormInputsAsObject(form)).toEqual({
      'editor[fields][body]': 'Unsaved owner text',
      [fieldName]: '',
    });
  }
);

it.each([false, true])(
  'retains plugin block edits across captured payload refreshes, nested=%s',
  async (insideBlock) => {
    const {
      form,
      host,
      fieldName,
      path,
      legacy,
      legacyControl,
      values,
      payload,
    } = await mount('editable', insideBlock, false, 0, true);
    const input = host.querySelector('craft-input')!;
    input.modelValue = 'Later unsaved edit';
    input.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    await nextTick();
    await nextTick();
    expect(valueAt(values, [...path, 'entries', 'block-a', 'title'])).toBe(
      'Later unsaved edit'
    );
    blockAction(host, 'disableForSite');
    await nextTick();
    blockAction(host, 'disable');
    await nextTick();
    await nextTick();
    legacy.value = valueAt(values, path);

    const refreshed = structuredClone(payload);
    refreshed.nodes[0]!.control!.uis![0]!.nodes[0]!.props.label =
      'Updated title label';
    const fragment = document.createElement('template');
    fragment.innerHTML = String(
      legacyControl.props.fragment &&
        (legacyControl.props.fragment as {html: string}).html
    );
    fragment.content.querySelector<HTMLElement>(
      'craft-entry-field-layout-ui'
    )!.dataset.payload = JSON.stringify(refreshed);
    legacy.control = {
      ...legacyControl,
      props: {
        ...legacyControl.props,
        fragment: {html: fragment.innerHTML, headHtml: '', bodyHtml: ''},
      },
    };
    await legacy.ready;
    await nextTick();
    expect(form.textContent).toContain('Updated title label');
    const restored = form.querySelector('[data-matrix-block]')!;
    expect(restored.hasAttribute('data-disabled-global')).toBe(true);
    expect(restored.hasAttribute('data-disabled-site')).toBe(true);
    expect(serializeFormInputsAsObject(form)).toMatchObject({
      [`${fieldName}[entries][block-a][title]`]: 'Later unsaved edit',
      [`${fieldName}[sortOrder][]`]: 'block-a',
    });

    blockAction(form.querySelector('craft-entry-field-layout-ui')!, 'delete');
    await nextTick();
    await nextTick();
    legacy.value = valueAt(values, path);
    legacy.control = legacyControl;
    await legacy.ready;
    await nextTick();
    expect(form.querySelector('[data-matrix-block]')).toBeNull();
    expect(serializeFormInputsAsObject(form)[fieldName]).toBe('');
  }
);

function createdBlock(path: string[], uid = 'block-b') {
  return {
    data: {
      uid,
      type: 'text',
      block: {label: 'Text', actions: [], data: {'element-id': 101}},
      form: {
        scope: [...path, 'entries', uid],
        refreshable: false,
        nodes: [
          {
            type: 'Field',
            component: 'craft:field',
            props: {label: 'Title'},
            control: {
              type: 'Text',
              component: 'craft:text',
              props: {},
              mode: 'editable',
              reactive: false,
              path: [...path, 'entries', uid, 'title'],
              deltaGroup: path,
            },
          },
        ],
      },
      values: {
        editor: {
          fields: {
            blocks: {entries: {[uid]: {title: 'Default title'}}},
          },
        },
      },
    },
  };
}

it('retains nested HTML block values and names when its enclosing block moves', async () => {
  const {form, host, fieldName, scope} = await mount('editable', true);
  const input = host.querySelector('craft-input')!;
  input.modelValue = 'Unsaved reordered title';
  input.dispatchEvent(new CustomEvent('model-value-changed', {bubbles: true}));
  await nextTick();

  const field = host.closest('craft-field')!;
  const sibling = document.createElement('div');
  form.append(sibling);
  sibling.after(field);
  await nextTick();

  expect((host as EntryFieldLayoutUiHost).payload?.scope).toEqual(scope);
  expect(serializeFormInputsAsObject(form)).toMatchObject({
    [`${fieldName}[entries][block-a][title]`]: 'Unsaved reordered title',
    [`${fieldName}[sortOrder][]`]: 'block-a',
  });
});

it.each(['add', 'duplicate', 'paste'])(
  'prepares the HTML owner and resolves draft identities before block %s',
  async (action) => {
    const paste = vi.fn(async () => [{id: 202}]);
    Object.assign(Craft.cp!, {
      getCopiedElements: () => [
        {type: 'Entry', id: 101, data: {entryTypeId: 4}},
      ],
      pasteElements: paste,
    });
    const {form, host, path, fieldName} = await mount(
      'editable',
      false,
      false,
      1
    );
    const payload = structuredClone((host as EntryFieldLayoutUiHost).payload!);
    Object.assign(payload.nodes[0]!.control!.props, {
      elementType: 'Entry',
      blocks: {'block-a': {data: {'element-id': 101}}},
    });
    Object.assign(payload.nodes[0]!.control!.props.create!, {
      ownerElementType: 'Entry',
      ownerHasDrafts: true,
    });
    (host as EntryFieldLayoutUiHost).payload = payload;
    await nextTick();
    copiedChanged?.([{type: 'Entry', id: 101, data: {entryTypeId: 4}}]);
    const input = host.querySelector('craft-input')!;
    input.modelValue = 'Pending duplicate source edit';
    input.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    await nextTick();

    let savedTitle: unknown;
    const editor = {
      settings: {canCreateDrafts: true, draftId: null as number | null},
      setFormValue: vi.fn(async () => {
        savedTitle =
          serializeFormInputsAsObject(form)[
            `${fieldName}[entries][block-a][title]`
          ];
        editor.settings.draftId = 4;
      }),
      saveDraft: vi.fn(),
      getDraftElementId: (id: number) => ({73: 173, 101: 201})[id] ?? id,
    };
    jquery(form).data('elementEditor', editor);
    const request = vi
      .spyOn(actionClient, 'post')
      .mockImplementation(async () =>
        action === 'paste'
          ? {data: {blocks: [createdBlock(path).data]}}
          : createdBlock(path)
      );
    window.dispatchEvent(
      new CustomEvent('craft:matrix-block-action', {
        detail: {
          action,
          uid: 'block-a',
          entryType: 'text',
          trigger: host.querySelector('[data-matrix-block]'),
        },
      })
    );
    await vi.waitFor(() =>
      expect(host.querySelectorAll('[data-matrix-block]')).toHaveLength(2)
    );

    expect(savedTitle).toBe('Pending duplicate source edit');
    expect(editor.setFormValue).toHaveBeenCalledWith(fieldName, '*');
    if (action === 'paste') {
      expect(paste).toHaveBeenCalledWith({
        primaryOwnerId: 173,
        ownerId: 173,
        fieldId: 9,
        siteId: 1,
      });
    } else {
      expect(request.mock.calls[0]![1]).toMatchObject({
        ownerId: 173,
        ...(action === 'duplicate' ? {duplicate: 201} : {}),
      });
    }
  }
);

it.each([false, true])(
  'retains newly created plugin block fields after remount, new wrapper=%s',
  async (replaceWrapper) => {
    const request = vi
      .spyOn(actionClient, 'post')
      .mockImplementation(async (_url, data) =>
        createdBlock((data as {path: string[]}).path)
      );
    const {form, host, fieldName, legacy, legacyControl, values, path} =
      await mount('editable', false, false, 1, true);
    window.dispatchEvent(
      new CustomEvent('craft:matrix-block-action', {
        detail: {
          action: 'add',
          uid: 'block-a',
          entryType: 'text',
          trigger: host.querySelector('[data-matrix-block]'),
        },
      })
    );
    await vi.waitFor(() =>
      expect(host.querySelectorAll('[data-matrix-block]')).toHaveLength(2)
    );
    const added = host
      .querySelector('[data-id="block-b"]')!
      .querySelector('craft-input')!;
    added.modelValue = 'Unsaved new block title';
    added.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    await nextTick();
    await nextTick();
    expect(
      serializeFormInputsAsObject(form)[`${fieldName}[entries][block-b][title]`]
    ).toBe('Unsaved new block title');
    const updated = structuredClone((host as EntryFieldLayoutUiHost).payload!);
    Object.assign(updated.nodes[0]!.control!.props, {
      blocks: {'block-b': {actions: [], data: {'element-id': 202}}},
    });
    (host as EntryFieldLayoutUiHost).payload = updated;
    await nextTick();
    expect(
      host.querySelector('[data-id="block-b"]')!.getAttribute('data-element-id')
    ).toBe('202');
    request.mockImplementation(async (_url, data) =>
      createdBlock((data as {path: string[]}).path, 'block-c')
    );
    window.dispatchEvent(
      new CustomEvent('craft:matrix-block-action', {
        detail: {
          action: 'add',
          uid: 'block-a',
          entryType: 'text',
          trigger: host.querySelector('[data-matrix-block]'),
        },
      })
    );
    await vi.waitFor(() =>
      expect(host.querySelectorAll('[data-matrix-block]')).toHaveLength(3)
    );
    expect(
      host.querySelector('[data-id="block-b"]')!.getAttribute('data-element-id')
    ).toBe('202');
    const target = replaceWrapper
      ? (document.createElement('craft-legacy-html-control') as typeof legacy)
      : legacy;
    target.values = values;
    target.value = valueAt(values, path);
    target.control = {
      ...legacyControl,
      props: {
        ...legacyControl.props,
        fragment: {
          ...(legacyControl.props.fragment as {
            html: string;
            headHtml: string;
            bodyHtml: string;
          }),
          html:
            (legacyControl.props.fragment as {html: string}).html +
            '<span data-remounted></span>',
        },
      },
    };
    if (replaceWrapper) {
      legacy.replaceWith(target);
    }
    await target.ready;
    await nextTick();
    expect(serializeFormInputsAsObject(form)).toMatchObject({
      [`${fieldName}[entries][block-b][title]`]: 'Unsaved new block title',
      [`${fieldName}[sortOrder][]`]: ['block-b', 'block-c', 'block-a'],
    });
    expect(form.querySelector('[data-id="block-b"] craft-spinner')).toBeNull();
  }
);

it('submits automatically added minimum blocks with their server defaults', async () => {
  const scroll = vi.spyOn(HTMLElement.prototype, 'scrollIntoView');
  const request = vi
    .spyOn(actionClient, 'post')
    .mockImplementation(async (_url, data) => {
      const path = (data as {path: string[]}).path;
      return createdBlock(path);
    });
  const {form, host, fieldName} = await mount('editable', false, false, 2);
  await vi.waitFor(() =>
    expect(host.querySelectorAll('[data-matrix-block]')).toHaveLength(2)
  );
  expect(serializeFormInputsAsObject(form)).toMatchObject({
    [`${fieldName}[entries][block-b][title]`]: 'Default title',
    [`${fieldName}[entries][block-b][enabled]`]: '1',
    [`${fieldName}[sortOrder][]`]: ['block-a', 'block-b'],
  });
  expect(request).toHaveBeenCalledOnce();
  expect(scroll).not.toHaveBeenCalled();
});

it('stops creating minimum blocks when the HTML owner form is removed', async () => {
  let finish!: (value: {data: unknown}) => void;
  const pending = new Promise<{data: unknown}>((resolve) => {
    finish = resolve;
  });
  const request = vi
    .spyOn(actionClient, 'post')
    .mockImplementationOnce(() => pending)
    .mockRejectedValue(new Error('Unexpected block creation'));
  const {form, path} = await mount('editable', false, false, 3);
  await vi.waitFor(() => expect(request).toHaveBeenCalledOnce());
  form.remove();
  finish({
    data: {
      uid: 'block-b',
      type: 'text',
      block: {actions: []},
      values: {},
      form: {
        scope: [...path, 'entries', 'block-b'],
        nodes: [],
        refreshable: false,
      },
    },
  });
  await new Promise((resolve) => setTimeout(resolve, 0));
  expect(request).toHaveBeenCalledOnce();
});

it.each(['readOnly', 'disabled'] as const)(
  'shows %s blocks without submitting their values',
  async (mode) => {
    const request = vi.spyOn(actionClient, 'post');
    const {form, host} = await mount(mode, false, false, 2);
    await nextTick();
    expect(host.querySelectorAll('[data-matrix-block]')).toHaveLength(1);
    expect(host.querySelector('craft-input')?.modelValue).toBe('Original');
    expect(Object.fromEntries(new FormData(form))).toEqual({
      'editor[fields][body]': 'Unsaved owner text',
    });
    expect(host.querySelector('craft-button')).toBeNull();
    expect(request).not.toHaveBeenCalled();
  }
);

it.each([false, true])(
  'refreshes a conditional block field without replacing the surrounding owner form, plugin=%s',
  async (plugin) => {
    vi.useFakeTimers();
    const {form, host, scope, fieldName, payload, values, path} = await mount(
      'editable',
      false,
      false,
      0,
      plugin
    );
    const refreshed = structuredClone(payload);
    refreshed.nodes[0]!.control!.uis![0]!.nodes[0]!.props.label =
      'Refreshed title';
    (
      refreshed.values.fields as {
        blocks: {entries: Record<string, {title: string}>};
      }
    ).blocks.entries['block-a']!.title = 'Server response title';
    vi.mocked(fetch).mockImplementation(
      async () =>
        new Response(
          JSON.stringify({
            form: ownerForm(refreshed, plugin),
            headHtml: '',
            bodyHtml: '',
          })
        )
    );
    const request = vi.spyOn(actionClient, 'post');
    await nextTick();
    expect(host.querySelector('[role="alert"]')?.textContent ?? '').toBe('');
    const input = host.querySelector('craft-input')!;
    input.modelValue = 'Unsaved block title';
    input.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    await vi.advanceTimersByTimeAsync(1000);
    expect(request).toHaveBeenCalledOnce();
    const data = new URLSearchParams(String(request.mock.calls[0]![1]));
    expect(data.get('editor[elementId]')).toBe('73');
    expect(data.get(`${fieldName}[entries][block-a][title]`)).toBe(
      'Unsaved block title'
    );
    expect(request.mock.calls[0]![2]?.headers).toMatchObject({
      'X-Craft-Ui-Root-Scope': JSON.stringify(scope),
      'X-Craft-Ui-Scope': JSON.stringify(scope),
    });
    expect(
      form.querySelector('input[name="editor[fields][body]"]')
    ).not.toBeNull();
    expect(host.querySelector('craft-input')?.modelValue).toBe(
      'Unsaved block title'
    );
    await vi.waitFor(() =>
      expect(host.textContent).toContain('Refreshed title')
    );
    if (plugin) {
      form.dispatchEvent(
        new Event('submit', {bubbles: true, cancelable: true})
      );
      expect(valueAt(values, [...path, 'entries', 'block-a', 'title'])).toBe(
        'Unsaved block title'
      );
    }
    vi.useRealTimers();
  }
);

it.each([false, true])(
  'refreshes nested cards while preserving the inline block and HTML owner edits, plugin=%s',
  async (plugin) => {
    const {form, host, payload, scope, fieldName} = await mount(
      'editable',
      false,
      true
    );
    const refreshed = structuredClone(payload);
    refreshed.nodes[0]!.control!.uis![0]!.nodes[1]!.control!.props.cards = [];
    const response = ownerForm(refreshed, plugin);
    response.nodes.push({
      type: 'Field',
      component: 'craft:field',
      props: {label: 'Unrelated owner field'},
      control: {
        type: 'Text',
        component: 'craft:text',
        props: {},
        path: ['editor', 'other'],
        deltaGroup: ['editor', 'other'],
        mode: 'editable',
      },
    });
    response.nodes[0]!.children = [
      {
        type: 'TemplateContent',
        component: 'craft:template-content',
        props: {html: 'Outer field action'},
      },
    ];
    vi.mocked(fetch).mockImplementation(
      async () =>
        new Response(
          JSON.stringify({form: response, headHtml: '', bodyHtml: ''})
        )
    );
    const request = vi.spyOn(actionClient, 'post');
    await nextTick();
    expect(host.querySelector('[role="alert"]')?.textContent ?? '').toBe('');
    const input = host.querySelector('craft-input')!;
    input.modelValue = 'Unsaved block title';
    input.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    await nextTick();
    const trigger = host.querySelector<HTMLElement>('[data-nested-id="81"]')!;
    trigger.dispatchEvent(
      new CustomEvent('craft:nested-element-action', {
        bubbles: true,
        detail: {
          action: 'element-action',
          elementId: 81,
          trigger,
          item: standardNestedActions.find(
            (item) => item.key === DELETE_ACTION
          ),
        },
      })
    );
    await vi.waitFor(() =>
      expect(host.querySelector('[data-nested-id="81"]')).toBeNull()
    );
    expect(host.querySelector('[role="alert"]')).toBeNull();
    expect(host.textContent).not.toContain('Unrelated owner field');
    expect(host.textContent).not.toContain('Outer field action');
    expect(host.querySelector('craft-input')?.modelValue).toBe(
      'Unsaved block title'
    );
    expect(serializeFormInputsAsObject(form)).toMatchObject({
      'editor[fields][body]': 'Unsaved owner text',
      [`${fieldName}[entries][block-a][title]`]: 'Unsaved block title',
    });
    const data = new URLSearchParams(String(request.mock.calls[0]![1]));
    expect(data.get('editor[elementId]')).toBe('73');
    expect(data.get('editor[fields][body]')).toBe('Unsaved owner text');
    expect(request.mock.calls[0]![2]?.headers).toMatchObject({
      'X-Craft-Ui-Root-Scope': JSON.stringify(scope),
    });
  }
);

it('preserves plugin wrappers in native editor refresh responses', async () => {
  const {payload} = await mount();
  const form = ownerForm(payload, true);
  const fragment = form.nodes[0]!.control!.props.fragment as {html: string};
  fragment.html =
    '<button data-plugin-command>Plugin command</button>' + fragment.html;
  vi.mocked(fetch).mockImplementation(
    async () => new Response(JSON.stringify({form}))
  );
  const response = await actionClient.post<{form: UiPayload}>(
    '/refresh',
    {elementId: 73},
    {
      headers: {'X-Craft-Ui-Root-Scope': JSON.stringify(['editor'])},
    }
  );
  expect(response.data.form.nodes[0]!.control!.component).toBe(
    'craft-legacy:html'
  );
  const retained = response.data.form.nodes[0]!.control!.props.fragment as {
    html: string;
  };
  const template = document.createElement('template');
  template.innerHTML = retained.html;
  expect(
    template.content.querySelector('[data-plugin-command]')?.textContent
  ).toBe('Plugin command');
  expect(
    template.content.querySelector('craft-entry-field-layout-ui')
  ).not.toBeNull();
});

it('decodes the requested HTML field without removing descendant plugin wrappers', async () => {
  const {payload, path} = await mount();
  const inner = ['fields', 'blocks', 'entries', 'block-a', 'fields', 'inner'];
  const native = structuredClone(payload);
  const nestedControl = structuredClone(native.nodes[0]!.control!);
  Object.assign(nestedControl, {
    path: inner,
    deltaGroup: ['fields', 'blocks'],
    uis: [],
  });
  const fragment = document.createElement('craft-entry-field-layout-ui');
  fragment.dataset.fieldPath = JSON.stringify(inner);
  fragment.dataset.payload = JSON.stringify({
    ...native,
    nodes: [{...native.nodes[0], control: nestedControl}],
  });
  native.nodes[0]!.control!.uis![0]!.nodes.push({
    type: 'Field',
    component: 'craft:field',
    props: {label: 'Plugin inner'},
    control: {
      type: 'LegacyHtmlControl',
      component: 'craft-legacy:html',
      path: inner,
      deltaGroup: ['fields', 'blocks'],
      mode: 'editable',
      props: {
        fragment: {
          html:
            '<button data-inner-plugin>Inner plugin command</button>' +
            fragment.outerHTML,
          headHtml: '',
          bodyHtml: '',
        },
      },
    },
  });
  vi.mocked(fetch).mockImplementation(
    async () => new Response(JSON.stringify({form: ownerForm(native, true)}))
  );
  const response = await actionClient.post<{form: UiPayload}>(
    '/refresh',
    'editor[elementId]=73',
    {
      headers: {
        'X-Craft-Ui-Root-Scope': JSON.stringify(['editor']),
        'X-Craft-Native-Field-Path': JSON.stringify(path),
      },
    }
  );
  const outer = response.data.form.nodes[0]!.control!;
  expect(outer.component).toBe('craft:nested-element-blocks');
  const retained = outer.uis![0]!.nodes[1]!.control!;
  expect(retained.component).toBe('craft-legacy:html');
  const template = document.createElement('template');
  template.innerHTML = (retained.props.fragment as {html: string}).html;
  expect(
    template.content.querySelector('[data-inner-plugin]')?.textContent
  ).toBe('Inner plugin command');
});
