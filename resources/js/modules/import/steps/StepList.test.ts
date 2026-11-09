import {createApp, h, nextTick, reactive, ref} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {cloneStep, cloneSteps} from '@/modules/import/mapping/paths';
import type {ImportStep} from '@/modules/import/mapping/types';
import type {ActionItemButton} from '@/common/types';
import StepList from './StepList.vue';

const state = vi.hoisted(() => ({
  openStepSlideout: vi.fn(),
  openStepMapping: vi.fn(),
}));

vi.mock('./step-slideout', () => ({
  openStepSlideout: state.openStepSlideout,
}));

vi.mock('./step-mapping', () => ({
  openStepMapping: state.openStepMapping,
}));

/** Renders a menu's label and hands its actions to the test through the element. */
vi.mock('@/common/components/ActionMenu.vue', () => ({
  default: {
    props: {label: String, actions: Array},
    setup(props: {label: string; actions: ActionItemButton[]}) {
      return () =>
        h('step-menu', {
          'aria-label': props.label,
          '.actions': props.actions,
        });
    },
  },
}));

const importerTypes = [
  {value: 'CraftCms\\Cms\\Entry\\Import\\EntryImporter', label: 'Entries'},
  {value: 'CraftCms\\Cms\\Asset\\Import\\AssetImporter', label: 'Assets'},
];

function step(overrides: Partial<ImportStep> = {}): ImportStep {
  return {
    uid: 'step-1',
    type: importerTypes[0]!.value,
    source: 'people.csv',
    transformer: null,
    batchSize: null,
    enabled: true,
    settings: {},
    ...overrides,
  };
}

let app: ReturnType<typeof createApp> | null;
let container: HTMLElement;
let steps: ReturnType<typeof ref<ImportStep[]>>;

function mount(
  initial: ImportStep[] = [step()],
  props: {
    editable?: boolean;
    errors?: Record<string, string[]>;
    stepSummaries?: Record<string, unknown>;
  } = {}
) {
  steps = ref<ImportStep[]>(initial);

  app = createApp({
    components: {StepList},
    template:
      '<StepList v-model="steps" :editable="editable" :importer-types="importerTypes" :errors="errors" :step-summaries="stepSummaries" />',
    setup: () => ({
      steps,
      importerTypes,
      editable: props.editable ?? true,
      errors: props.errors ?? {},
      stepSummaries: props.stepSummaries ?? {},
    }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
}

function rowButtons(index = 0): HTMLElement[] {
  const row = [...container.querySelectorAll('li')][index]!;

  return [...row.querySelectorAll<HTMLElement>('craft-button')];
}

function rowActions(index = 0): ActionItemButton[] {
  const row = [...container.querySelectorAll('li')][index]!;
  const menu = row.querySelector<HTMLElement & {actions: ActionItemButton[]}>(
    'step-menu'
  );

  return menu?.actions ?? [];
}

function action(label: string, index = 0): ActionItemButton {
  return rowActions(index).find((item) => item.label === label)!;
}

function addButton(): HTMLElement {
  return [...container.querySelectorAll<HTMLElement>('craft-button')].at(-1)!;
}

function isLoading(button: HTMLElement): boolean {
  return (button as HTMLElement & {loading: boolean}).loading;
}

function deferred<T>() {
  let resolve!: (value: T) => void;
  const promise = new Promise<T>((r) => (resolve = r));

  return {promise, resolve};
}

beforeEach(() => {
  state.openStepSlideout.mockReset();
  state.openStepMapping.mockReset().mockResolvedValue(true);
  state.openStepSlideout.mockResolvedValue(true);
  vi.stubGlobal('confirm', vi.fn().mockReturnValue(true));
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ok: false}));
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
  // structuredClone throws on a Proxy, which is what the list hands back.
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
    editable: true,
  });
  expect(state.openStepSlideout.mock.calls[0]![0].step.uid).toBe('step-1');
});

