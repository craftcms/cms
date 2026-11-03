import {createApp, h, nextTick, ref} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {ImportStep} from '@/modules/import/mapping/types';
import StepTabs from './StepTabs.vue';

const state = vi.hoisted(() => ({
  editors: [] as Array<{
    props: any;
    emit: (event: string, value: unknown) => void;
  }>,
  actions: [] as any[],
}));

vi.mock('./StepEditor.vue', async () => {
  const {defineComponent, h} = await import('vue');

  return {
    default: defineComponent({
      props: {
        step: Object,
        editable: Boolean,
        part: String,
        searchable: Boolean,
        errors: Array,
      },
      emits: ['update:step'],
      setup(props, {emit}) {
        state.editors.push({
          props,
          emit: (event, value) => emit(event as any, value),
        });

        return () =>
          h('div', {class: `editor-stub editor-stub--${props.part}`});
      },
    }),
  };
});

vi.mock('./SourcePreview.vue', async () => {
  const {defineComponent, h} = await import('vue');

  return {
    default: defineComponent({props: ['step'], setup: () => () => h('div')}),
  };
});

vi.mock('@/common/components/ActionMenu.vue', async () => {
  const {defineComponent, h} = await import('vue');

  return {
    default: defineComponent({
      props: ['actions', 'label', 'flush'],
      setup(props) {
        return () => {
          state.actions = props.actions;

          return h('div', {class: 'action-menu-stub'});
        };
      },
    }),
  };
});

const ENTRIES = 'CraftCms\\Cms\\Entry\\Import\\EntryImporter';
const USERS = 'CraftCms\\Cms\\User\\Import\\UserImporter';

function step(overrides: Partial<ImportStep> = {}): ImportStep {
  return {
    uid: 'step-1',
    type: ENTRIES,
    source: 'guides.json',
    transformer: null,
    batchSize: null,
    enabled: true,
    settings: {},
    ...overrides,
  };
}

let app: ReturnType<typeof createApp> | null = null;
let container: HTMLElement;
let steps: ReturnType<typeof ref<ImportStep[]>>;

function mount(
  initial: ImportStep[],
  errors: Record<string, string[]> = {}
): void {
  steps = ref<ImportStep[]>(initial);
  app = createApp({
    render: () =>
      h(StepTabs, {
        modelValue: steps.value!,
        'onUpdate:modelValue': (next: ImportStep[]) => (steps.value = next),
        editable: true,
        importerTypes: [
          {value: ENTRIES, label: 'Entries'},
          {value: USERS, label: 'Users'},
        ],
        errors,
      }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
}

function tabs(): HTMLElement[] {
  return [...container.querySelectorAll<HTMLElement>('[role="tab"]')];
}

function shownParts(): string[] {
  return [...container.querySelectorAll('.editor-stub')].map((el) =>
    el.className.replace('editor-stub editor-stub--', '')
  );
}

function action(label: string) {
  return state.actions.find((item) => item.label === label);
}

beforeEach(() => {
  state.editors = [];
  state.actions = [];
  vi.stubGlobal('confirm', vi.fn().mockReturnValue(true));
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue(new Response('<svg></svg>'))
  );
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app?.unmount();
  container.remove();
  vi.unstubAllGlobals();
});

it('shows a tab for each step, with the first one’s mapping', () => {
  mount([step(), step({uid: 'step-2', type: USERS, enabled: false})]);

  expect(tabs().map((tab) => tab.querySelector('span')!.textContent)).toEqual([
    '1. Entries',
    '2. Users',
  ]);
  expect(tabs()[1]!.querySelector('craft-badge')!.textContent).toBe('Disabled');
  expect(tabs()[0]!.getAttribute('aria-selected')).toBe('true');
  expect(shownParts()).toEqual(['mapping', 'matching']);
  expect(
    state.editors.find((editor) => editor.props.part === 'mapping')!.props
  ).toMatchObject({searchable: true, step: {uid: 'step-1'}});
});

it('switches to another step’s tab', async () => {
  mount([step(), step({uid: 'step-2', type: USERS})]);

  tabs()[1]!.click();
  await nextTick();

  expect(tabs()[1]!.getAttribute('aria-selected')).toBe('true');
  expect(state.editors.at(-1)!.props.step.uid).toBe('step-2');
});

it('folds a step’s settings out above its mapping, with its matching below', async () => {
  mount([step()]);

  expect(shownParts()).toEqual(['mapping', 'matching']);
  expect(
    container.querySelector('.step-tabs__heading')!.textContent!.trim()
  ).toBe('Matching');

  [...container.querySelectorAll<HTMLElement>('craft-button')]
    .find((button) => button.textContent!.trim() === 'Step settings')!
    .click();
  await nextTick();

  expect(shownParts()).toEqual([
    'source',
    'advanced',
    'settings',
    'mapping',
    'matching',
  ]);
});

it('adds a step, with its settings out to choose the rest', async () => {
  mount([step()]);

  [...container.querySelectorAll<HTMLElement>('craft-button')]
    .find((button) => button.textContent!.trim() === 'Add step')!
    .click();
  await nextTick();

  expect(steps.value!).toHaveLength(2);
  expect(steps.value![1]).toMatchObject({
    type: ENTRIES,
    source: null,
    enabled: true,
  });
  expect(tabs()[1]!.getAttribute('aria-selected')).toBe('true');
  expect(shownParts()).toContain('source');
});

it('keeps a step’s changes from any of its editors', async () => {
  mount([step()]);

  state.editors.at(-1)!.emit('update:step', step({source: 'places.csv'}));
  await nextTick();

  expect(steps.value![0]!.source).toBe('places.csv');
});

it('disables, moves and deletes a step from its actions', async () => {
  mount([step(), step({uid: 'step-2', type: USERS})]);

  action('Disable step').onClick();
  await nextTick();
  expect(steps.value![0]!.enabled).toBe(false);

  action('Move later').onClick();
  await nextTick();
  expect(steps.value!.map((s) => s.uid)).toEqual(['step-2', 'step-1']);

  action('Delete step').onClick();
  await nextTick();
  expect(steps.value!.map((s) => s.uid)).toEqual(['step-2']);
});

it('keeps at least one step enabled', () => {
  mount([step(), step({uid: 'step-2', enabled: false})]);

  expect(action('Disable step').disabled).toBe(true);
});

it('flags the steps the server found errors in, and hands each its own', () => {
  mount([step(), step({uid: 'step-2'})], {
    'steps.step-1.source': ['File does not exist.'],
  });

  expect(tabs()[0]!.textContent).toContain('Has errors');
  expect(tabs()[1]!.textContent).not.toContain('Has errors');
  expect(state.editors.at(-1)!.props.errors).toEqual([
    {path: ['source'], messages: ['File does not exist.']},
  ]);
});
