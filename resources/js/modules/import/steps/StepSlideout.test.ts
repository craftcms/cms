import {createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {UiPayload} from '@/modules/ui/types';
import type {ImportStep} from '@/modules/import/mapping/types';
import {StepMappingUnavailableError} from './step-mapping';
import StepSlideout from './StepSlideout.vue';

const state = vi.hoisted(() => ({
  layout: vi.fn(),
  fetchStepUi: vi.fn(),
  validateStep: vi.fn(),
  openStepMapping: vi.fn(),
  errorHandler: vi.fn(),
  context: null as any,
  values: {} as Record<string, unknown>,
  refresh: null as ((values: unknown) => Promise<UiPayload>) | null,
  emitChange: null as (() => void) | null,
  errors: null as UiPayload['errors'] | null,
}));

vi.mock('@/common/composables/useAppLayout', () => ({
  useAppLayout: (options: unknown) => {
    state.layout(options instanceof Function ? options() : options);
  },
}));

vi.mock('@/common/slideouts', () => ({
  useSlideout: () => null,
}));

vi.mock('./step-mapping', async (importOriginal) => ({
  ...(await importOriginal<typeof import('./step-mapping')>()),
  openStepMapping: state.openStepMapping,
}));

vi.mock('./step-slideout', () => ({
  takeStepSlideoutContext: () => state.context,
  fetchStepUi: state.fetchStepUi,
  validateStep: state.validateStep,
}));

// Stubbed so the test can drive the panel's refresh callback and reported values.
vi.mock('@/modules/ui/UiRenderer.vue', () => ({
  default: {
    name: 'UiRenderer',
    props: ['payload', 'refresh', 'errors'],
    emits: ['change'],
    setup(props: any, {expose, emit}: any) {
      state.refresh = props.refresh;
      state.emitChange = () => emit('change', {}, state.values);
      expose({currentValues: () => state.values});

      return () => {
        state.errors = props.errors;

        return h('div', {class: 'ui-renderer'}, [
          h('input', {'data-ui-control-path': '["source"]'}),
          h('input', {'data-ui-control-path': '["transformer"]'}),
        ]);
      };
    },
  },
}));

function payload(): UiPayload {
  return {
    scope: [],
    refreshable: true,
    nodes: [],
    values: {},
    errors: [],
    globalErrors: [],
  } as unknown as UiPayload;
}

const step: ImportStep = {
  uid: 'step-1',
  type: 'CraftCms\\Cms\\Entry\\Import\\EntryImporter',
  source: 'people.csv',
  transformer: null,
  batchSize: null,
  settings: {},
};

let app: ReturnType<typeof createApp>;
let container: HTMLElement;

function mount(
  canMap: boolean,
  type: string | null = step.type,
  formSettings: Record<string, unknown> = {},
  editable = true
) {
  state.values = {type, source: step.source, settings: formSettings};
  state.context = {
    step: {...structuredClone(step), type},
    payload: payload(),
    canMap,
    sourceError: null,
    editable,
    apply: vi.fn(),
  };

  app = createApp(StepSlideout, {contextId: 'ctx', title: 'Edit step'});
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.config.errorHandler = state.errorHandler;
  app.mount(container);
}

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
    .querySelector(`[data-ui-control-path='${path}']`)!
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
  state.fetchStepUi.mockReset();
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

it('renders the form and mapping inside the container field group, like other slideout forms', () => {
  mount(true);

  const group = container.querySelector('.cp-container > craft-field-group');

  expect(group).not.toBeNull();
  expect(group!.querySelector('.ui-renderer')).not.toBeNull();
  expect(group!.querySelector('section')).not.toBeNull();
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
  mount(false);
  state.fetchStepUi.mockResolvedValue({ui: payload(), canMap: true});

  await state.refresh!({settings: {}});
  await nextTick();

  expect(mappingButton().disabled).toBe(false);
});

it('lets a read-only step’s mapping be viewed', () => {
  mount(true, step.type, {}, false);

  expect(mappingButton().disabled).toBe(false);
  expect(mappingButton().textContent!.trim()).toBe('View mapping');
});

it('disables the mapping button again when a refresh reports it unmappable', async () => {
  mount(true);
  state.fetchStepUi.mockResolvedValue({ui: payload(), canMap: false});

  await state.refresh!({settings: {}});
  await nextTick();

  expect(mappingButton().disabled).toBe(true);
});

it('shows the data source’s problem under its field when a refresh reports one', async () => {
  mount(true);
  state.fetchStepUi.mockResolvedValue({
    ui: payload(),
    canMap: false,
    sourceError: 'File “people.csv” does not exist.',
  });

  await state.refresh!({settings: {}});
  await nextTick();

  expect(state.errors).toEqual([
    {path: ['source'], messages: ['File “people.csv” does not exist.']},
  ]);
  expect(mappingButton().disabled).toBe(true);
});

it('shows the mapping section only once the chosen type’s form arrives', async () => {
  vi.useFakeTimers();
  const loading = deferred<{ui: UiPayload; canMap: boolean}>();
  state.fetchStepUi.mockReturnValue(loading.promise);
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

  loading.resolve({ui: payload(), canMap: true});
  await refreshing;
  await nextTick();

  expect(spinner()).toBeNull();
  expect(mappingSection()).not.toBeNull();
  expect(mappingButton().disabled).toBe(false);
});

it('doesn’t show the spinner for a refresh that keeps the type', async () => {
  vi.useFakeTimers();
  const loading = deferred<{ui: UiPayload; canMap: boolean}>();
  state.fetchStepUi.mockReturnValue(loading.promise);
  mount(false);

  const refreshing = state.refresh!({settings: {}});
  vi.advanceTimersByTime(200);
  await nextTick();

  expect(spinner()).toBeNull();

  loading.resolve({ui: payload(), canMap: true});
  await refreshing;
});

it('ignores a refresh response that a newer one has overtaken', async () => {
  const first = deferred<{ui: UiPayload; canMap: boolean}>();
  const second = deferred<{ui: UiPayload; canMap: boolean}>();
  state.fetchStepUi
    .mockReturnValueOnce(first.promise)
    .mockReturnValueOnce(second.promise);
  mount(false, null);

  state.values = {...state.values, type: step.type};
  const stale = state.refresh!({settings: {}});
  state.values = {...state.values, type: null};
  const latest = state.refresh!({settings: {}});

  second.resolve({ui: payload(), canMap: false});
  await latest;
  first.resolve({ui: payload(), canMap: true});
  await stale;
  await nextTick();

  expect(mappingSection()).toBeNull();
});

it('clears the spinner and keeps the mapping section hidden when a refresh fails', async () => {
  vi.useFakeTimers();
  const loading = deferred<{ui: UiPayload; canMap: boolean}>();
  state.fetchStepUi.mockReturnValue(loading.promise);
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

it('checks whether the step can be mapped once the data source field loses focus', async () => {
  const checking = deferred<{ui: UiPayload; canMap: boolean}>();
  state.fetchStepUi.mockReturnValue(checking.promise);
  mount(false);

  state.values = {...state.values, source: 'other.csv'};
  blur('["source"]');
  await nextTick();

  expect(state.fetchStepUi).toHaveBeenCalledOnce();
  expect(state.fetchStepUi.mock.calls[0]![0]).toMatchObject({
    source: 'other.csv',
  });
  expect(mappingButton().loading).toBe(true);

  checking.resolve({ui: payload(), canMap: true});
  await checking.promise;
  await nextTick();

  expect(mappingButton().loading).toBe(false);
  expect(mappingButton().disabled).toBe(false);
});

it('doesn’t check again when the data source hasn’t changed', async () => {
  mount(false);

  blur('["source"]');
  await nextTick();

  expect(state.fetchStepUi).not.toHaveBeenCalled();
});

it('doesn’t check when another field loses focus', async () => {
  mount(false);

  state.values = {...state.values, source: 'other.csv'};
  blur('["transformer"]');
  await nextTick();

  expect(state.fetchStepUi).not.toHaveBeenCalled();
});

it('lets a refresh that starts after a source check decide whether the step can be mapped', async () => {
  const checking = deferred<{ui: UiPayload; canMap: boolean}>();
  const refreshing = deferred<{ui: UiPayload; canMap: boolean}>();
  state.fetchStepUi
    .mockReturnValueOnce(checking.promise)
    .mockReturnValueOnce(refreshing.promise);
  mount(false);

  state.values = {...state.values, source: 'other.csv'};
  blur('["source"]');
  const refresh = state.refresh!({settings: {}});

  refreshing.resolve({ui: payload(), canMap: false});
  await refresh;
  checking.resolve({ui: payload(), canMap: true});
  await checking.promise;
  await nextTick();

  expect(mappingButton().disabled).toBe(true);
  expect(mappingButton().loading).toBe(false);
});

it('keeps the last reported state when the source check fails', async () => {
  const checking = deferred<{ui: UiPayload; canMap: boolean}>();
  state.fetchStepUi.mockReturnValue(checking.promise);
  mount(true);

  state.values = {...state.values, source: 'missing.csv'};
  blur('["source"]');
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

it('shows a data source that can’t be read under its field, not under the button', async () => {
  state.openStepMapping.mockRejectedValue(
    new StepMappingUnavailableError(
      'The data in “people.csv” couldn’t be read.',
      'source'
    )
  );
  mount(true);

  mappingButton().dispatchEvent(new Event('click'));
  await nextTick();
  await nextTick();

  expect(state.errors).toEqual([
    {
      path: ['source'],
      messages: ['The data in “people.csv” couldn’t be read.'],
    },
  ]);
  expect(mappingSection()!.querySelector('[role="alert"]')).toBeNull();
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
    response: {data: {errors: {source: ['Source must be provided.']}}},
  });

  const onSave = state.layout.mock.calls.at(-1)![0].onSave;
  await onSave();
  await nextTick();

  expect(state.context.apply).not.toHaveBeenCalled();
  expect(state.errors).toEqual([
    {path: ['source'], messages: ['Source must be provided.']},
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

it('isn’t dirty when folding the form back in changes nothing', async () => {
  mount(true);
  // PHP sends an empty tree as `[]`, and the form reports its keys in another order.
  state.context.step.settings = [];
  state.values = {
    settings: {},
    source: step.source,
    type: step.type,
    batchSize: '',
  };

  state.emitChange!();
  await nextTick();

  expect(state.layout.mock.calls.at(-1)![0].form.isDirty).toBe(false);
});

it('is dirty once a setting really changes', async () => {
  mount(true);
  state.values = {...state.values, source: 'other.csv'};

  state.emitChange!();
  await nextTick();

  expect(state.layout.mock.calls.at(-1)![0].form.isDirty).toBe(true);
});

it('isn’t dirty after opening the mapping and canceling it', async () => {
  state.openStepMapping.mockResolvedValue(true);
  mount(true, step.type, {entryType: 'blog'});

  mappingButton().dispatchEvent(new Event('click'));
  await nextTick();
  await nextTick();

  expect(state.openStepMapping).toHaveBeenCalled();
  expect(state.layout.mock.calls.at(-1)![0].form.isDirty).toBe(false);
});
