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
  context: null as any,
  refresh: null as ((values: unknown) => Promise<FormPayload>) | null,
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
// carries an updated `canMap` back from the server.
vi.mock('@/modules/forms/FormRenderer.vue', () => ({
  default: {
    name: 'FormRenderer',
    props: ['payload', 'refresh', 'errors'],
    setup(props: any, {expose}: any) {
      state.refresh = props.refresh;
      expose({currentValues: () => ({settings: {}})});

      return () => {
        state.errors = props.errors;

        return h('div', {class: 'form-renderer'});
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

function mount(canMap: boolean) {
  state.context = {
    step: structuredClone(step),
    payload: payload(),
    canMap,
    urls,
    editable: true,
    apply: vi.fn(),
  };

  app = createApp(StepSlideout, {contextId: 'ctx', title: 'Edit step'});
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
}

/** The Mapping section only exists while the step can be mapped. */
function mappingSection(): HTMLElement | null {
  return container.querySelector('section');
}

beforeEach(() => {
  state.layout.mockClear();
  state.fetchStepForm.mockReset();
  state.validateStep.mockReset();
  state.openStepMapping.mockReset();
  state.refresh = null;
  state.errors = null;
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
});

it('hides the mapping section while the step can’t be mapped', () => {
  mount(false);

  expect(mappingSection()).toBeNull();
});

it('shows the mapping section once the step can be mapped', () => {
  mount(true);

  expect(mappingSection()).not.toBeNull();
  expect(mappingSection()!.textContent).toContain('Mapping');
});

it('reveals the mapping section when a refresh reports the step as mappable', async () => {
  // An element importer only resolves its field layout once an entry type is chosen, which
  // reaches the panel as a refresh — the section has to appear without reopening.
  mount(false);
  state.fetchStepForm.mockResolvedValue({form: payload(), canMap: true});

  await state.refresh!({settings: {}});
  await nextTick();

  expect(mappingSection()).not.toBeNull();
});

it('hides the mapping section again when a refresh reports it unmappable', async () => {
  mount(true);
  state.fetchStepForm.mockResolvedValue({form: payload(), canMap: false});

  await state.refresh!({settings: {}});
  await nextTick();

  expect(mappingSection()).toBeNull();
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