/** A card's settings rows, as label and value. */
function details(index = 0): string[][] {
  const card = [...container.querySelectorAll('li')][index]!;

  return [...card.querySelectorAll('.step-card__settings dt')].map((dt) => [
    dt.textContent!.trim(),
    dt.nextElementSibling!.textContent!.trim(),
  ]);
}

/** A card's type and source, from its rail. */
function identity(index = 0): string[] {
  const card = [...container.querySelectorAll('li')][index]!;

  return [
    card.querySelector('.step-card__number')!.textContent!.trim(),
    card
      .querySelector('[id^="step-label-"]')!
      .textContent!.replace(/\s+/g, ' ')
      .trim(),
    card.querySelector('.step-card__rail code')?.textContent!.trim() ?? '',
  ];
}

it('numbers each step and lists its importer type and source', () => {
  mount([step(), step({uid: 'step-2', source: 'more.csv'})]);

  expect(identity(0)).toEqual(['1', 'Step 1: Entries', 'people.csv']);
  expect(identity(1)).toEqual(['2', 'Step 2: Entries', 'more.csv']);
});

it('lists the settings the server summed up for a step', () => {
  mount([step()], {
    stepSummaries: {
      'step-1': {
        settings: [
          {label: 'Site', value: 'Craft Test Site'},
          {label: 'Section', value: 'Guides'},
        ],
        destinationLabels: {},
      },
    },
  });

  expect(details()).toEqual([
    ['Site', 'Craft Test Site'],
    ['Section', 'Guides'],
  ]);
});

it('says what a matching step does with rows that match', () => {
  mount([
    step({
      settings: {matchCriteria: {slug: '1'}, matchMode: 'updateOnly'},
    }),
    step({uid: 'step-2', settings: {matchMode: 'updateOnly'}}),
  ]);

  expect(details(0)).toEqual([['If matched', 'Update existing only']]);
  // with nothing to match on, every row is new, so there's nothing to say
  expect(details(1)).toEqual([]);
});

it('takes a step’s new summary from its slideout once applied', async () => {
  mount();

  rowButtons()[0]!.click();
  await nextTick();

  state.openStepSlideout.mock.calls[0]![0].apply(step({source: 'new.csv'}), {
    settings: [{label: 'Site', value: 'Other Site'}],
    destinationLabels: {},
  });
  await nextTick();

  expect(identity()[2]).toBe('new.csv');
  expect(details()).toEqual([['Site', 'Other Site']]);
});

it('opens a step’s mapping straight from its mapping section', async () => {
  mount([step({settings: {site: 'default'}})]);

  const mappingButton = container.querySelector<HTMLElement>(
    'li .step-card__mapping craft-button'
  )!;
  mappingButton.click();
  await nextTick();

  expect(state.openStepMapping).toHaveBeenCalledOnce();
  expect(state.openStepMapping.mock.calls[0]![0]).toMatchObject({
    step: {uid: 'step-1'},
    editable: true,
    opener: mappingButton,
  });

  state.openStepMapping.mock.calls[0]![0].apply({map: {title: 'name'}});
  await nextTick();

  expect(steps.value![0]!.settings).toEqual({
    site: 'default',
    map: {title: 'name'},
  });
  expect(
    container.querySelector('.step-card__mapping h3')!.textContent
  ).toContain('1 field');
});

it('names each row’s edit button and action menu after its step', () => {
  mount([step(), step({uid: 'step-2', source: 'people.csv'})]);

  const row = [...container.querySelectorAll('li')][1]!;

  expect(rowButtons(1)[0]!.getAttribute('aria-label')).toBe(
    'Edit 2. Entries — people.csv'
  );
  expect(row.querySelector('step-menu')!.getAttribute('aria-label')).toBe(
    'Actions for 2. Entries — people.csv'
  );
});

