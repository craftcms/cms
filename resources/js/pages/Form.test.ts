import {
  createApp,
  defineComponent,
  h,
  nextTick,
  onMounted,
  reactive,
  shallowRef,
  type ComponentPublicInstance,
} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {FormChange, FormPayload, FormValue} from '@/modules/forms/types';
import FormPage from './Form.vue';

const state = vi.hoisted<{
  layout: ReturnType<typeof vi.fn>;
  post: ReturnType<typeof vi.fn>;
  refresh?: (
    values: FormPayload['values'],
    scope: string[]
  ) => Promise<FormPayload>;
  submit: ReturnType<
    typeof vi.fn<
      (action: FormSubmitAction, values: FormPayload['values']) => void
    >
  >;
  setValue: ReturnType<typeof vi.fn>;
  change?: (change: FormChange, values: FormPayload['values']) => void;
  currentValues: FormPayload['values'];
  confirmElevation: ReturnType<typeof vi.fn>;
}>(() => ({
  layout: vi.fn(),
  post: vi.fn(),
  refresh: undefined,
  submit: vi.fn(),
  setValue: vi.fn(),
  change: undefined,
  currentValues: {siteId: 42, name: 'Changed', live: '1'},
  confirmElevation: vi.fn(),
}));

interface FormSubmitAction {
  method: 'get' | 'post' | 'put' | 'patch' | 'delete';
  url: string;
}

type FormPageExposed = ComponentPublicInstance & {
  setValue(path: string[], value: FormValue, kind?: FormChange['kind']): void;
};

vi.mock('@craftcms/ui', async (importOriginal) => ({
  ...(await importOriginal()),
  actionClient: {post: state.post},
}));

vi.mock('@inertiajs/vue3', () => ({
  usePage: () => ({props: {}}),
  useForm: () => {
    let transform = (data: FormPayload['values']) => data;
    const form = {
      errors: {},
      isDirty: false,
      name: undefined,
      data: () => (form.name === undefined ? {} : {name: form.name}),
      defaults: vi.fn(),
      clearErrors: vi.fn(),
      transform: vi.fn((callback: typeof transform) => {
        transform = callback;
        return form;
      }),
      submit: vi.fn(
        (action: FormSubmitAction, options: {onSuccess?: () => void}) => {
          state.submit(action, transform(form.data()));
          options.onSuccess?.();
        }
      ),
    };
    form.clearErrors.mockImplementation(() => form);

    return form;
  },
}));

vi.mock('@/common/composables/useAppLayout', () => ({
  useAppLayout: state.layout,
}));

vi.mock('@/modules/auth/elevated-session', () => ({
  elevatedSessionManager: {require: state.confirmElevation},
}));

vi.mock('@/modules/forms/FormRenderer.vue', () => ({
  default: defineComponent({
    props: ['refresh'],
    emits: ['change', 'update:mutation'],
    setup: (props, {emit, expose, slots}) => {
      state.refresh = props.refresh;
      state.change = (change, values) => emit('change', change, values);
      expose({
        advanceBaseline: vi.fn(),
        currentValues: () => structuredClone(state.currentValues),
        setValue: state.setValue,
      });
      onMounted(() => emit('update:mutation', {name: 'Changed'}));

      return () => h('div', slots.name?.({value: 'Original'}));
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
  state.confirmElevation.mockReset();
  state.currentValues = {siteId: 42, name: 'Changed', live: '1'};
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

  expect(container.querySelector('form')).not.toBeNull();

  const layoutCall = state.layout.mock.calls[0];
  if (!layoutCall) throw new Error('Expected the layout registration.');
  layoutCall[0].onSave({redirect: false});

  expect(state.submit).toHaveBeenCalledWith(
    {method: 'post', url: '/settings/general'},
    expect.objectContaining({siteId: 42, name: 'Changed', live: '1'})
  );
});

it('passes screen layout options through to the app layout', () => {
  app = createApp(FormPage, {
    form: payload,
    submit: {method: 'post', url: '/settings/users'},
    fullWidth: true,
    defaultFormActions: [],
  });
  app.mount(container);

  expect(state.layout).toHaveBeenCalledWith(
    expect.objectContaining({fullWidth: true, defaultFormActions: []})
  );
});

it('accepts reactive Inertia form values', () => {
  app = createApp(FormPage, {
    form: reactive(payload),
    submit: {method: 'post', url: '/settings/users/groups'},
    elevatedFields: ['permissions'],
  });

  expect(() => app.mount(container)).not.toThrow();
});

it('confirms elevated field changes once per saved baseline', async () => {
  state.currentValues = {
    siteId: 42,
    name: 'Changed',
    live: '1',
    permissions: ['accessCp'],
  };
  state.confirmElevation.mockResolvedValue(true);
  app = createApp(FormPage, {
    form: {...payload, values: {...payload.values, permissions: []}},
    submit: {method: 'post', url: '/settings/users/groups'},
    elevatedFields: ['permissions'],
  });
  app.mount(container);
  await nextTick();

  const layoutCall = state.layout.mock.calls[0];
  if (!layoutCall) throw new Error('Expected the layout registration.');
  layoutCall[0].onSave();
  await vi.waitFor(() => expect(state.submit).toHaveBeenCalledTimes(1));

  layoutCall[0].onSave();

  expect(state.confirmElevation).toHaveBeenCalledTimes(1);
  expect(state.submit).toHaveBeenCalledTimes(2);
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

  if (!state.refresh) throw new Error('Expected the form refresh callback.');
  const result = await state.refresh({hasUrls: true}, []);

  expect(state.post).toHaveBeenCalledWith('/settings/sites/form', {
    values: {hasUrls: true},
    scope: [],
  });
  expect(result).toBe(refreshed);
});

it('forwards control changes and external value updates', async () => {
  const onChange = vi.fn();
  const page = shallowRef<FormPageExposed | null>(null);
  app = createApp({
    setup: () => () =>
      h(FormPage, {
        ref: page,
        form: payload,
        submit: {method: 'post', url: '/settings/sites'},
        onChange,
      }),
  });
  app.mount(container);
  await nextTick();
  if (!page.value) throw new Error('Expected FormPage to mount.');

  const change: FormChange = {kind: 'typing', path: ['name']};
  if (!state.change)
    throw new Error('Expected the FormRenderer change callback.');
  state.change(change, {name: 'My Site'});
  page.value.setValue(['baseUrl'], '$MY_SITE_URL', 'typing');

  expect(onChange).toHaveBeenCalledWith(change, {name: 'My Site'});
  expect(state.setValue).toHaveBeenCalledWith(
    ['baseUrl'],
    '$MY_SITE_URL',
    'typing'
  );
});

it('forwards path-keyed control slots to the Form renderer', async () => {
  app = createApp({
    setup: () => () =>
      h(
        FormPage,
        {
          form: payload,
          submit: {method: 'post', url: '/settings/sites'},
        },
        {
          name: ({value}: {value: string}) =>
            h('span', {'data-name-override': ''}, value),
        }
      ),
  });
  app.mount(container);
  await nextTick();

  expect(container.querySelector('[data-name-override]')?.textContent).toBe(
    'Original'
  );
});
