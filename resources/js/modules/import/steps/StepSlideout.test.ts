import {createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {FormPayload} from '@/modules/forms/types';
import type {StepPayload} from '@/modules/import/mapping/types';
import StepSlideout from './StepSlideout.vue';

const state = vi.hoisted(() => ({
  layout: vi.fn(),
  fetchStepForm: vi.fn(),
  validateStep: vi.fn(),
  openStepMapping: vi.fn(),
  errorHandler: vi.fn(),
  context: null as any,
  values: {} as Record<string, unknown>,
  refresh: null as ((values: unknown) => Promise<FormPayload>) | null,
  emitChange: null as (() => void) | null,
  errors: null as FormPayload['errors'] | null,
}));

vi.mock('@/common/composables/useAppLayout', () => ({
  useAppLayout: (options: unknown) => {
    state.layout(options instanceof Function ? options() : options);
  },
}));

vi.mock('@/common/slideouts', () => ({
  useSlideout: () => null,
}));

vi.mock('./step-mapping', () => ({
  openStepMapping: state.openStepMapping,
}));

vi.mock('./step-slideout', () => ({
  takeStepSlideoutContext: () => state.context,
  fetchStepForm: state.fetchStepForm,
  validateStep: state.validateStep,
}));

// Stubbed so the test can invoke the refresh callback the panel hands it, which is what
// carries an updated `canMap` back from the server, emit changes, and control the values
// it reports.
vi.mock('@/modules/forms/FormRenderer.vue', () => ({
  default: {
    name: 'FormRenderer',
    props: ['payload', 'refresh', 'errors'],
    emits: ['change'],
    setup(props: any, {expose, emit}: any) {
      state.refresh = props.refresh;
      state.emitChange = () => emit('change', {}, state.values);
      expose({currentValues: () => state.values});

      return () => {
        state.errors = props.errors;

        return h('div', {class: 'form-renderer'}, [
          h('input', {'data-form-control-path': '["file"]'}),
          h('input', {'data-form-control-path': '["transformer"]'}),
        ]);
      };
    },
  },
}));

function payload(): FormPayload {
  return {
    scope: [],
    refreshable: true,
    nodes: [],
    values: {},
    errors: [],
    globalErrors: [],
  } as unknown as FormPayload;
}

const step: StepPayload = {
  uid: 'step-1',
  type: 'CraftCms\\Cms\\Entry\\Import\\EntryImporter',
  file: 'people.csv',
  transformer: null,
  batchSize: null,
  settings: {},
};

const urls = {
  settingsUrl: '/actions/import/step-settings',
  validateUrl: '/actions/import/validate-step',
  mappingUrl: '/actions/import/step-mapping',
  nestedColsUrl: '/actions/import/nested-mapping-cols',
};

let app: ReturnType<typeof createApp>;
let container: HTMLElement;

function mount(canMap: boolean, type: string | null = step.type) {
  state.values = {type, file: step.file, settings: {}};
  state.context = {
    step: {...structuredClone(step), type},
    payload: payload(),
    canMap,
    urls,
    editable: true,
    apply: vi.fn(),
  };

  app = createApp(StepSlideout, {contextId: 'ctx', title: 'Edit step'});
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.config.errorHandler = state.errorHandler;
  app.mount(container);
}

/** The Mapping section only exists once the form for an importer type has loaded. */
function mappingSection(): HTMLElement | null {
  return container.querySelector('section');
}

function mappingButton(): HTMLElement & {disabled: boolean; loading: boolean} {
  return mappingSection()!.querySelector('craft-button') as HTMLElement & {
    disabled: boolean;
    loading: boolean;
  };
}

function blur(path: string): void {
  container
    .querySelector(`[data-form-control-path='${path}']`)!
    .dispatchEvent(new FocusEvent('focusout', {bubbles: true}));
}

function spinner(): Element | null {
  return container.querySelector('craft-spinner');
}

function deferred<T>() {
  let resolve!: (value: T) => void;
  let reject!: (reason: unknown) => void;
  const promise = new Promise<T>((res, rej) => {
    resolve = res;
    reject = rej;
  });

  return {promise, resolve, reject};
}

beforeEach(() => {
  state.layout.mockClear();
  state.fetchStepForm.mockReset();
  state.validateStep.mockReset();
  state.openStepMapping.mockReset();
  state.errorHandler.mockReset();
  state.refresh = null;
  state.emitChange = null;
  state.errors = null;
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
  vi.useRealTimers();
});

it('hides the mapping section until an importer type is chosen', () => {
  mount(false, null);

  expect(mappingSection()).toBeNull();
});

it('shows the mapping section with a disabled button while the step can’t be mapped', () => {
  mount(false);

  expect(mappingSection()!.textContent).toContain('Mapping');
  expect(mappingButton().disabled).toBe(true);
});

it('enables the mapping button once the step can be mapped', () => {
  mount(true);

  expect(mappingButton().disabled).toBe(false);
});

it('enables the mapping button when a refresh reports the step as mappable', async () => {
  // An element importer only resolves its field layout once an entry type is chosen, which
  // reaches the panel as a refresh — the button has to enable without reopening.
  mount(false);
  state.fetchStepForm.mockResolvedValue({form: payload(), canMap: true});

  await state.refresh!({settings: {}});
  await nextTick();

  expect(mappingButton().disabled).toBe(false);
});

it('disables the mapping button again when a refresh reports it unmappable', async () => {
  mount(true);
  state.fetchStepForm.mockResolvedValue({form: payload(), canMap: false});

  await state.refresh!({settings: {}});
  await nextTick();

  expect(mappingButton().disabled).toBe(true);
});

it('shows the mapping section only once the chosen type’s form arrives', async () => {
  vi.useFakeTimers();
  const loading = deferred<{form: FormPayload; canMap: boolean}>();
  state.fetchStepForm.mockReturnValue(loading.promise);
  mount(false, null);

  state.values = {...state.values, type: step.type};
  state.emitChange!();
  const refreshing = state.refresh!({settings: {}});
  await nextTick();

  expect(mappingSection()).toBeNull();
  expect(spinner()).toBeNull();

  vi.advanceTimersByTime(200);
  await nextTick();

  expect(spinner()).not.toBeNull();
  expect(mappingSection()).toBeNull();

  loading.resolve({form: payload(), canMap: true});
  await refreshing;
  await nextTick();

  expect(spinner()).toBeNull();
  expect(mappingSection()).not.toBeNull();
  expect(mappingButton().disabled).toBe(false);
});

it('doesn’t show the spinner for a refresh that keeps the type', async () => {
  vi.useFakeTimers();
  const loading = deferred<{form: FormPayload; canMap: boolean}>();
  state.fetchStepForm.mockReturnValue(loading.promise);
  mount(false);

  const refreshing = state.refresh!({settings: {}});
  vi.advanceTimersByTime(200);
  await nextTick();

  expect(spinner()).toBeNull();

  loading.resolve({form: payload(), canMap: true});
  await refreshing;
});

it('ignores a refresh response that a newer one has overtaken', async () => {
  const first = deferred<{form: FormPayload; canMap: boolean}>();
  const second = deferred<{form: FormPayload; canMap: boolean}>();
  state.fetchStepForm
    .mockReturnValueOnce(first.promise)
    .mockReturnValueOnce(second.promise);
  mount(false, null);

  state.values = {...state.values, type: step.type};
  const stale = state.refresh!({settings: {}});
  state.values = {...state.values, type: null};
  const latest = state.refresh!({settings: {}});

  second.resolve({form: payload(), canMap: false});
  await latest;
  first.resolve({form: payload(), canMap: true});
  await stale;
  await nextTick();

  expect(mappingSection()).toBeNull();
});

it('clears the spinner and keeps the mapping section hidden when a refresh fails', async () => {
  vi.useFakeTimers();
  const loading = deferred<{form: FormPayload; canMap: boolean}>();
  state.fetchStepForm.mockReturnValue(loading.promise);
  mount(false, null);

  state.values = {...state.values, type: step.type};
  const refreshing = state.refresh!({settings: {}});
  vi.advanceTimersByTime(200);
  await nextTick();

  expect(spinner()).not.toBeNull();

  loading.reject(new Error('Request failed.'));
  await refreshing.catch(() => {});
  await nextTick();

  expect(spinner()).toBeNull();
  expect(mappingSection()).toBeNull();
});

it('checks whether the step can be mapped once the data file field loses focus', async () => {
  const checking = deferred<{form: FormPayload; canMap: boolean}>();
  state.fetchStepForm.mockReturnValue(checking.promise);
  mount(false);

  state.values = {...state.values, file: 'other.csv'};
  blur('["file"]');
  await nextTick();

  expect(state.fetchStepForm).toHaveBeenCalledOnce();
  expect(state.fetchStepForm.mock.calls[0]![1]).toMatchObject({
    file: 'other.csv',
  });
  expect(mappingButton().loading).toBe(true);

  checking.resolve({form: payload(), canMap: true});
  await checking.promise;
  await nextTick();

  expect(mappingButton().loading).toBe(false);
  expect(mappingButton().disabled).toBe(false);
});

it('doesn’t check again when the data file hasn’t changed', async () => {
  mount(false);

  blur('["file"]');
  await nextTick();

  expect(state.fetchStepForm).not.toHaveBeenCalled();
});

it('doesn’t check when another field loses focus', async () => {
  mount(false);

  state.values = {...state.values, file: 'other.csv'};
  blur('["transformer"]');
  await nextTick();

  expect(state.fetchStepForm).not.toHaveBeenCalled();
});

it('lets a refresh that starts after a file check decide whether the step can be mapped', async () => {
  const checking = deferred<{form: FormPayload; canMap: boolean}>();
  const refreshing = deferred<{form: FormPayload; canMap: boolean}>();
  state.fetchStepForm
    .mockReturnValueOnce(checking.promise)
    .mockReturnValueOnce(refreshing.promise);
  mount(false);

  state.values = {...state.values, file: 'other.csv'};
  blur('["file"]');
  const refresh = state.refresh!({settings: {}});

  refreshing.resolve({form: payload(), canMap: false});
  await refresh;
  checking.resolve({form: payload(), canMap: true});
  await checking.promise;
  await nextTick();

  expect(mappingButton().disabled).toBe(true);
  expect(mappingButton().loading).toBe(false);
});

it('keeps the last reported state when the file check fails', async () => {
  const checking = deferred<{form: FormPayload; canMap: boolean}>();
  state.fetchStepForm.mockReturnValue(checking.promise);
  mount(true);

  state.values = {...state.values, file: 'missing.csv'};
  blur('["file"]');
  checking.reject(new Error('Request failed.'));
  await checking.promise.catch(() => {});
  await nextTick();

  expect(mappingButton().disabled).toBe(false);
  expect(mappingButton().loading).toBe(false);
  expect(state.errorHandler).not.toHaveBeenCalled();
});

it('shows a spinner on the mapping button while the mapping slideout opens', async () => {
  const opening = deferred<boolean>();
  state.openStepMapping.mockReturnValue(opening.promise);
  mount(true);

  mappingButton().dispatchEvent(new Event('click'));
  await nextTick();

  expect(mappingButton().loading).toBe(true);

  opening.resolve(true);
  await opening.promise;
  await nextTick();

  expect(mappingButton().loading).toBe(false);
});

it('reports a mapping slideout that fails to open and clears the spinner', async () => {
  state.openStepMapping.mockRejectedValue(new Error('Request failed.'));
  mount(true);

  mappingButton().dispatchEvent(new Event('click'));
  await nextTick();
  await nextTick();

  expect(mappingButton().loading).toBe(false);
  expect(mappingSection()!.querySelector('[role="alert"]')!.textContent).toBe(
    'Request failed.'
  );
  expect(state.errorHandler).not.toHaveBeenCalled();
});

it('clears a previous mapping error when trying again', async () => {
  state.openStepMapping.mockRejectedValueOnce(new Error('Request failed.'));
  state.openStepMapping.mockResolvedValueOnce(true);
  mount(true);

  mappingButton().dispatchEvent(new Event('click'));
  await nextTick();
  await nextTick();

  mappingButton().dispatchEvent(new Event('click'));
  await nextTick();

  expect(mappingSection()!.querySelector('[role="alert"]')).toBeNull();
});

it('ignores repeat clicks on the mapping button while the slideout opens', async () => {
  const opening = deferred<boolean>();
  state.openStepMapping.mockReturnValue(opening.promise);
  mount(true);

  mappingButton().dispatchEvent(new Event('click'));
  mappingButton().dispatchEvent(new Event('click'));
  await nextTick();

  expect(state.openStepMapping).toHaveBeenCalledOnce();

  opening.resolve(true);
  await opening.promise;
});

it('keeps the slideout open and shows the errors when the draft step is invalid', async () => {
  mount(true);
  state.validateStep.mockRejectedValue({
    response: {data: {errors: {file: ['File must be provided.']}}},
  });

  const onSave = state.layout.mock.calls.at(-1)![0].onSave;
  await onSave();
  await nextTick();

  expect(state.context.apply).not.toHaveBeenCalled();
  expect(state.errors).toEqual([
    {path: ['file'], messages: ['File must be provided.']},
  ]);
});

it('applies the step and lets the slideout close once it validates', async () => {
  mount(true);
  state.validateStep.mockResolvedValue(undefined);

  const onSave = state.layout.mock.calls.at(-1)![0].onSave;
  await onSave();

  expect(state.context.apply).toHaveBeenCalledWith(
    expect.objectContaining({uid: 'step-1'})
  );
});
