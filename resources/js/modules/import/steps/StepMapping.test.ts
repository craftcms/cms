import {createApp, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {applySuggestions, cloneValues} from '@/modules/import/mapping/paths';
import type {
  MappingColumn,
  MappingColumnEntry,
  MappingValues,
  ImportStep,
  SuggestedMap,
} from '@/modules/import/mapping/types';
import StepMapping from './StepMapping.vue';

const state = vi.hoisted(() => ({
  layout: vi.fn(),
  openNested: vi.fn(),
  context: null as any,
}));

vi.mock('@/common/composables/useAppLayout', () => ({
  useAppLayout: (options: unknown) => {
    state.layout(options instanceof Function ? options() : options);
  },
}));

vi.mock('@/common/slideouts', () => ({
  useSlideout: () => null,
}));

vi.mock('@/modules/import/mapping/nested-mapping', () => ({
  openNestedMapping: state.openNested,
}));

vi.mock('./step-mapping', () => ({
  takeStepMappingContext: () => state.context,
}));

// The real element is Lion's select-rich, whose options register before its invoker
// exists under happy-dom, so a bare element stands in for it.
vi.mock('@craftcms/ui/components/select-rich/select-rich', () => {
  class CraftSelectRich extends HTMLElement {
    modelValue: unknown = '';
  }
  if (!customElements.get('craft-select-rich')) {
    customElements.define('craft-select-rich', CraftSelectRich);
  }

  return {default: CraftSelectRich};
});

function col(
  overrides: Partial<MappingColumn> & {prefixedHandle: string}
): MappingColumn {
  return {
    handle: overrides.prefixedHandle,
    label: overrides.prefixedHandle,
    prefixedHandleAsArray: [overrides.prefixedHandle],
    prefixedHandleForMap: `map[${overrides.prefixedHandle}]`,
    prefixedHandleForMatchCriteria: `matchCriteria[${overrides.prefixedHandle}]`,
    prefixedHandleForClear: `clearableItems[${overrides.prefixedHandle}]`,
    isContainer: false,
    canBeMatchCriteria: false,
    canBeCleared: false,
    ...overrides,
  };
}

const title = col({prefixedHandle: 'title', canBeMatchCriteria: true});
const body = col({
  prefixedHandle: 'body',
  canBeMatchCriteria: true,
  canBeCleared: true,
});
const relation = col({prefixedHandle: 'author'});
// Mirrors the server, which flags a container as both matchable and clearable.
const outerMatrix = col({
  prefixedHandle: 'outerMatrix',
  isContainer: true,
  fieldUid: 'field-uid',
  canKeepMissingNestedElements: true,
  canBeMatchCriteria: true,
  canBeCleared: true,
});

const step: ImportStep = {
  uid: 'step-uid',
  type: 'CraftCms\\Cms\\Entry\\Import\\EntryImporter',
  source: 'people.csv',
  transformer: null,
  batchSize: null,
  settings: {},
};

function emptyValues(): MappingValues {
  return {
    map: {},
    matchCriteria: {},
    clearableItems: {},
    keepMissingNestedElements: {},
    fieldSettings: {},
  };
}

let applied: MappingValues | null;

function mount(
  destinationCols: MappingColumnEntry[],
  values: MappingValues = emptyValues(),
  suggestedMap: SuggestedMap = {}
) {
  state.context = {
    destinationCols,
    sourceDataCols: [
      {label: 'Please select', value: ''},
      {label: 'Name', value: 'name'},
      {label: 'Email', value: 'email'},
    ],
    values,
    suggestedMap,
    editable: true,
    step,
    apply: (next: MappingValues) => (applied = next),
  };

  app = createApp(StepMapping, {contextId: 'ctx', title: 'Edit mapping'});
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
}

function apply(): MappingValues {
  state.layout.mock.calls.at(-1)![0].onSave();

  return applied!;
}

function cells(): HTMLTableCellElement[] {
  return [...container.querySelectorAll<HTMLTableCellElement>('tbody td')];
}

function toggleCheckbox(column: 'match' | 'clear', checked = true): void {
  const cell = cells()[column === 'match' ? 1 : 2]!;
  const checkbox = cell.querySelector<HTMLElement & {checked: boolean}>(
    'craft-checkbox'
  )!;
  checkbox.checked = checked;

  checkbox.dispatchEvent(
    new CustomEvent('model-value-changed', {bubbles: true, detail: {}})
  );
}

function sourceSelect(): HTMLElement & {modelValue?: string} {
  return cells()[0]!.querySelector(
    'craft-select-rich'
  ) as unknown as HTMLElement & {modelValue?: string};
}

function chooseSource(value: string): void {
  const host = sourceSelect();
  host.modelValue = value;

  host.dispatchEvent(
    new CustomEvent('model-value-changed', {bubbles: true, detail: {}})
  );
}

let app: ReturnType<typeof createApp>;
let container: HTMLElement;

beforeEach(() => {
  state.layout.mockClear();
  state.openNested.mockReset();
  applied = null;
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ok: false}));
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
  vi.unstubAllGlobals();
});