it('disables a step and enables it again', async () => {
  mount([step(), step({uid: 'step-2'})]);

  action('Disable step').onClick!(new Event('click'));
  await nextTick();

  expect(steps.value!.map((s) => s.enabled)).toEqual([false, true]);
  expect(
    container
      .querySelector('li [id^="step-label-"] craft-badge')!
      .textContent!.trim()
  ).toBe('Disabled');

  action('Enable step').onClick!(new Event('click'));
  await nextTick();

  expect(steps.value!.map((s) => s.enabled)).toEqual([true, true]);
});

it('only lets a step be disabled while another stays enabled', () => {
  mount([step(), step({uid: 'step-2', enabled: false})]);

  expect(action('Disable step', 0).disabled).toBe(true);
  expect(action('Enable step', 1).disabled).toBe(false);
});

it('leaves the action menu off a read-only step', () => {
  mount([step()], {editable: false});

  expect(container.querySelector('step-menu')).toBeNull();
});

it('names a read-only row’s button for viewing', () => {
  mount([step()], {editable: false});

  expect(rowButtons()[0]!.getAttribute('aria-label')).toBe(
    'View 1. Entries — people.csv'
  );
});

it('appends a step the add slideout hands back', async () => {
  mount([]);

  addButton().dispatchEvent(new Event('click'));
  await nextTick();

  const {step: draft, apply} = state.openStepSlideout.mock.calls[0]![0];
  apply({...draft, type: importerTypes[1]!.value, source: 'assets.csv'});
  await nextTick();

  expect(steps.value).toHaveLength(1);
  expect(steps.value![0]!.source).toBe('assets.csv');
  expect(steps.value![0]!.uid).toBe(draft.uid);
});

it('replaces the edited step in place, keeping its uid', async () => {
  mount([step(), step({uid: 'step-2', source: 'other.csv'})]);

  rowButtons(1)[0]!.dispatchEvent(new Event('click'));
  await nextTick();

  state.openStepSlideout.mock.calls[0]![0].apply(
    step({uid: 'ignored', source: 'edited.csv'})
  );
  await nextTick();

  expect(steps.value!.map((s) => s.uid)).toEqual(['step-1', 'step-2']);
  expect(steps.value![1]!.source).toBe('edited.csv');
});

it('removes a step once the deletion is confirmed', async () => {
  mount([step(), step({uid: 'step-2'})]);

  action('Delete step').onClick!(new Event('click'));
  await nextTick();

  expect(steps.value!.map((s) => s.uid)).toEqual(['step-2']);
});

it('keeps a step the user declines to delete', async () => {
  vi.stubGlobal('confirm', vi.fn().mockReturnValue(false));
  mount([step(), step({uid: 'step-2'})]);

  action('Delete step').onClick!(new Event('click'));
  await nextTick();

  expect(steps.value!.map((s) => s.uid)).toEqual(['step-1', 'step-2']);
});

it('reorders a step when its handle asks to move', async () => {
  mount([step(), step({uid: 'step-2'})]);

  container
    .querySelector('craft-reorder-button')!
    .dispatchEvent(
      new CustomEvent('craft-reorder', {detail: {direction: 'down'}})
    );
  await nextTick();

  expect(steps.value!.map((s) => s.uid)).toEqual(['step-2', 'step-1']);
});

it('ignores a reorder that would move a step off either end', async () => {
  mount([step(), step({uid: 'step-2'})]);

  container
    .querySelector('craft-reorder-button')!
    .dispatchEvent(
      new CustomEvent('craft-reorder', {detail: {direction: 'up'}})
    );
  await nextTick();

  expect(steps.value!.map((s) => s.uid)).toEqual(['step-1', 'step-2']);
});

it('shows a server error on the step it belongs to', () => {
  mount([step(), step({uid: 'step-2'})], {
    errors: {'steps.step-2.source': ['File does not exist.']},
  });

  const rows = [...container.querySelectorAll('li')];

  expect(rows[0]!.querySelector('.error')).toBeNull();
  expect(rows[1]!.querySelector('.error')!.textContent).toContain(
    'The step “Entries — people.csv” is invalid. Edit it to fix the problem.'
  );
});

