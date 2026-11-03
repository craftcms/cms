import {createApp, nextTick, reactive, ref} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {cloneStep, cloneSteps} from '@/modules/import/mapping/paths';
import type {ImportStep} from '@/modules/import/mapping/types';
import StepList from './StepList.vue';

const state = vi.hoisted(() => ({
  openStepSlideout: vi.fn(),
  menus: new Map<string, Array<any>>(),
}));

vi.mock('./step-slideout', () => ({
  openStepSlideout: state.openStepSlideout,
}));

// stands in for each card's actions menu, keeping its items to act on
vi.mock('@/common/components/ActionMenu.vue', async () => {
  const {h} = await import('vue');

  return {
    default: {
      name: 'ActionMenu',
      props: ['actions', 'label'],
      setup(props: any) {
        return () => {
          state.menus.set(props.label, props.actions);

          return h('div', {class: 'action-menu-stub'}, props.label);
        };
      },
    },
  };
});

/** Steps saved with the plan, which have pages of their own. */
const savedUids = ['step-1', 'step-2'];

function stepPageUrl(step: ImportStep): string | null {
  return savedUids.includes(step.uid)
    ? `/admin/import/plan/steps/${step.uid}`
    : null;
}

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
  } = {}
) {
  steps = ref<ImportStep[]>(initial);

  app = createApp({
    components: {StepList},
    template:
      '<StepList v-model="steps" :editable="editable" :importer-types="importerTypes" :errors="errors" :step-page-url="stepPageUrl" />',
    setup: () => ({
      steps,
      importerTypes,
      editable: props.editable ?? true,
      errors: props.errors ?? {},
      stepPageUrl,
    }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
}

/**
 * Vue drops an event reaching a listener second-hand in the same millisecond the listener
 * was attached, so a click straight after mounting can go unheard.
 */
async function mountAndWait(...args: Parameters<typeof mount>): Promise<void> {
  mount(...args);
  await new Promise((resolve) => setTimeout(resolve, 2));
}

function row(index = 0): HTMLElement {
  return [...container.querySelectorAll<HTMLElement>('[role="listitem"]')][
    index
  ]!;
}

/** The card's own link, which opens the step. */
function openButton(index = 0): HTMLElement {
  return row(index).querySelector<HTMLElement>('.step-card__open')!;
}

/** The items of a card's actions menu, by label. */
function menu(index = 0): Record<string, any> {
  return Object.fromEntries(
    (state.menus.get(`Step ${index + 1} actions`) ?? []).map((item: any) => [
      item.label,
      item,
    ])
  );
}

/** The Create step menu's items, by label: one for each importer type. */
function createMenu(): Record<string, any> {
  return Object.fromEntries(
    (state.menus.get('Create step') ?? [])
      .flatMap((group: any) => group.items)
      .map((item: any) => [item.label, item])
  );
}

/** What the list last opened a slideout with. */
function opened(): any {
  return state.openStepSlideout.mock.lastCall?.[0];
}

beforeEach(() => {
  state.openStepSlideout.mockReset().mockResolvedValue(true);
  state.menus.clear();
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

it('links each saved step’s card to its page', () => {
  mount([step(), step({uid: 'step-2'}), step({uid: 'step-3'})]);

  expect(openButton(1).getAttribute('href')).toBe(
    '/admin/import/plan/steps/step-2'
  );
  expect(openButton(2).getAttribute('href')).toBe('#');
});
it('heads a step’s card with its importer type and source', () => {
  mount();

  expect(openButton().textContent!.trim()).toBe('1. Entries');
  expect(row().querySelector('.step-card__source')!.textContent).toBe(
    'people.csv'
  );
});

it('offers editing a step, from its menu as well as its card', () => {
  mount([step(), step({uid: 'step-2'})]);

  menu(1)['Edit step'].onClick();

  expect(opened()).toMatchObject({
    step: step({uid: 'step-2'}),
    editable: true,
    title: 'Edit step 2',
    editUrl: '/admin/import/plan/steps/step-2',
  });
});

it('only offers viewing a step that can’t be edited', () => {
  mount([step()], {editable: false});

  expect(Object.keys(menu(0))).toEqual(['View step']);

  menu(0)['View step'].onClick();

  expect(opened()).toMatchObject({editable: false, title: 'Step 1'});
});
it('describes each card by its step', () => {
  mount([step(), step({uid: 'step-2'})]);

  expect(row(1).getAttribute('aria-label')).toBe('2. Entries — people.csv');
});
it('disables a step and enables it again', async () => {
  mount([step(), step({uid: 'step-2'})]);

  menu(0)['Disable step'].onClick();
  await nextTick();

  expect(steps.value!.map((s) => s.enabled)).toEqual([false, true]);
  expect(row(0).querySelector('craft-badge')!.textContent).toBe('Disabled');

  menu(0)['Enable step'].onClick();
  await nextTick();

  expect(steps.value!.map((s) => s.enabled)).toEqual([true, true]);
});
it('only lets a step be disabled while another stays enabled', () => {
  mount([step(), step({uid: 'step-2', enabled: false})]);

  expect(menu(0)['Disable step'].disabled).toBe(true);
  expect(menu(1)['Enable step'].disabled).toBe(false);
});
it('offers adding the first step from the empty state', () => {
  mount([]);

  const empty = container.querySelector('craft-empty')!;

  expect((empty as HTMLElement & {appearance: string}).appearance).toBe(
    'dashed'
  );
  expect(empty.querySelector('.action-menu-stub')!.textContent).toBe(
    'Create step'
  );
  expect(Object.keys(createMenu())).toEqual(['Add Entries', 'Add Assets']);
  expect(container.querySelector('.step-list__actions')).toBeNull();
});

it('offers adding another step below the list', () => {
  mount([step()]);

  expect(container.querySelector('craft-empty')).toBeNull();
  expect(
    container.querySelector('.step-list__actions .action-menu-stub')
  ).not.toBeNull();
});

it('offers no way to add a step that can’t be edited', () => {
  mount([], {editable: false});

  expect(container.querySelector('craft-empty .action-menu-stub')).toBeNull();
});

it('adds a step of the chosen type in a slideout, and to the list once it’s applied', async () => {
  mount([step()]);

  createMenu()['Add Assets'].onClick();

  expect(opened()).toMatchObject({
    step: {
      type: importerTypes[1]!.value,
      source: null,
      enabled: true,
      settings: {},
    },
    editUrl: null,
  });

  opened().apply(step({uid: 'step-3', source: 'places.csv'}));
  await nextTick();

  expect(steps.value!.map((s) => s.uid)).toEqual(['step-1', 'step-3']);
});

it('replaces a step with the one its slideout applies', async () => {
  mount([step(), step({uid: 'step-2'})]);

  menu(0)['Edit step'].onClick();
  opened().apply(step({source: 'places.csv'}));
  await nextTick();

  expect(steps.value!.map((s) => s.source)).toEqual([
    'places.csv',
    'people.csv',
  ]);
});

it('hands a step’s slideout the errors the server found in it', () => {
  mount([step()], {errors: {'steps.step-1.source': ['File does not exist.']}});

  menu(0)['Edit step'].onClick();

  expect(opened().errors).toEqual([
    {path: ['source'], messages: ['File does not exist.']},
  ]);
});
it('removes a step once the deletion is confirmed', async () => {
  mount([step(), step({uid: 'step-2'})]);

  menu(0)['Delete step'].onClick();
  await nextTick();

  expect(steps.value!.map((s) => s.uid)).toEqual(['step-2']);
});

it('keeps a step the user declines to delete', async () => {
  vi.stubGlobal('confirm', vi.fn().mockReturnValue(false));
  mount([step(), step({uid: 'step-2'})]);

  menu(0)['Delete step'].onClick();
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

it('badges a step the server found errors in', () => {
  mount([step(), step({uid: 'step-2'})], {
    errors: {'steps.step-2.source': ['File does not exist.']},
  });

  expect(row(0).querySelector('craft-badge')).toBeNull();
  expect(row(1).querySelector('craft-badge')!.textContent).toBe('Has errors');
});

it('describes an invalid step’s card button with its badge', () => {
  mount([step(), step({uid: 'step-2'})], {
    errors: {'steps.step-2.source': ['File does not exist.']},
  });

  const describedBy = openButton(1).getAttribute('aria-describedby');

  expect(openButton(0).hasAttribute('aria-describedby')).toBe(false);
  expect(container.querySelector(`#${describedBy}`)!.textContent).toBe(
    'Has errors'
  );
});

function pencil(index = 0): HTMLElement {
  return row(index).querySelector<HTMLElement>(
    'craft-button[aria-label^="Edit "]'
  )!;
}

it('opens a step in a slideout from the pencil in its card', async () => {
  await mountAndWait([step(), step({uid: 'step-2'})]);

  expect(pencil(1).getAttribute('aria-label')).toBe(
    'Edit 2. Entries — people.csv'
  );

  pencil(1).dispatchEvent(new MouseEvent('click', {bubbles: true}));

  expect(opened()).toMatchObject({
    step: step({uid: 'step-2'}),
    opener: pencil(1),
  });
});

it('opens a step in a slideout when its card is double-clicked', async () => {
  await mountAndWait([step()]);

  row(0)
    .querySelector('.step-card__source')!
    .dispatchEvent(new MouseEvent('dblclick', {bubbles: true}));

  expect(opened()).toMatchObject({step: step()});
});

it('leaves a double-click on the card’s own controls alone', () => {
  mount([step()]);

  pencil(0).dispatchEvent(new MouseEvent('dblclick', {bubbles: true}));

  // the pencil's own clicks aren't dispatched by a synthetic dblclick
  expect(state.openStepSlideout).not.toHaveBeenCalled();
});

it('opens a double-clicked step in a new tab with a modifier key', async () => {
  const open = vi.fn();
  vi.stubGlobal('open', open);
  await mountAndWait([step()]);

  row(0)
    .querySelector('.step-card__source')!
    .dispatchEvent(new MouseEvent('dblclick', {bubbles: true, metaKey: true}));

  expect(open).toHaveBeenCalledWith(
    '/admin/import/plan/steps/step-1',
    '_blank',
    'noopener'
  );
  expect(state.openStepSlideout).not.toHaveBeenCalled();
});

it('opens a step in a slideout from the link on its name', async () => {
  await mountAndWait([step()]);

  openButton(0).dispatchEvent(
    new MouseEvent('click', {bubbles: true, cancelable: true, button: 0})
  );

  expect(opened()).toMatchObject({step: step()});
});

it('opens a step that’s yet to be saved in a slideout, even with a modifier key', async () => {
  await mountAndWait([step({uid: 'step-3'})]);

  openButton(0).dispatchEvent(
    new MouseEvent('click', {bubbles: true, cancelable: true, metaKey: true})
  );

  expect(opened()).toMatchObject({step: step({uid: 'step-3'})});
});
