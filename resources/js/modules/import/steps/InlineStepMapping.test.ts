import {createApp, defineComponent, h, nextTick, reactive} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {ImportStep, MappingValues} from '@/modules/import/mapping/types';
import InlineStepMapping from './InlineStepMapping.vue';

const state = vi.hoisted(() => ({
  fetchStepMapping: vi.fn(),
}));

vi.mock('./step-mapping', async (importOriginal) => ({
  ...(await importOriginal<typeof import('./step-mapping')>()),
  fetchStepMapping: state.fetchStepMapping,
}));

// The table and matching settings only render the trees the editor provides.
vi.mock('@/modules/import/mapping/MappingTable.vue', () => ({
  default: defineComponent({render: () => h('table')}),
}));
vi.mock('@/modules/import/mapping/MatchingSection.vue', () => ({
  default: defineComponent({render: () => null}),
}));

function emptyValues(): MappingValues {
  return {
    map: {},
    matchCriteria: {},
    clearableItems: {},
    defaultValues: {},
    keepMissingNestedElements: {},
    fieldSettings: {},
    templates: {},
    matchMode: null,
  };
}

function payload(suggestions: Record<string, unknown> = {}) {
  return {
    available: true,
    destinationCols: [],
    sourceDataCols: [],
    templateTextExpanderTriggers: [],
    values: emptyValues(),
    suggestions,
  };
}

/** The step as the slideout edits it, whose settings are replaced as it changes. */
const step = reactive<{-readonly [K in keyof ImportStep]: ImportStep[K]}>({
  uid: 'step-1',
  type: 'CraftCms\\Cms\\Entry\\Import\\EntryImporter',
  source: 'people.csv',
  transformer: null,
  batchSize: null,
  enabled: true,
  settings: {},
});

let app: ReturnType<typeof createApp>;
let container: HTMLElement;
let changes: MappingValues[];

async function flush(): Promise<void> {
  for (let i = 0; i < 5; i++) {
    await Promise.resolve();
    await nextTick();
  }
}

async function mount(): Promise<void> {
  app = createApp({
    render: () =>
      h(InlineStepMapping, {
        step,
        editable: true,
        canMap: true,
        onChange: (values: MappingValues) => changes.push(values),
      }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
  await flush();
}

beforeEach(() => {
  state.fetchStepMapping.mockReset().mockResolvedValue(payload());
  step.settings = {entryType: 'news'};
  changes = [];
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
});

it('hands up the server’s suggestions once they’re shown, so applying the step keeps them', async () => {
  state.fetchStepMapping.mockResolvedValue(payload({title: 'name'}));

  await mount();

  expect(changes.at(-1)?.map).toEqual({title: 'name'});
});

it('hands nothing up when there’s nothing to suggest', async () => {
  await mount();

  expect(changes).toEqual([]);
});

it('loads the columns again when a setting other than the mapping changes, such as the entry type', async () => {
  await mount();
  expect(state.fetchStepMapping).toHaveBeenCalledOnce();

  step.settings = {...step.settings, entryType: 'blog'};
  await flush();

  expect(state.fetchStepMapping).toHaveBeenCalledTimes(2);
  expect(state.fetchStepMapping.mock.calls[1]![0].settings.entryType).toBe(
    'blog'
  );
});

it('doesn’t load the columns again as the mapping itself is edited', async () => {
  await mount();

  step.settings = {
    ...step.settings,
    map: {title: 'name'},
    matchCriteria: {title: '1'},
    matchMode: 'updateOnly',
  };
  await flush();

  expect(state.fetchStepMapping).toHaveBeenCalledOnce();
});
