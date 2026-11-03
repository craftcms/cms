import {createApp, defineComponent, h, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {ImportStep} from '@/modules/import/mapping/types';

const state = vi.hoisted(() => ({
  layout: null as null | (() => any),
  validateStep: vi.fn(),
  slideout: {saved: vi.fn(), close: vi.fn()},
  openSlideoutWith: vi.fn(),
  editor: null as null | {props: any; emit: (step: ImportStep) => void},
}));

vi.mock('@/common/composables/useAppLayout', () => ({
  useAppLayout: (options: () => any) => {
    state.layout = options;
  },
}));

vi.mock('@/common/slideouts', () => ({
  openSlideoutWith: state.openSlideoutWith,
  useSlideout: () => state.slideout,
}));

vi.mock('./step-slideout', async (importOriginal) => ({
  ...(await importOriginal<typeof import('./step-slideout')>()),
  validateStep: state.validateStep,
}));

// stands in for the editor, so a test can report an edit the way it would
vi.mock('./StepEditor.vue', async () => {
  const {defineComponent, h} = await import('vue');

  return {
    default: defineComponent({
      props: ['step', 'editable', 'errors'],
      emits: ['update:step'],
      setup(props, {emit}) {
        state.editor = {props, emit: (step) => emit('update:step', step)};

        return () => h('div');
      },
    }),
  };
});

const {openStepSlideout} = await import('./step-slideout');
const {default: StepSlideout} = await import('./StepSlideout.vue');

function step(overrides: Partial<ImportStep> = {}): ImportStep {
  return {
    uid: 'step-1',
    type: 'CraftCms\\Cms\\Entry\\Import\\EntryImporter',
    source: 'people.csv',
    transformer: null,
    batchSize: null,
    enabled: true,
    settings: {},
    ...overrides,
  };
}

let app: ReturnType<typeof createApp> | null = null;
let apply: ReturnType<typeof vi.fn<(step: ImportStep) => void>>;

/** Opens the panel the way the step list does, and renders what it would show. */
async function mount(editable = true): Promise<void> {
  apply = vi.fn();

  await openStepSlideout({
    step: step(),
    editable,
    errors: [],
    editUrl: null,
    title: 'Edit step 1',
    opener: null,
    apply,
  });

  const [, props] = state.openSlideoutWith.mock.lastCall!;
  app = createApp(defineComponent({render: () => h(StepSlideout, props)}));
  app.mount(document.createElement('div'));
}

beforeEach(() => {
  state.validateStep.mockReset().mockResolvedValue(undefined);
  state.openSlideoutWith.mockReset().mockReturnValue({});
  state.slideout.saved.mockReset();
  state.slideout.close.mockReset();
});

afterEach(() => {
  app?.unmount();
  app = null;
});

it('hands the edited step back to the plan once it checks out, and closes', async () => {
  await mount();

  state.editor!.emit(step({source: 'places.csv'}));
  await state.layout!().onSave();

  expect(state.validateStep).toHaveBeenCalledWith(step({source: 'places.csv'}));
  expect(apply).toHaveBeenCalledWith(step({source: 'places.csv'}));
  expect(state.slideout.close).toHaveBeenCalledWith({force: true});
});

it('keeps a step that doesn’t check out open, with the server’s errors', async () => {
  state.validateStep.mockRejectedValue({
    response: {data: {errors: {'settings.section': ['Choose a section.']}}},
  });
  await mount();

  await state.layout!().onSave();
  await nextTick();

  expect(apply).not.toHaveBeenCalled();
  expect(state.slideout.close).not.toHaveBeenCalled();
  expect(state.editor!.props.errors).toEqual([
    {path: ['settings', 'section'], messages: ['Choose a section.']},
  ]);
});

it('knows when the step has changes to lose', async () => {
  await mount();

  expect(state.layout!().form.isDirty).toBe(false);

  state.editor!.emit(step({source: 'places.csv'}));
  await nextTick();

  expect(state.layout!().form.isDirty).toBe(true);
});

it('leaves out Apply for a step that can’t be edited', async () => {
  await mount(false);

  expect(state.layout!().form).toBeNull();
});
