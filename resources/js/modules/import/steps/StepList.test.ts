import {createApp, nextTick, reactive, ref} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {StepPayload} from '@/modules/import/mapping/types';
import {cloneStep, cloneSteps} from './clone';
import StepList from './StepList.vue';

const state = vi.hoisted(() => ({
  openStepSlideout: vi.fn(),
}));

vi.mock('./step-slideout', () => ({
  openStepSlideout: state.openStepSlideout,
}));

const urls = {
  settingsUrl: '/actions/import/step-settings',
  mappingUrl: '/actions/import/step-mapping',
  nestedColsUrl: '/actions/import/nested-mapping-cols',
};

const importerTypes = [
  {value: 'CraftCms\\Cms\\Entry\\Import\\EntryImporter', label: 'Entries'},
  {value: 'CraftCms\\Cms\\Asset\\Import\\AssetImporter', label: 'Assets'},
];

function step(overrides: Partial<StepPayload> = {}): StepPayload {
  return {
    uid: 'step-1',
    type: importerTypes[0]!.value,
    file: 'people.csv',
    transformer: null,
    batchSize: null,
    settings: {},
    ...overrides,
  };
}

let app: ReturnType<typeof createApp> | null;
let container: HTMLElement;
let steps: ReturnType<typeof ref<StepPayload[]>>;