it('renders the mapping table inside the container field group, like other slideout forms', () => {
  mount([title]);

  expect(
    container.querySelector('.cp-container > craft-field-group table')
  ).not.toBeNull();
});

it('writes a chosen source column to the destination column’s path', async () => {
  mount([title]);

  chooseSource('name');
  await nextTick();

  expect(apply().map).toEqual({title: 'name'});
});

it('keeps the Match and Clear columns on their own trees', () => {
  mount([body]);

  toggleCheckbox('match');
  toggleCheckbox('clear');

  expect(apply().matchCriteria).toEqual({body: '1'});
  expect(apply().clearableItems).toEqual({body: '1'});
});

it('offers no Match or Clear on a container row', () => {
  mount([outerMatrix]);

  const [incoming, match, clear] = cells();

  expect(incoming!.querySelector('craft-button')).not.toBeNull();
  expect(match!.querySelector('craft-checkbox')).toBeNull();
  expect(clear!.querySelector('craft-checkbox')).toBeNull();
});

it('leaves the option cells empty when neither applies', () => {
  mount([relation]);

  const [, match, clear] = cells();

  expect(match!.querySelector('craft-checkbox')).toBeNull();
  expect(clear!.querySelector('craft-checkbox')).toBeNull();
  expect(match!.textContent!.trim()).toBe('');
  expect(clear!.textContent!.trim()).toBe('');
});

it('offers a mapped column’s import settings and keeps them on their own tree', async () => {
  const assets = col({
    prefixedHandle: 'photos',
    importSettings: [
      {
        name: 'fileConflict',
        label:
          'What should happen when an incoming file matches an existing one?',
        options: [
          {value: 'useExisting', label: 'Use the existing asset'},
          {
            value: 'replace',
            label: 'Replace the existing file with the incoming one',
          },
        ],
        default: 'useExisting',
      },
    ],
  });

  mount([assets]);

  expect(cells()[0]!.querySelector('craft-select')).toBeNull();

  chooseSource('name');
  await nextTick();

  const select = cells()[0]!.querySelector<HTMLElement & {modelValue?: string}>(
    'craft-select'
  )!;

  expect(select.modelValue).toBe('useExisting');

  select.modelValue = 'replace';
  select.dispatchEvent(
    new CustomEvent('model-value-changed', {bubbles: true, detail: {}})
  );

  expect(apply().fieldSettings).toEqual({photos: {fileConflict: 'replace'}});
});

