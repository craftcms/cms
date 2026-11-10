import {createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {UiPayload} from '@/modules/ui/types';
import type {ImportStep, MappingValues} from '@/modules/import/mapping/types';
import StepSlideout from './StepSlideout.vue';

const state = vi.hoisted(() => ({
  layout: vi.fn(),
  fetchStepUi: vi.fn(),
  validateStep: vi.fn(),
  errorHandler: vi.fn(),
  context: null as any,
  values: {} as Record<string, unknown>,
  refresh: null as ((values: unknown) => Promise<UiPayload>) | null,
  emitChange: null as (() => void) | null,
  errors: null as UiPayload['errors'] | null,
  mapping: null as {
    canMap: boolean;
    editable: boolean;
    step: ImportStep;
  } | null,
  changeMapping: null as ((values: Partial<MappingValues>) => void) | null,
}));

vi.mock('@/common/composables/useAppLayout', () => ({
  useAppLayout: (options: unknown) => {
    state.layout(options instanceof Function ? options() : options);
  },
}));

vi.mock('@/common/slideouts', () => ({
  useSlideout: () => null,
}));

// Stubbed so the test can read what the panel tells the mapping, and edit it.
vi.mock('./InlineStepMapping.vue', () => ({
  default: {
    name: 'InlineStepMapping',
    props: ['step', 'editable', 'canMap'],
    emits: ['change'],
    setup(props: any, {emit}: any) {
      state.changeMapping = (values) => emit('change', values);

      return () => {
        state.mapping = {
          canMap: props.canMap,
          editable: props.editable,
          step: props.step,
        };

        return h('section', {class: 'inline-step-mapping'});
      };
    },
  },
}));

vi.mock('./step-slideout', () => ({
  takeStepSlideoutContext: () => state.context,
  fetchStepUi: state.fetchStepUi,
  validateStep: state.validateStep,
}));

// Stubbed so the test can drive the panel's refresh callback and reported values. It renders
// the mapping slot the server's form always includes.
vi.mock('@/modules/ui/UiRenderer.vue', () => ({
  default: {
    name: 'UiRenderer',
    props: ['payload', 'refresh', 'errors'],
    emits: ['change'],
    setup(props: any, {expose, emit, slots}: any) {
      state.refresh = props.refresh;
      state.emitChange = () => emit('change', {}, state.values);
      expose({
        currentValues: () => state.values,
        // a refreshable change refreshes the form, as the real renderer does
        setValues: () => void props.refresh?.(state.values)?.catch(() => {}),
      });

      return () => {
        state.errors = props.errors;

        return h('div', {class: 'ui-renderer'}, [
          h('input', {'data-ui-control-path': '["source"]'}),
          slots.mapping?.(),
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

const summary = {
  settings: [],
  destinationLabels: {title: {label: 'Title', handle: 'title'}},
};

/** What the step form endpoint sends back. */
function response(canMap: boolean, sourceError: string | null = null) {
  return {ui: payload(), canMap, sourceError, summary};
}

const step: ImportStep = {
  uid: 'step-1',
  type: 'CraftCms\\Cms\\Entry\\Import\\EntryImporter',
  source: 'people.csv',
  transformer: null,
  batchSize: null,
  enabled: true,
  settings: {},
};

let app: ReturnType<typeof createApp>;
let container: HTMLElement;

function mount(
  canMap: boolean,
  type: string | null = step.type,
  formSettings: Record<string, unknown> = {},
  editable = true,
  settings: ImportStep['settings'] = {}
) {
  state.values = {type, source: step.source, settings: formSettings};
  state.context = {
    step: {...structuredClone(step), type, settings},
    payload: payload(),
    canMap,
    sourceError: null,
    summary,
    editable,
    apply: vi.fn(),
  };

  app = createApp(StepSlideout, {contextId: 'ctx', title: 'Edit step'});
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.config.errorHandler = state.errorHandler;
  app.mount(container);
}

/** Whether the panel last told the mapping the step can be mapped. */
function canMap(): boolean | undefined {
  return state.mapping?.canMap;
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
  state.errorHandler.mockReset();
  state.mapping = null;
  state.changeMapping = null;
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
  expect(group!.querySelector('.inline-step-mapping')).not.toBeNull();
});

it.each([true, false])(
  'tells the mapping whether the step can be mapped (%s)',
  (mappable) => {
    mount(mappable);

    expect(canMap()).toBe(mappable);
  }
);

it('tells the mapping whether the step can be edited', () => {
  mount(true, step.type, {}, false, {map: {title: 'name'}});

  expect(state.mapping!.editable).toBe(false);
  expect(state.mapping!.step.settings).toMatchObject({map: {title: 'name'}});
});

it.each([true, false])(
  'follows a refresh that reports whether the step can be mapped (%s)',
  async (mappable) => {
    mount(!mappable);
    state.fetchStepUi.mockResolvedValue(response(mappable));

    await state.refresh!({settings: {}});
    await nextTick();

    expect(canMap()).toBe(mappable);
  }
);

it('shows the data source’s problem under its field when a refresh reports one', async () => {
  mount(true);
  state.fetchStepUi.mockResolvedValue(
    response(false, 'File “people.csv” does not exist.')
  );

  await state.refresh!({settings: {}});
  await nextTick();

  expect(state.errors).toEqual([
    {path: ['source'], messages: ['File “people.csv” does not exist.']},
  ]);
  expect(canMap()).toBe(false);
});

it('shows a spinner while the chosen type’s form loads, then lets the step be mapped', async () => {
  vi.useFakeTimers();
  const loading = deferred<{ui: UiPayload; canMap: boolean}>();
  state.fetchStepUi.mockReturnValue(loading.promise);
  mount(false, null);

  state.values = {...state.values, type: step.type};
  state.emitChange!();
  const refreshing = state.refresh!({settings: {}});
  await nextTick();

  expect(spinner()).toBeNull();

  vi.advanceTimersByTime(200);
  await nextTick();

  expect(spinner()).not.toBeNull();
  expect(canMap()).toBe(false);

  loading.resolve(response(true));
  await refreshing;
  await nextTick();

  expect(spinner()).toBeNull();
  expect(canMap()).toBe(true);
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

  loading.resolve(response(true));
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

  second.resolve(response(false));
  await latest;
  first.resolve(response(true));
  await stale;
  await nextTick();

  expect(canMap()).toBe(false);
});

it('clears the spinner and leaves the step unmappable when a refresh fails', async () => {
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
  expect(canMap()).toBe(false);
});

it('refreshes the form to check whether the step can be mapped once the data source field loses focus', async () => {
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

  checking.resolve(response(true));
  await checking.promise;
  await nextTick();

  expect(canMap()).toBe(true);
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

  refreshing.resolve(response(false));
  await refresh;
  checking.resolve(response(true));
  await checking.promise;
  await nextTick();

  expect(canMap()).toBe(false);
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

  expect(canMap()).toBe(true);
  expect(state.errorHandler).not.toHaveBeenCalled();
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
    expect.objectContaining({uid: 'step-1'}),
    {
      settings: [],
      destinationLabels: {title: {label: 'Title', handle: 'title'}},
    }
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

it('applies the mapping as it’s edited in place, with the form’s settings', async () => {
  mount(true, step.type, {entryType: 'blog'});
  state.validateStep.mockResolvedValue(undefined);

  state.changeMapping!({map: {title: 'name'}});
  await nextTick();

  expect(state.layout.mock.calls.at(-1)![0].form.isDirty).toBe(true);

  await state.layout.mock.calls.at(-1)![0].onSave();

  expect(state.context.apply).toHaveBeenCalledWith(
    expect.objectContaining({
      settings: expect.objectContaining({
        entryType: 'blog',
        map: {title: 'name'},
      }),
    }),
    expect.anything()
  );
});