function mount(
  initial: StepPayload[] = [step()],
  props: {editable?: boolean; errors?: Record<string, string[]>} = {}
) {
  // a ref, as the edit screen holds these — reading an element back out of it hands
  // the list a reactive proxy, which is the case that used to break cloning
  steps = ref<StepPayload[]>(initial);

  app = createApp({
    components: {StepList},
    template:
      '<StepList v-model="steps" :urls="urls" :editable="editable" :importer-types="importerTypes" :errors="errors" />',
    setup: () => ({
      steps,
      urls,
      importerTypes,
      editable: props.editable ?? true,
      errors: props.errors ?? {},
    }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
}

/** The rows' buttons, in DOM order: [Edit, Delete] per row. */
function rowButtons(index = 0): HTMLElement[] {
  const row = [...container.querySelectorAll('li')][index]!;

  return [...row.querySelectorAll<HTMLElement>('craft-button')];
}

function addButton(): HTMLElement {
  return [...container.querySelectorAll<HTMLElement>('craft-button')].at(-1)!;
}

beforeEach(() => {
  state.openStepSlideout.mockReset();
  state.openStepSlideout.mockResolvedValue(true);
  vi.stubGlobal('confirm', vi.fn().mockReturnValue(true));
  app = null;
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app?.unmount();
  container.remove();
  vi.unstubAllGlobals();
});

it('clones a step held in reactive state', () => {
  // `structuredClone` throws `DataCloneError` on a Proxy, which is what a step read
  // back out of the list's model always is.
  const proxied = reactive(step());

  expect(() => cloneStep(proxied)).not.toThrow();
  expect(cloneStep(proxied)).toEqual(step());
  expect(cloneSteps(reactive([step()]))).toEqual([step()]);
});

it('opens the slideout for a step read out of its reactive model', async () => {
  mount();

  rowButtons()[0]!.dispatchEvent(new Event('click'));
  await nextTick();

  expect(state.openStepSlideout).toHaveBeenCalledOnce();
  expect(state.openStepSlideout.mock.calls[0]![0]).toMatchObject({
    urls,
    editable: true,
  });
  expect(state.openStepSlideout.mock.calls[0]![0].step.uid).toBe('step-1');
});

it('summarizes a step by its importer type and file', () => {
  mount();

  expect(container.querySelector('li')!.textContent).toContain(
    '1. Entries — people.csv'
  );
});

it('appends a step the add slideout hands back', async () => {
  mount([]);

  addButton().dispatchEvent(new Event('click'));
  await nextTick();

  const {step: draft, apply} = state.openStepSlideout.mock.calls[0]![0];
  apply({...draft, type: importerTypes[1]!.value, file: 'assets.csv'});
  await nextTick();

  expect(steps.value).toHaveLength(1);
  expect(steps.value![0]!.file).toBe('assets.csv');
  expect(steps.value![0]!.uid).toBe(draft.uid);
});

it('replaces the edited step in place, keeping its uid', async () => {
  mount([step(), step({uid: 'step-2', file: 'other.csv'})]);

  rowButtons(1)[0]!.dispatchEvent(new Event('click'));
  await nextTick();

  state.openStepSlideout.mock.calls[0]![0].apply(
    step({uid: 'ignored', file: 'edited.csv'})
  );
  await nextTick();

  expect(steps.value!.map((s) => s.uid)).toEqual(['step-1', 'step-2']);
  expect(steps.value![1]!.file).toBe('edited.csv');
});

it('removes a step once the deletion is confirmed', async () => {
  mount([step(), step({uid: 'step-2'})]);

  rowButtons(0)[1]!.dispatchEvent(new Event('click'));
  await nextTick();

  expect(steps.value!.map((s) => s.uid)).toEqual(['step-2']);
});

it('keeps a step the user declines to delete', async () => {
  vi.stubGlobal('confirm', vi.fn().mockReturnValue(false));
  mount([step(), step({uid: 'step-2'})]);

  rowButtons(0)[1]!.dispatchEvent(new Event('click'));
  await nextTick();

  expect(steps.value!.map((s) => s.uid)).toEqual(['step-1', 'step-2']);
});

it('reorders a step when its handle asks to move', async () => {
  mount([step(), step({uid: 'step-2'})]);

  container
    .querySelector('craft-reorder-button')!
    .dispatchEvent(new CustomEvent('reorder', {detail: {direction: 'down'}}));
  await nextTick();

  expect(steps.value!.map((s) => s.uid)).toEqual(['step-2', 'step-1']);
});

it('ignores a reorder that would move a step off either end', async () => {
  mount([step(), step({uid: 'step-2'})]);

  container
    .querySelector('craft-reorder-button')!
    .dispatchEvent(new CustomEvent('reorder', {detail: {direction: 'up'}}));
  await nextTick();

  expect(steps.value!.map((s) => s.uid)).toEqual(['step-1', 'step-2']);
});

it('shows a server error on the step it belongs to', () => {
  mount([step(), step({uid: 'step-2'})], {
    errors: {'steps.step-2.file': ['File does not exist.']},
  });

  const rows = [...container.querySelectorAll('li')];

  expect(rows[0]!.querySelector('.error')).toBeNull();
  expect(rows[1]!.querySelector('.error')!.textContent).toContain(
    'File does not exist.'
  );
});

it('shows an error about the list itself when there are no steps', () => {
  // `steps` with nothing after it is the list's own error, not a row's
  mount([], {errors: {steps: ['An import needs at least one step.']}});

  expect(container.querySelector('.error-list')!.textContent).toContain(
    'An import needs at least one step.'
  );
});

it('keeps a list error and a row error apart', () => {
  mount([step()], {
    errors: {
      steps: ['An import needs at least one step.'],
      'steps.step-1.file': ['File does not exist.'],
    },
  });

  expect(container.querySelector('.error-list')!.textContent).toContain(
    'An import needs at least one step.'
  );
  expect(container.querySelector('.error-list')!.textContent).not.toContain(
    'File does not exist.'
  );
  expect(container.querySelector('li .error')!.textContent).toContain(
    'File does not exist.'
  );
});

it('reports a slideout that fails to open instead of doing nothing', async () => {
  state.openStepSlideout.mockRejectedValue(new Error('Request failed.'));
  mount([]);

  addButton().dispatchEvent(new Event('click'));
  await nextTick();
  await nextTick();

  expect(container.querySelector('.error-list')!.textContent).toContain(
    'Request failed.'
  );
});