it('describes an invalid step’s edit button with its error', () => {
  mount([step(), step({uid: 'step-2'})], {
    errors: {'steps.step-2.source': ['File does not exist.']},
  });

  const describedBy = rowButtons(1)[0]!.getAttribute('aria-describedby');

  expect(rowButtons(0)[0]!.hasAttribute('aria-describedby')).toBe(false);
  expect(describedBy).not.toBeNull();
  expect(container.querySelector(`#${describedBy}`)!.textContent).toContain(
    'The step “Entries — people.csv” is invalid. Edit it to fix the problem.'
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

it('shows a spinner on the add button while its slideout opens', async () => {
  const opening = deferred<boolean>();
  state.openStepSlideout.mockReturnValue(opening.promise);
  mount([]);

  addButton().dispatchEvent(new Event('click'));
  await nextTick();

  expect(isLoading(addButton())).toBe(true);

  opening.resolve(true);
  await opening.promise;
  await nextTick();

  expect(isLoading(addButton())).toBe(false);
});

it('shows a spinner only on the edit button that was clicked', async () => {
  const opening = deferred<boolean>();
  state.openStepSlideout.mockReturnValue(opening.promise);
  mount([step(), step({uid: 'step-2'})]);

  rowButtons(1)[0]!.dispatchEvent(new Event('click'));
  await nextTick();

  expect(isLoading(rowButtons(0)[0]!)).toBe(false);
  expect(isLoading(rowButtons(1)[0]!)).toBe(true);
  expect(isLoading(addButton())).toBe(false);

  opening.resolve(true);
  await opening.promise;
  await nextTick();

  expect(isLoading(rowButtons(1)[0]!)).toBe(false);
});

it('clears the spinner when a slideout fails to open', async () => {
  state.openStepSlideout.mockRejectedValue(new Error('Request failed.'));
  mount();

  rowButtons()[0]!.dispatchEvent(new Event('click'));
  await nextTick();
  await nextTick();

  expect(isLoading(rowButtons()[0]!)).toBe(false);
});

it('ignores other open requests while a slideout is opening', async () => {
  const opening = deferred<boolean>();
  state.openStepSlideout.mockReturnValue(opening.promise);
  mount();

  rowButtons()[0]!.dispatchEvent(new Event('click'));
  rowButtons()[0]!.dispatchEvent(new Event('click'));
  addButton().dispatchEvent(new Event('click'));
  await nextTick();

  expect(state.openStepSlideout).toHaveBeenCalledOnce();

  opening.resolve(true);
  await opening.promise;
  await nextTick();

  addButton().dispatchEvent(new Event('click'));
  await nextTick();

  expect(state.openStepSlideout).toHaveBeenCalledTimes(2);
});

it('offers a dashed Add mapping button in place of an empty mapping, and Edit once mapped', async () => {
  mount([step()]);

  const section = () => container.querySelector('.step-card__mapping')!;
  const buttons = () =>
    [...section().querySelectorAll<HTMLElement>('craft-button')].map(
      (button) => [
        button.textContent!.trim(),
        (button as HTMLElement & {variant?: string}).variant,
      ]
    );

  expect(buttons()).toEqual([['Add mapping', 'dashed']]);

  section().querySelector<HTMLElement>('craft-button')!.click();
  await nextTick();
  state.openStepMapping.mock.calls[0]![0].apply({map: {title: 'name'}});
  await nextTick();

  expect(buttons()[0]).toEqual(['Edit mapping', 'plain']);
});

it('says nothing is mapped yet, with no button, when the step can’t be edited', () => {
  mount([step()], {editable: false});

  expect(
    container.querySelector('.step-card__mapping craft-button')
  ).toBeNull();
  expect(container.querySelector('.step-card__mapping')!.textContent).toContain(
    'Nothing mapped yet.'
  );
});
