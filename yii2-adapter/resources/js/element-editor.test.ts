import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, nextTick, provide} from 'vue';
import {router} from '@inertiajs/vue3';
import {cpComponentRegistry} from '@/bootstrap/components';
import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
import {provideLayoutSlotRegistry} from '@/common/composables/layoutSlots';
import {
  createScreenPropsStore,
  ScreenPagePropsKey,
  ScreenPropsStoreKey,
  type ScreenPageProps,
} from '@/common/composables/screen';
import {registerUiComponents} from '@/modules/ui/register';
import type {UiPayload} from '@/modules/ui/types';
import EditPage from '@/pages/elements/Edit.vue';
import './element-editor';

const {postSpy} = vi.hoisted(() => ({postSpy: vi.fn()}));
vi.mock('@craftcms/ui', async (importOriginal) => ({
  ...(await importOriginal<Record<string, unknown>>()),
  actionClient: {post: postSpy},
}));
vi.mock('@inertiajs/vue3', async (importOriginal) => ({
  ...(await importOriginal<Record<string, unknown>>()),
  usePage: () => ({props: {}}),
}));
let app: ReturnType<typeof createApp>;
let container: HTMLElement;
const internals = Object.getOwnPropertyDescriptor(
  HTMLElement.prototype,
  'attachInternals'
);
beforeEach(() => {
  Object.defineProperty(HTMLElement.prototype, 'attachInternals', {
    configurable: true,
    value: () => ({setFormValue: vi.fn()}),
  });
  vi.useFakeTimers({toFake: ['setTimeout', 'clearTimeout']});
  vi.stubGlobal(
    'fetch',
    vi.fn(async () => new Response('<svg></svg>'))
  );
  postSpy.mockReset();
  postSpy.mockResolvedValue({data: {draftId: 7}});
});
afterEach(() => {
  app?.unmount();
  if (app) {
    cpComponentRegistry.uninstall(app);
  }
  container?.remove();
  if (internals) {
    Object.defineProperty(HTMLElement.prototype, 'attachInternals', internals);
  } else {
    delete (HTMLElement.prototype as Partial<HTMLElement>).attachInternals;
  }
  vi.useRealTimers();
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
});

it('autosaves and submits custom HTML inputs together with native field edits', async () => {
  const ui: UiPayload = {
    scope: [],
    refreshable: false,
    nodes: [
      {
        type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
        component: 'craft:field',
        props: {label: 'Body', instructions: null, required: false},
        control: {
          type: 'CraftCms\\Cms\\Ui\\Controls\\Text',
          component: 'craft:text',
          props: {inputType: 'text'},
          path: ['fields', 'body'],
          mode: 'editable',
          deltaGroup: ['fields', 'body'],
          uis: [],
        },
      },
    ],
    values: {fields: {body: 'Original body'}},
    errors: [],
    globalErrors: [],
  };
  const props = {
    elementId: 12,
    canonicalId: 12,
    elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
    siteId: 1,
    draftId: null,
    isProvisionalDraft: false,
    canAutosave: true,
    ui,
    sidebarUi: null,
    saveParams: {entryId: 12, siteId: 1},
    saveUrl: '/actions/entries/save-entry',
    applyDraftUrl: '/actions/elements/apply-draft',
    autosaveUrl: '/actions/elements/save-draft',
    activityUrl: null,
    updatedTimestamps: {element: 1, canonical: 1},
    editorActions: {
      primary: {
        label: 'Save',
        actionUrl: null,
        params: {},
        redirect: null,
        tabId: null,
      },
      menu: [],
      buttons: [],
    },
    actionMenu: [],
    previewTargets: [],
    editorComponent: 'craft-legacy:element-editor',
    editorContentHtml:
      '<div data-element-editor-form></div><label>Instructions<input name="fields[instructions]" value="Leave at door"></label><shipping-choice></shipping-choice>',
  };
  app = createApp({
    setup() {
      const screen = createScreenPropsStore();
      provide(ScreenPagePropsKey, () => props as unknown as ScreenPageProps);
      provide(ScreenPropsStoreKey, screen);
      provideLayoutSlotRegistry();
      return () =>
        h(
          'form',
          {
            onSubmit(event: Event) {
              event.preventDefault();
              screen.save();
            },
          },
          [
            h(EditPage, props),
            h('button', {type: 'submit'}, 'Save'),
            h(LayoutSlotOutlet, {name: 'additional-buttons'}),
          ]
        );
    },
  });
  registerUiComponents(cpComponentRegistry);
  cpComponentRegistry.install(app);
  container = document.createElement('div');
  document.body.append(container);
  app.mount(container);
  await vi.waitFor(() =>
    expect(container.querySelector('input[name="fields[body]"]')).not.toBeNull()
  );
  await nextTick();

  const choice = container.querySelector('shipping-choice')!;
  Object.assign(choice, {name: 'delivery[method]', value: 'express'});
  choice.dispatchEvent(new Event('change', {bubbles: true}));
  const instructions = container.querySelector<HTMLInputElement>(
    'input[name="fields[instructions]"]'
  )!;
  instructions.value = 'Ring the bell';
  instructions.dispatchEvent(new Event('input', {bubbles: true}));
  const body = container.querySelector<HTMLInputElement>(
    'input[name="fields[body]"]'
  )!;
  body.value = 'Edited body';
  body.dispatchEvent(new Event('input', {bubbles: true}));
  await nextTick();

  await vi.advanceTimersByTimeAsync(1000);
  expect(postSpy).toHaveBeenCalledOnce();
  expect(postSpy.mock.calls[0]![1]).toEqual({
    elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
    elementId: 12,
    siteId: 1,
    provisional: 1,
    fields: {body: 'Edited body', instructions: 'Ring the bell'},
    delivery: {method: 'express'},
  });

  const submit = vi.spyOn(router, 'post').mockImplementation(() => {});
  container.querySelector<HTMLButtonElement>('button[type="submit"]')!.click();
  expect(submit).toHaveBeenCalledOnce();
  expect(submit.mock.calls[0]![1]).toEqual({
    elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
    elementId: 12,
    entryId: 12,
    siteId: 1,
    draftId: 7,
    provisional: 1,
    fields: {body: 'Edited body', instructions: 'Ring the bell'},
    delivery: {method: 'express'},
  });
});
