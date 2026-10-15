import {createApp, h, nextTick, reactive, type App} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';

const state = vi.hoisted(() => ({
  isAdmin: true,
  errors: {} as Record<string, string>,
}));

vi.mock('@inertiajs/vue3', async () => {
  const {reactive} = await import('vue');

  return {
    usePage: () => ({
      props: {
        isAdmin: state.isAdmin,
        preferences: {},
        languageOptions: [],
        localeOptions: [],
        weekStartDayOptions: [],
        timeZoneOptions: [],
        displaySettingOptions: [],
        generalSettingOptions: [],
        notificationDurationOptions: [],
        notificationPositionOptions: [],
        slideoutPositionOptions: [],
        developmentSettingOptions: [],
        prefsHook: null,
      },
    }),
    useForm: (initial: Record<string, unknown>) =>
      reactive({...initial, errors: state.errors}),
  };
});
vi.mock('@/common/composables/useAppLayout', () => ({useAppLayout: () => {}}));
vi.mock('@/modules/settings/composables/useSettingsSave', () => ({
  useSettingsSave: () => ({save: () => {}}),
}));
vi.mock('@actions/Users/PreferencesController', () => ({update: {}}));

const passthrough = {
  setup:
    (_props: unknown, {slots}: {slots: {default?: () => unknown}}) =>
    () =>
      slots.default?.(),
};
const empty = {render: () => null};
vi.mock('@/modules/user/components/UserScreen.vue', () => ({
  default: passthrough,
}));
vi.mock('@/common/components/LayoutSlot.vue', () => ({default: passthrough}));
vi.mock('@/common/form/CraftCombobox.vue', () => ({default: empty}));
vi.mock('@/common/form/Select.vue', () => ({default: empty}));
vi.mock('@/common/form/CheckboxGroup.vue', () => ({default: empty}));
vi.mock('@/common/components/HtmlFragmentRenderer.vue', () => ({
  default: empty,
}));

const {default: Preferences} = await import('./Preferences.vue');
let app: App;
let root: HTMLElement;

afterEach(() => {
  app?.unmount();
  root?.remove();
  state.isAdmin = true;
  state.errors = reactive({});
});

async function mount(): Promise<HTMLElement> {
  root = document.createElement('div');
  document.body.append(root);
  app = createApp({render: () => h(Preferences)});
  app.mount(root);
  await nextTick();

  return root;
}

function tabs(root: HTMLElement): HTMLElement[] {
  return [...root.querySelectorAll<HTMLElement>('craft-tabs craft-tab')];
}

it('splits the preferences into General, Accessibility, and Development tabs', async () => {
  const root = await mount();

  expect(tabs(root).map((tab) => tab.textContent?.trim())).toEqual([
    'General',
    'Accessibility',
    'Development',
  ]);
  expect(
    tabs(root).map((tab) => {
      const panel = root.querySelector(`#${tab.getAttribute('controls')}`);
      return panel?.classList.contains('hidden');
    })
  ).toEqual([false, true, true]);
});

it('leaves out the Development tab for non-admins', async () => {
  state.isAdmin = false;
  const root = await mount();

  expect(tabs(root).map((tab) => tab.textContent?.trim())).toEqual([
    'General',
    'Accessibility',
  ]);
  expect(root.querySelector('#preferences-development')).toBeNull();
});

it('flags the tab that holds a field with an error', async () => {
  state.errors = {notificationDuration: 'Invalid duration.'};
  const root = await mount();

  expect(
    tabs(root).map(
      (tab) => tab.querySelector('craft-icon[slot="suffix"]') !== null
    )
  ).toEqual([false, true, false]);
});
