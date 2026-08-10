import {createApp, defineComponent, h, nextTick, onMounted} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {FormChange, FormPayload} from '@/modules/forms/types';
import FormPage from './Form.vue';

const state = vi.hoisted(() => ({
  layout: vi.fn(),
  post: vi.fn(),
  refresh: undefined as
    | ((values: FormPayload['values'], scope: string[]) => Promise<FormPayload>)
    | undefined,
  submit: vi.fn(),
  setValue: vi.fn(),
  change: undefined as
    | ((change: FormChange, values: FormPayload['values']) => void)
    | undefined,
  currentValues: {name: 'Changed', live: '1'},
}));

vi.mock('@craftcms/ui', async (importOriginal) => ({
  ...(await importOriginal()),
  actionClient: {post: state.post},
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
    props: ['refresh'],
    emits: ['change', 'update:mutation'],
    setup: (props, {emit, expose}) => {
      state.refresh = props.refresh;
      state.change = (change, values) => emit('change', change, values);
      expose({
        advanceBaseline: vi.fn(),
        currentValues: () => structuredClone(state.currentValues),
        setValue: state.setValue,
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
  state.post.mockReset();
  state.refresh = undefined;
  state.change = undefined;
  state.setValue.mockReset();
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

it('refreshes the form through the configured endpoint', async () => {
  const refreshed = {...payload, values: {name: 'Changed', live: '1'}};
  state.post.mockResolvedValueOnce({data: {form: refreshed}});
  app = createApp(FormPage, {
    form: payload,
    submit: {method: 'post', url: '/settings/sites'},
    refreshUrl: '/settings/sites/form',
  });
  app.mount(container);
  await nextTick();

  const result = await state.refresh!({hasUrls: true}, []);

  expect(state.post).toHaveBeenCalledWith('/settings/sites/form', {
    values: {hasUrls: true},
    scope: [],
  });
  expect(result).toBe(refreshed);
});

it('forwards control changes and external value updates', async () => {
  const onChange = vi.fn();
  app = createApp(FormPage, {
    form: payload,
    submit: {method: 'post', url: '/settings/sites'},
    onChange,
  });
  const page = app.mount(container) as unknown as {
    setValue(path: string[], value: unknown, kind?: FormChange['kind']): void;
  };
  await nextTick();

  const change: FormChange = {kind: 'typing', path: ['name']};
  state.change!(change, {name: 'My Site'});
  page.setValue(['baseUrl'], '$MY_SITE_URL', 'typing');

  expect(onChange).toHaveBeenCalledWith(change, {name: 'My Site'});
  expect(state.setValue).toHaveBeenCalledWith(
    ['baseUrl'],
    '$MY_SITE_URL',
    'typing'
  );
});
