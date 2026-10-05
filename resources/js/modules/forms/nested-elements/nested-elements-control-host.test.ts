import {nextTick} from 'vue';
import jquery from 'jquery';
import {
  afterEach,
  beforeEach,
  expect,
  it,
  vi,
  type MockInstance,
} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import {defineNestedElementsControlHost} from './nested-elements-control-host';
import type {FormControlPayload} from '../types';
import type {NestedElementsProps} from './nested-elements';
import {inputName} from '../runtime';
import {
  DELETE_ACTION,
  button,
  nestedElement,
  nestedIndexPayload,
  standardNestedActions,
  stubElementInternals,
} from './nested-index.fixture';

import {actionClient, ConfigService} from '@craftcms/ui';
import '../../../../../yii2-adapter/resources/js/native-field-refresh';
let requests: {post: MockInstance<typeof actionClient.post>};
const actions = vi.hoisted(() => ({run: vi.fn()}));
vi.mock('@craftcms/ui/actions.mjs', () => ({runAction: actions.run}));

let restoreInternals = () => {};
beforeEach(() => {
  requests = {post: vi.spyOn(actionClient, 'post')};
  vi.stubGlobal('Cp', {registeredAssetBundles: [], registeredJsFiles: []});
  ConfigService.getInstance().initialize({
    actionUrl: 'http://localhost/admin/actions',
  });
  restoreInternals = stubElementInternals();
  vi.stubGlobal('$', jquery);
  vi.stubGlobal('Craft', {
    systemUid: 'test',
    pageTrigger: 'p',
    elementTypeNames: {Entry: ['Entry', 'Entries', 'entry', 'entries']},
    cp: {onCopyElements: vi.fn(), getCopiedElements: () => []},
  });
  vi.stubGlobal(
    'fetch',
    vi.fn(async () => new Response('<svg></svg>'))
  );
  defineNestedElementsControlHost(createCpComponentRegistry());
});
afterEach(() => {
  vi.useRealTimers();
  document.body.replaceChildren();
  requests.post.mockReset();
  actions.run.mockClear();
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
  restoreInternals();
});

function mount({
  drafts = false,
  mode = 'editable',
  index = false,
  insideBlock = false,
  relative = false,
} = {}) {
  const scope = insideBlock ? ['fields', 'blocks', 'entries', 'block-a'] : [];
  const control: FormControlPayload<NestedElementsProps> = {
    type: 'NestedElements',
    component: 'craft:nested-elements',
    mode: mode as 'editable' | 'readOnly',
    path: [...scope, 'fields', 'cards'],
    deltaGroup: insideBlock ? ['fields', 'blocks'] : ['fields', 'cards'],
    props: {
      viewMode: index ? 'index' : 'cards',
      ...(index
        ? {index: {initial: nestedIndexPayload([nestedElement(81)])}}
        : {}),
      cards: [nestedElement(81)],
      manager: {
        elementType: 'Entry',
        ownerElementType: insideBlock ? 'Entry' : 'GlobalSet',
        ownerId: insideBlock ? 92 : 73,
        ownerSiteId: 1,
        ownerHasDrafts: drafts,
        attribute: 'field:cards',
        fieldId: 9,
        canCreate: true,
        canPaste: false,
        sortable: true,
        createAttributes: [{label: 'Entry', attributes: {typeId: 9}}],
      },
    },
  };
  const form = document.createElement('form');
  const text = document.createElement('input');
  text.name = 'slideout[fields][text]';
  text.value = 'Unsaved edit';
  form.append(text);
  if (insideBlock) {
    const heading = document.createElement('input');
    heading.name = 'slideout[fields][blocks][entries][block-a][title]';
    heading.value = 'Unsaved block title';
    form.append(heading);
  }
  const host = document.createElement('craft-nested-elements-control');
  host.dataset.control = JSON.stringify(control);
  host.dataset.scope = JSON.stringify(scope);
  host.innerHTML =
    (mode === 'editable'
      ? `<input type="hidden" name="${relative ? 'cards' : inputName(['slideout', ...control.path])}" value="*" disabled data-nested-modified>`
      : '') + '<div data-nested-mount></div>';
  form.append(host);
  document.body.append(form);
  return {form, host, text, control};
}

async function deleteCard(host: HTMLElement) {
  await nextTick();
  const trigger = host.querySelector<HTMLElement>('[data-nested-id="81"]')!;
  trigger.dispatchEvent(
    new CustomEvent('craft:nested-element-action', {
      bubbles: true,
      detail: {
        action: 'element-action',
        elementId: 81,
        trigger,
        item: standardNestedActions.find((item) => item.key === DELETE_ACTION),
      },
    })
  );
}

