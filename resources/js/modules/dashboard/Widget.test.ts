import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, defineComponent, h, type App} from 'vue';
import Widget from './Widget.vue';
import type {DashboardWidget} from './types';

const state = vi.hoisted(() => ({loadSettingsUi: vi.fn()}));
vi.mock('./settingsUi', () => ({loadSettingsUi: state.loadSettingsUi}));
vi.mock('./WidgetSettings.vue', () => ({default: () => null}));
vi.mock('@/modules/messages/useMessages', () => ({
  useMessages: () => ({error: vi.fn()}),
}));

let app: App;
let host: HTMLElement;

/** A widget's component, which draws the header the dashboard gives it. */
const TestWidget = defineComponent({
  setup:
    (_, {slots}) =>
    () =>
      h('div', slots.header?.()),
});

function mount(settings: (open: boolean) => void) {
  host = document.createElement('div');
  document.body.append(host);
  app = createApp({
    setup: () => () => h(Widget, {widget, ready: true, onSettings: settings}),
  });
  app.component('test-widget', TestWidget);
  app.mount(host);
}

afterEach(() => {
  app?.unmount();
  host?.remove();
  vi.resetAllMocks();
});

const widget: DashboardWidget = {
  id: 3,
  type: 'Example',
  name: 'Example',
  title: 'Example',
  subtitle: null,
  colspan: 1,
  maxColspan: 4,
  color: 'gray',
  settings: {},
  hasSettings: true,
  component: 'test-widget',
  data: null,
  fragment: {html: '', headHtml: '', bodyHtml: ''},
};

it('counts its settings as open while they load, so reloads wait for them', async () => {
  let respond!: (ui: unknown) => void;
  state.loadSettingsUi.mockReturnValue(
    new Promise((resolve) => (respond = resolve))
  );
  const settings = vi.fn();
  mount(settings);
  expect(settings.mock.lastCall).toEqual([false]);

  host
    .querySelector('.widget-settings-button')!
    .dispatchEvent(new MouseEvent('click', {bubbles: true}));
  await vi.waitFor(() => expect(settings.mock.lastCall).toEqual([true]));
  expect(host.querySelector('.settings-face')).toBeNull();

  respond({scope: ['widget3-settings'], nodes: [], values: {}});
  await vi.waitFor(() =>
    expect(host.querySelector('.settings-face')).not.toBeNull()
  );
  expect(settings.mock.lastCall).toEqual([true]);
});

it('counts its settings as closed again when they can’t load', async () => {
  state.loadSettingsUi.mockRejectedValue(new Error('offline'));
  const settings = vi.fn();
  mount(settings);

  host
    .querySelector('.widget-settings-button')!
    .dispatchEvent(new MouseEvent('click', {bubbles: true}));

  await vi.waitFor(() => expect(settings).toHaveBeenCalledWith(true));
  await vi.waitFor(() => expect(settings.mock.lastCall).toEqual([false]));
  expect(host.querySelector('.settings-face')).toBeNull();
});
