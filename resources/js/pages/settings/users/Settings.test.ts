import {createApp, defineComponent, h} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {
  UiControlPayload,
  UiPayload,
  UiProperties,
} from '@/modules/ui/types';
import '@craftcms/ui/components/checkbox/checkbox';
import '@craftcms/ui/components/checkbox-group/checkbox-group';
import Settings from './Settings.vue';

const state = vi.hoisted(() => ({
  setRequire2fa: vi.fn(),
}));

vi.mock('@/pages/Ui.vue', () => ({
  default: defineComponent({
    setup:
      (_, {slots}) =>
      () =>
        h('div', [
          slots.require2fa?.({
            control: control('require2fa', {
              options: [
                {label: 'All users', value: 'all'},
                {label: 'Admins', value: 'admins'},
              ],
            }),
            value: false,
            label: 'Require two-step verification',
            setValue: state.setRequire2fa,
            editable: true,
            invalid: false,
          }),
        ]),
  }),
}));

const ui: UiPayload = {
  scope: [],
  refreshable: true,
  nodes: [],
  values: {},
  errors: [],
  globalErrors: [],
};

let app: ReturnType<typeof createApp>;
let container: HTMLElement;

beforeEach(() => {
  state.setRequire2fa.mockReset();
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
});

it('keeps the exclusive two-step verification value shape', () => {
  mount();
  const all = container.querySelector<HTMLInputElement>(
    'craft-checkbox-group craft-checkbox input'
  );
  if (!all) throw new Error('Expected the two-step verification group.');

  all.click();
  all.click();

  expect(state.setRequire2fa).toHaveBeenNthCalledWith(1, 'all');
  expect(state.setRequire2fa).toHaveBeenNthCalledWith(2, false);
});

function mount(): void {
  app = createApp(Settings, {
    ui,
    submit: {method: 'post', url: '/settings/users'},
    refreshUrl: '/settings/users/render-ui',
  });
  app.mount(container);
}

function control(path: string, props: UiProperties): UiControlPayload {
  return {
    type: 'test',
    component: 'test',
    props,
    path: [path],
    mode: 'editable',
    deltaGroup: [path],
    uis: [],
  };
}