it.each([
  {
    location: 'direct field',
    insideBlock: false,
    legacy: false,
    relative: false,
  },
  {
    location: 'field inside a Matrix block',
    insideBlock: true,
    legacy: false,
    relative: false,
  },
  {
    location: 'field with a relative legacy input name',
    insideBlock: false,
    legacy: false,
    relative: true,
  },
  {
    location: 'plugin field inside a Matrix block',
    insideBlock: true,
    legacy: true,
    relative: false,
  },
])(
  'refreshes a $location after deletion without losing unsaved HTML form values',
  async ({insideBlock, legacy, relative}) => {
    const {form, host, text, control} = mount({insideBlock, relative});
    const namespace = relative
      ? ''
      : insideBlock
        ? 'slideout[fields][blocks][entries][block-a]'
        : 'slideout';
    const fieldName = relative ? 'cards' : `${namespace}[fields][cards]`;
    const refreshed = {
      ...control,
      path: relative ? control.path : ['slideout', ...control.path],
      props: {...control.props, cards: []},
    };
    const fragment = document.createElement('craft-nested-elements-control');
    fragment.dataset.control = JSON.stringify({
      ...refreshed,
      path: ['fields', 'cards'],
    });
    vi.mocked(fetch).mockImplementation(
      async () =>
        new Response(
          JSON.stringify({
            form: {
              scope: [],
              values: {},
              errors: [],
              globalErrors: [],
              refreshable: true,
              nodes: [
                {
                  type: 'Field',
                  component: 'craft:field',
                  props: {},
                  control: legacy
                    ? {
                        ...refreshed,
                        component: 'craft-legacy:html',
                        props: {
                          fragment: {
                            html: fragment.outerHTML,
                            headHtml: '',
                            bodyHtml: '',
                          },
                        },
                      }
                    : refreshed,
                },
              ],
            },
          })
        )
    );
    expect(new FormData(form).has(fieldName)).toBe(false);
    expect(host.textContent).toContain('Entry 81');

    await deleteCard(host);
    await vi.waitFor(() => expect(host.textContent).toContain('Nothing yet.'));

    expect(host.querySelector('[role="alert"]')).toBeNull();
    expect(new FormData(form).get(fieldName)).toBe('*');
    expect(text.value).toBe('Unsaved edit');
    const body = new URLSearchParams(String(requests.post.mock.calls[0]![1]));
    expect(body.get(fieldName)).toBe('*');
    expect(body.get(namespace ? `${namespace}[elementId]` : 'elementId')).toBe(
      insideBlock ? '92' : '73'
    );
    expect(
      body.get(namespace ? `${namespace}[elementType]` : 'elementType')
    ).toBe(insideBlock ? 'Entry' : 'GlobalSet');
    expect(body.get('slideout[fields][text]')).toBe('Unsaved edit');
    if (insideBlock) {
      expect(body.get(`${namespace}[title]`)).toBe('Unsaved block title');
      expect(new FormData(form).get(`${namespace}[title]`)).toBe(
        'Unsaved block title'
      );
    }
    expect(requests.post.mock.calls[0]![2]?.headers).toMatchObject({
      'X-Craft-Namespace': namespace || undefined,
      'X-Craft-Form-Root-Scope': relative
        ? '[]'
        : insideBlock
          ? '["slideout","fields","blocks","entries","block-a"]'
          : '["slideout"]',
    });
  }
);

it('blocks changes when a draft owner has no element editor', async () => {
  const {host} = mount({drafts: true});
  await deleteCard(host);
  await vi.waitFor(() =>
    expect(host.querySelector('[role="alert"]')?.textContent).toContain(
      'Could not save the owner draft'
    )
  );
  expect(actions.run).not.toHaveBeenCalled();
  expect(requests.post).not.toHaveBeenCalled();
});

it('prepares the owner draft before changing its nested elements', async () => {
  const {host, form} = mount({drafts: true});
  let finishDraft!: () => void;
  const draftSaved = new Promise<void>((resolve) => {
    finishDraft = resolve;
  });
  const editor = {
    settings: {canCreateDrafts: true, draftId: null as number | null},
    setFormValue: vi.fn(async () => {
      await draftSaved;
      editor.settings.draftId = 44;
    }),
    getDraftElementId: () => 94,
  };
  $(form).data('elementEditor', editor);
  requests.post.mockRejectedValue(new Error('Offline'));
  await deleteCard(host);
  await vi.waitFor(() =>
    expect(editor.setFormValue).toHaveBeenCalledExactlyOnceWith(
      'slideout[fields][cards]',
      '*'
    )
  );
  expect(actions.run).not.toHaveBeenCalled();
  expect(new FormData(form).get('slideout[fields][cards]')).toBe('*');
  finishDraft();
  await vi.waitFor(() => expect(actions.run).toHaveBeenCalled());
  expect(actions.run.mock.calls[0]![0]).toMatchObject({body: {ownerId: 94}});
  await vi.waitFor(() =>
    expect(host.querySelector('[role="alert"]')?.textContent).toContain(
      'Offline'
    )
  );
});

it('stops handling nested actions when its surrounding HTML form is removed', async () => {
  vi.useFakeTimers();
  const {host, form} = mount();
  await nextTick();
  const card = host.querySelector<HTMLElement>('[data-nested-id="81"]')!;
  form.remove();
  window.dispatchEvent(
    new CustomEvent('craft:nested-element-action', {
      detail: {
        action: 'element-action',
        elementId: 81,
        trigger: card,
        item: standardNestedActions.find((item) => item.key === DELETE_ACTION),
      },
    })
  );
  await vi.runAllTimersAsync();
  expect(actions.run).not.toHaveBeenCalled();
  expect(requests.post).not.toHaveBeenCalled();
});

it('renders an editable embedded index in an HTML form without an Inertia page', async () => {
  const {host} = mount({index: true});
  await nextTick();
  expect(host.textContent).toContain('Entry 81');
  expect(host.querySelector('table')).not.toBeNull();
});

it('hides creation controls and blocks mutations for a read-only nested field', async () => {
  const {host} = mount({mode: 'readOnly'});
  await deleteCard(host);
  await nextTick();
  expect(host.textContent).toContain('Entry 81');
  expect(button(host, 'New element')).toBeUndefined();
  expect(actions.run).not.toHaveBeenCalled();
  expect(requests.post).not.toHaveBeenCalled();
});
