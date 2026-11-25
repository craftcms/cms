import {createApp, h, nextTick, reactive} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {ImportStep, MappingValues} from '@/modules/import/mapping/types';
import InlineStepMapping from './InlineStepMapping.vue';

const state = vi.hoisted(() => ({
  fetchStepMapping: vi.fn(),
  openStepMapping: vi.fn(),
}));

vi.mock('./step-mapping', async (importOriginal) => ({
  ...(await importOriginal<typeof import('./step-mapping')>()),
  fetchStepMapping: state.fetchStepMapping,
  openStepMapping: state.openStepMapping,
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
    destinationCols: [
      {
        handle: 'title',
        label: 'Title',
        prefixedHandleAsArray: ['title'],
        icon: 'i-cursor',
      },
      {
        heading: 'Content',
        subfields: [
          {
            handle: 'summary',
            label: 'Summary',
            prefixedHandleAsArray: ['layout-uid'],
            icon: null,
          },
        ],
      },
    ],
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

async function mount(canMap = true): Promise<void> {
  app = createApp({
    render: () =>
      h(InlineStepMapping, {
        step,
        editable: true,
        canMap,
        onChange: (values: MappingValues) => changes.push(values),
      }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
  await flush();
}

beforeEach(() => {
  state.fetchStepMapping.mockReset().mockResolvedValue(payload());
  state.openStepMapping.mockReset().mockResolvedValue(true);
  step.settings = {entryType: 'news'};
  changes = [];
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
});

it('shows nothing of the mapping until the step can be mapped', async () => {
  await mount(false);

  expect(container.textContent!.trim()).toBe('');
  expect(state.fetchStepMapping).not.toHaveBeenCalled();
});

it('lists every mapped destination by its label, whether the map has it by layout uid or by handle', async () => {
  step.settings = {
    entryType: 'news',
    map: {title: 'name', 'layout-uid': 'blurb', a: 'a', b: 'b', c: 'c', d: 'd'},
  };

  await mount();

  const names = [...container.querySelectorAll('tbody th .truncate')].map(
    (span) => span.textContent!.trim()
  );
  // all of them, with no limit to show the rest behind
  expect(names).toEqual(['Title', 'Summary', 'a', 'b', 'c', 'd']);
});

it('edits the mapping in its panel, handing up what’s applied there', async () => {
  step.settings = {entryType: 'news', map: {title: 'name'}};
  await mount();

  [...container.querySelectorAll<HTMLElement>('craft-button')]
    .find((button) => button.textContent!.trim() === 'Edit mapping')!
    .click();
  await flush();

  expect(state.openStepMapping).toHaveBeenCalledOnce();
  const applied = {...emptyValues(), map: {title: 'heading'}};
  state.openStepMapping.mock.calls[0]![0].apply(applied);

  expect(changes).toEqual([applied]);
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
