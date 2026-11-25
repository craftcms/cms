import {createApp, h} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {ImportStep} from '@/modules/import/mapping/types';
import {
  StepSaveError,
  type StepSlideoutContext,
} from '@/modules/import/steps/step-slideout';
import StepEdit from './StepEdit.vue';

const state = vi.hoisted(() => ({
  visit: vi.fn(),
  context: null as StepSlideoutContext | null,
}));

vi.mock('@inertiajs/vue3', () => ({router: {visit: state.visit}}));

// Stubbed so the test can save through the context the page hands the form.
vi.mock('@/modules/import/steps/StepSlideout.vue', () => ({
  default: {
    props: ['context', 'title'],
    setup(props: {context: StepSlideoutContext}) {
      state.context = props.context;

      return () => h('div');
    },
  },
}));

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

beforeEach(() => {
  state.visit.mockReset();
  container = document.createElement('div');
  document.body.append(container);
  app = createApp(StepEdit, {
    title: 'Edit step',
    planUid: 'plan-1',
    step,
    stepUi: {
      ui: {},
      canMap: true,
      sourceError: null,
      summary: {settings: [], destinationLabels: {}},
    },
    editable: true,
    submit: {url: '/actions/import/save-step', method: 'post'},
  });
  app.mount(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
});

/** Saves the step, and finishes the visit the way the server answered it. */
function save(answer: (options: any) => void): Promise<void> {
  const saving = state.context!.apply(step, {
    settings: [],
    destinationLabels: {},
  }) as Promise<void>;
  const options = state.visit.mock.calls.at(-1)![1];
  answer(options);
  options.onFinish?.();

  return saving;
}

it('saves the step into its plan, and keeps the form as it is if that fails', async () => {
  await expect(save((options) => options.onSuccess?.())).resolves.toBe(
    undefined
  );

  expect(state.visit.mock.calls[0]![1]).toMatchObject({
    method: 'post',
    data: {planUid: 'plan-1', step},
    preserveState: 'errors',
  });
});

it('rejects with the plan’s errors when it can’t be saved, rather than resolving as saved', async () => {
  const errors = {'steps.step-2.source': 'Source must be provided.'};
  const saving = save((options) => options.onError?.(errors));

  await expect(saving).rejects.toBeInstanceOf(StepSaveError);
  await expect(saving).rejects.toHaveProperty('errors', errors);
});
