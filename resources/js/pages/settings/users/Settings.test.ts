import {createApp, defineComponent, h} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {
  FormControlPayload,
  FormPayload,
  FormProperties,
} from '@/modules/forms/types';
import Settings from './Settings.vue';

interface CheckboxGroupElement extends HTMLElement {
  modelValue: string[];
}

const state = vi.hoisted(() => ({
  openSlideout: vi.fn(),
  setPhotoVolume: vi.fn(),
  setRequire2fa: vi.fn(),
}));

vi.mock('@actions/Settings/VolumesController', () => ({
  create: {url: () => '/settings/assets/new'},
}));

vi.mock('@/common/slideouts', () => ({
  openSlideout: state.openSlideout,
}));

vi.mock('@craftcms/ui/vue/CraftCombobox.vue', () => ({
  default: defineComponent({
    emits: ['update:modelValue'],
    setup:
      (_, {emit}) =>
      () =>
        h('button', {
          'data-create-volume': '',
          onClick: () => emit('update:modelValue', '__createVolume__'),
        }),
  }),
}));

vi.mock('@/pages/Form.vue', () => ({
  default: defineComponent({
    setup:
      (_, {slots}) =>
      () =>
        h('div', [
          slots.photoVolumeUid?.({
            control: control('photoVolumeUid', {
              options: [
                {label: 'Existing', value: 'existing'},
                {label: 'Create a new volume…', value: '__createVolume__'},
              ],
            }),
            value: '',
            setValue: state.setPhotoVolume,
            editable: true,
            invalid: false,
            required: false,
          }),
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

const form: FormPayload = {
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
  state.openSlideout.mockReset();
  state.setPhotoVolume.mockReset();
  state.setRequire2fa.mockReset();
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
});

it('selects a volume created from the combobox', async () => {
  mount();
  container.querySelector<HTMLElement>('[data-create-volume]')?.click();

  expect(state.openSlideout).toHaveBeenCalledWith(
    '/settings/assets/new',
    expect.objectContaining({onSaved: expect.any(Function)})
  );

  const openCall = state.openSlideout.mock.calls[0];
  if (!openCall) throw new Error('Expected the volume slideout to open.');
  openCall[1].onSaved({
    data: {volume: {name: 'User Photos', uid: 'new-volume'}},
  });

  expect(state.setPhotoVolume).toHaveBeenCalledWith('new-volume');
});

it('keeps the exclusive two-step verification value shape', () => {
  mount();
  const group = container.querySelector<CheckboxGroupElement>(
    'craft-checkbox-group'
  );
  if (!group) throw new Error('Expected the two-step verification group.');

  group.modelValue = ['all'];
  group.dispatchEvent(new CustomEvent('model-value-changed'));
  group.modelValue = [];
  group.dispatchEvent(new CustomEvent('model-value-changed'));

  expect(state.setRequire2fa).toHaveBeenNthCalledWith(1, 'all');
  expect(state.setRequire2fa).toHaveBeenNthCalledWith(2, false);
});

function mount(): void {
  app = createApp(Settings, {
    form,
    submit: {method: 'post', url: '/settings/users'},
    refreshUrl: '/settings/users/render-form',
  });
  app.mount(container);
}

function control(path: string, props: FormProperties): FormControlPayload {
  return {
    type: 'test',
    component: 'test',
    props,
    path: [path],
    mode: 'editable',
    deltaGroup: [path],
    forms: [],
  };
}
