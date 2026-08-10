import {createApp, defineComponent, h, nextTick, onMounted} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {FormPayload} from '@/modules/forms/types';
import FormPage from './Form.vue';

const state = vi.hoisted(() => ({
  layout: vi.fn(),
  submit: vi.fn(),
  currentValues: {name: 'Changed', live: '1'},
}));

vi.mock('@inertiajs/vue3', () => ({
  usePage: () => ({props: {}}),
  useForm: () => {
    let transform = (data: Record<string, unknown>) => data;
    const form: Record<string, any> = {
      errors: {},
      isDirty: false,
      data: () => (form.name === undefined ? {} : {name: form.name}),
      defaults: vi.fn(),
    };
    form.clearErrors = vi.fn(() => form);
    form.transform = vi.fn((callback) => {
      transform = callback;
      return form;
    });
    form.submit = vi.fn((action, options) => {
      state.submit(action, transform(form.data()));
      options.onSuccess?.();
    });

    return form;
  },
}));

vi.mock('@/common/composables/useAppLayout', () => ({
  useAppLayout: state.layout,
}));

vi.mock('@/common/components/Pane.vue', () => ({
  default: defineComponent({
    setup:
      (_, {slots}) =>
      () =>
        h('div', slots.default?.()),
  }),
}));

vi.mock('@/modules/forms/FormRenderer.vue', () => ({
  default: defineComponent({
    emits: ['update:mutation'],
    setup: (_, {emit, expose}) => {
      expose({
        advanceBaseline: vi.fn(),
        currentValues: () => structuredClone(state.currentValues),
      });
      onMounted(() => emit('update:mutation', {name: 'Changed'}));

      return () => h('div');
    },
  }),
}));

const payload: FormPayload = {
  scope: [],
  refreshable: false,
  nodes: [],
  values: {name: 'Original', live: '1'},
  errors: [],
  globalErrors: [],
};

let app: ReturnType<typeof createApp>;
let container: HTMLElement;

beforeEach(() => {
  state.layout.mockClear();
  state.submit.mockClear();
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
});

it('submits complete current values after a partial mutation', async () => {
  app = createApp(FormPage, {
    form: payload,
    submit: {method: 'post', url: '/settings/general'},
  });
  app.mount(container);
  await nextTick();

  state.layout.mock.calls[0]![0].onSave({redirect: false});

  expect(state.submit).toHaveBeenCalledWith(
    {method: 'post', url: '/settings/general'},
    expect.objectContaining({name: 'Changed', live: '1'})
  );
});
