import {createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {UiPayload} from '@/modules/ui/types';
import type {ImportStep, MappingValues} from '@/modules/import/mapping/types';
import {StepMappingUnavailableError} from './step-mapping';
import StepEditor from './StepEditor.vue';

const state = vi.hoisted(() => ({
  fetchStepUi: vi.fn(),
  loadStepMapping: vi.fn(),
  values: {} as Record<string, unknown>,
  refresh: null as ((values: unknown) => Promise<UiPayload>) | null,
  emitChange: null as (() => void) | null,
  errors: null as UiPayload['errors'] | null,
  mappingProps: null as any,
  shownPaths: [] as string[],
  emitMapping: null as ((values: MappingValues) => void) | null,
}));

vi.mock('./step-settings', () => ({
  fetchStepUi: state.fetchStepUi,
}));

vi.mock('./step-mapping', async (importOriginal) => ({
  ...(await importOriginal<typeof import('./step-mapping')>()),
  loadStepMapping: state.loadStepMapping,
}));

// Stubbed so the test can drive the form's refresh callback and reported values.
vi.mock('@/modules/ui/UiRenderer.vue', () => ({
  default: {
    name: 'UiRenderer',
    props: ['payload', 'refresh', 'errors'],
    emits: ['change'],
    setup(props: any, {expose, emit}: any) {
      state.refresh = props.refresh;
      state.emitChange = () => emit('change', {}, state.values);
      expose({currentValues: () => state.values});

      return () => {
        state.errors = props.errors;
        state.shownPaths = props.payload.nodes.map(
          (node: any) =>
            node.control?.path?.[0] ?? node.children?.[0]?.control?.path?.[0]
        );

        return h('div', {class: 'ui-renderer'}, [
          h('input', {'data-ui-control-path': '["source"]'}),
        ]);
      };
    },
  },
}));

vi.mock('./StepMappingEditor.vue', () => ({
  default: {
    name: 'StepMappingEditor',
    props: [
      'mapping',
      'step',
      'editable',
      'templateErrors',
      'loading',
      'headless',
      'hideMatchSummary',
    ],
    emits: ['change'],
    setup(props: any, {emit}: any) {
      state.emitMapping = (values) => emit('change', values);

      return () => {
        state.mappingProps = props;

        return h('div', {class: 'mapping-stub'});
      };
    },
  },
}));

function payload(): UiPayload {
  return {
    scope: [],
    refreshable: true,
    nodes: [
      {control: {path: ['type']}},
      {control: {path: ['source']}},
      {children: [{control: {path: ['batchSize']}}]},
      {control: {path: ['settings', 'section']}},
    ],
    values: {},
    errors: [],
    globalErrors: [],
  } as unknown as UiPayload;
}

function emptyValues(): MappingValues {
  return {
    map: {},
    matchCriteria: {},
    clearableItems: {},
    keepMissingNestedElements: {},
    fieldSettings: {},
    templates: {},
  };
}

function mapping(values: MappingValues = emptyValues()) {
  return {
    destinationCols: [],
    sourceDataCols: [],
    templateTextExpanderTriggers: [],
    values,
    suggestedMap: {},
    targetName: 'entries',
  };
}

const step: ImportStep = {
  uid: 'step-1',
  type: 'CraftCms\\Cms\\Entry\\Import\\EntryImporter',
  source: 'people.csv',
  transformer: null,
  batchSize: null,
  enabled: true,
  settings: {section: 'news', map: {title: 'name'}},
};

let app: ReturnType<typeof createApp>;
let container: HTMLElement;
let updates: ImportStep[];
let canMapReports: boolean[];

async function mount(
  canMap: boolean,
  errors: UiPayload['errors'] = [],
  part?: 'source' | 'advanced' | 'settings' | 'mapping'
): Promise<void> {
  state.fetchStepUi.mockResolvedValue({
    ui: payload(),
    canMap,
    sourceError: null,
  });
  state.values = {
    type: step.type,
    source: step.source,
    settings: {section: 'news'},
  };

  app = createApp({
    render: () =>
      h(StepEditor, {
        step,
        editable: true,
        errors,
        part,
        'onUpdate:step': (next: ImportStep) => updates.push(next),
        'onUpdate:canMap': (next: boolean) => canMapReports.push(next),
      }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);

  await settle();
}

/** Lets the form's fetch resolve and the mapping's debounced load run. */
async function settle(): Promise<void> {
  for (let i = 0; i < 3; i++) {
    await Promise.resolve();
    await nextTick();
  }
  await vi.runAllTimersAsync();
  await nextTick();
}

beforeEach(() => {
  vi.useFakeTimers();
  state.fetchStepUi.mockReset();
  state.loadStepMapping.mockReset().mockResolvedValue(mapping());
  state.mappingProps = null;
  updates = [];
  canMapReports = [];
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
  vi.useRealTimers();
});

it('builds the step’s form from the step it’s given', async () => {
  await mount(false);

  expect(state.fetchStepUi).toHaveBeenCalledWith(step);
  expect(container.querySelector('.ui-renderer')).not.toBeNull();
});

it('reports a form change as an updated step, carrying its mapping along', async () => {
  await mount(false);

  state.values = {...state.values, source: 'other.csv'};
  state.emitChange!();
  await nextTick();

  expect(updates.at(-1)).toMatchObject({
    uid: 'step-1',
    source: 'other.csv',
    settings: {section: 'news', map: {title: 'name'}},
  });
});

it('leaves the mapping out until the step can be mapped', async () => {
  await mount(false);

  expect(state.loadStepMapping).not.toHaveBeenCalled();
  expect(container.querySelector('.step-editor__mapping')).toBeNull();
});

it('loads the mapping inline once the step can be mapped', async () => {
  await mount(true);

  expect(state.loadStepMapping).toHaveBeenCalledOnce();
  expect(container.querySelector('.mapping-stub')).not.toBeNull();
  expect(state.mappingProps.editable).toBe(true);
});

it('says the mapping is loading under its heading until it arrives', async () => {
  state.loadStepMapping.mockReturnValue(new Promise(() => {}));
  await mount(true);

  expect(container.querySelector('.mapping-loading')!.textContent).toContain(
    'Loading the mapping'
  );
  expect(container.querySelector('.mapping-stub')).toBeNull();
});

it('keeps the mapping in view while it reloads, marked as loading', async () => {
  await mount(true);

  state.loadStepMapping.mockReturnValue(new Promise(() => {}));
  state.values = {...state.values, settings: {section: 'blog'}};
  await state.refresh!(state.values);
  await settle();

  expect(container.querySelector('.mapping-stub')).not.toBeNull();
  expect(state.mappingProps.loading).toBe(true);
  expect(container.querySelector('.mapping-loading')).toBeNull();
});

it('keeps the server’s guesses as part of the step', async () => {
  state.loadStepMapping.mockResolvedValue(
    mapping({...emptyValues(), map: {title: 'name', slug: 'slug'}})
  );
  await mount(true);

  expect(updates.at(-1)!.settings).toMatchObject({
    section: 'news',
    map: {title: 'name', slug: 'slug'},
  });
});

it('folds the mapping’s changes into the step', async () => {
  await mount(true);

  state.emitMapping!({...emptyValues(), map: {title: 'email'}});
  await nextTick();

  expect(updates.at(-1)!.settings).toMatchObject({
    section: 'news',
    map: {title: 'email'},
  });
});

it('reloads the mapping when what the step imports into changes', async () => {
  await mount(true);

  state.values = {...state.values, settings: {section: 'blog'}};
  state.fetchStepUi.mockResolvedValue({
    ui: payload(),
    canMap: true,
    sourceError: null,
  });
  await state.refresh!(state.values);
  await settle();

  expect(state.loadStepMapping).toHaveBeenCalledTimes(2);
  expect(state.loadStepMapping.mock.calls[1]![0].settings).toMatchObject({
    section: 'blog',
  });
});

it('shows why the data source couldn’t be read under its field', async () => {
  state.loadStepMapping.mockRejectedValue(
    new StepMappingUnavailableError('The file couldn’t be read.', 'source')
  );
  await mount(true);

  expect(state.errors).toEqual([
    {path: ['source'], messages: ['The file couldn’t be read.']},
  ]);
  expect(container.querySelector('.mapping-stub')).toBeNull();
});

it('hands the server’s template errors to the mapping, keyed by column path', async () => {
  await mount(true, [
    {path: ['settings', 'templates', 'title'], messages: ['Invalid.']},
  ]);

  expect(state.mappingProps.templateErrors).toEqual({title: ['Invalid.']});
});

it('reports whether the step can be mapped', async () => {
  await mount(true);

  expect(canMapReports).toEqual([false, true]);
});

it('shows just the settings, without loading the mapping, when asked to', async () => {
  await mount(true, [], 'settings');

  expect(container.querySelector('.ui-renderer')).not.toBeNull();
  expect(container.querySelector('.step-editor__mapping')).toBeNull();
  expect(state.loadStepMapping).not.toHaveBeenCalled();
});

it('shows just the mapping when asked to', async () => {
  await mount(true, [], 'mapping');

  expect(state.mappingProps).toMatchObject({
    headless: true,
    hideMatchSummary: true,
  });

  expect(container.querySelector('.ui-renderer')).toBeNull();
  expect(container.querySelector('.mapping-stub')).not.toBeNull();
});

it('shows just one part of the form when asked to', async () => {
  for (const [part, shown] of [
    ['source', ['source']],
    ['advanced', ['batchSize']],
    ['settings', ['type', 'settings']],
  ] as const) {
    await mount(false, [], part);
    expect(state.shownPaths).toEqual(shown);
    app.unmount();
    container.innerHTML = '';
  }

  await mount(false);
});

it('keeps the rest of the step when only its data source is reported', async () => {
  await mount(false, [], 'source');

  state.values = {source: 'other.csv'};
  state.emitChange!();
  await nextTick();

  expect(updates.at(-1)).toMatchObject({
    type: step.type,
    source: 'other.csv',
    settings: {section: 'news', map: {title: 'name'}},
  });
});

it('doesn’t reload the mapping for a change to what’s done with existing rows', async () => {
  await mount(true);

  state.emitMapping!({...emptyValues(), map: {title: 'name'}});
  state.values = {
    ...state.values,
    settings: {section: 'news', matchMode: 'createOnly'},
  };
  state.emitChange!();
  await settle();

  expect(state.loadStepMapping).toHaveBeenCalledOnce();
});

it('leaves what’s matched on to the matching when it’s just the mapping', async () => {
  await mount(true, [], 'mapping');

  state.emitMapping!({
    ...emptyValues(),
    map: {title: 'name'},
    matchCriteria: {title: '1'},
  });
  await nextTick();

  expect(updates.at(-1)!.settings).toMatchObject({map: {title: 'name'}});
  expect((updates.at(-1)!.settings as any).matchCriteria).toEqual({});
});