it('shows an import setting’s instructions in an info icon beside its label', async () => {
  const assets = col({
    prefixedHandle: 'photos',
    importSettings: [
      {
        name: 'fileConflict',
        label:
          'What should happen when an incoming file matches an existing one?',
        instructions: 'Incoming files are matched by filename.',
        options: [{value: 'useExisting', label: 'Use the existing asset'}],
        default: 'useExisting',
      },
      {
        name: 'other',
        label: 'Another setting',
        options: [{value: 'a', label: 'A'}],
        default: 'a',
      },
    ],
  });

  mount([assets]);
  chooseSource('name');
  await nextTick();

  const [withInstructions, withoutInstructions] = Array.from(
    cells()[0]!.querySelectorAll('craft-select')
  ).map((select) => select.querySelector('[slot="label"]')!);

  expect(withInstructions!.textContent).toContain(
    'What should happen when an incoming file matches an existing one?'
  );
  expect(
    withInstructions!.querySelector('craft-info-icon')!.textContent!.trim()
  ).toBe('Incoming files are matched by filename.');
  expect(withoutInstructions!.textContent!.trim()).toBe('Another setting');
  expect(withoutInstructions!.querySelector('craft-info-icon')).toBeNull();
});

it('hands the step’s mapping trees back on apply', () => {
  mount([title], {...emptyValues(), map: {title: 'name'}});

  expect(apply()).toEqual({
    map: {title: 'name'},
    matchCriteria: {},
    clearableItems: {},
    keepMissingNestedElements: {},
    fieldSettings: {},
  });
});

it('opens a container column’s nested mapping against the draft step', () => {
  mount([outerMatrix]);

  expect(container.querySelector('craft-select-rich')).toBeNull();

  container.querySelector('craft-button')!.dispatchEvent(new Event('click'));

  expect(state.openNested).toHaveBeenCalledOnce();
  expect(state.openNested.mock.calls[0]![0]).toMatchObject({
    col: outerMatrix,
    step,
    editable: true,
  });
});

it('shows a suggested source column and flags it as a best guess', async () => {
  mount([title], {...emptyValues(), map: {title: 'name'}}, {title: true});

  expect(sourceSelect().modelValue).toBe('name');
  expect(cells()[0]!.classList).toContain('best-guess');
});

it('clears the best-guess flag once the user chooses a column themselves', async () => {
  mount([title], {...emptyValues(), map: {title: 'name'}}, {title: true});

  chooseSource('email');
  await nextTick();

  expect(cells()[0]!.classList).not.toContain('best-guess');
});

it('keeps a freshly guessed column flagged once the select renders', async () => {
  const values = cloneValues(emptyValues());
  const suggestedMap: SuggestedMap = {};
  applySuggestions(values.map, {title: 'name'}, suggestedMap);

  mount([title], values, suggestedMap);

  expect(sourceSelect().modelValue).toBe('name');
  expect(cells()[0]!.classList).toContain('best-guess');
  expect(apply().map).toEqual({title: 'name'});
});

it('merges a nested panel’s result back into the trees', async () => {
  mount([outerMatrix]);

  container.querySelector('craft-button')!.dispatchEvent(new Event('click'));

  const next: MappingValues = {
    ...emptyValues(),
    map: {outerMatrix: {outerEt: {title: 'name'}}},
    // A container's own keep decision lives under a reserved `__keep__` leaf.
    keepMissingNestedElements: {outerMatrix: {__keep__: '1'}},
  };
  state.openNested.mock.calls[0]![0].apply(next);
  await nextTick();

  expect(apply().map).toEqual(next.map);
  expect(apply().keepMissingNestedElements).toEqual(
    next.keepMissingNestedElements
  );
});

it('isn’t dirty when a checkbox is ticked and unticked again', async () => {
  mount([title]);

  toggleCheckbox('match');
  toggleCheckbox('match', false);
  await nextTick();

  expect(state.layout.mock.calls.at(-1)![0].form.isDirty).toBe(false);
});

it('is dirty once a column is mapped', async () => {
  mount([title]);

  chooseSource('name');
  await nextTick();

  expect(state.layout.mock.calls.at(-1)![0].form.isDirty).toBe(true);
});
