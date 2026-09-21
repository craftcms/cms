import {createApp, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {applySuggestions, cloneValues} from '@/modules/import/mapping/paths';
import type {
  MappingCol,
  MappingColEntry,
  MappingValues,
  StepPayload,
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

function col(
  overrides: Partial<MappingCol> & {prefixedHandle: string}
): MappingCol {
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
// The server reports both flags as true for a container — `CustomField` tests `$this`
// where it means `$field` — so the fixture mirrors that rather than the ideal.
const outerMatrix = col({
  prefixedHandle: 'outerMatrix',
  isContainer: true,
  fieldUid: 'field-uid',
  canKeepMissingNestedElements: true,
  canBeMatchCriteria: true,
  canBeCleared: true,
});

const step: StepPayload = {
  uid: 'step-uid',
  type: 'CraftCms\\Cms\\Entry\\Import\\EntryImporter',
  file: 'people.csv',
  transformer: null,
  batchSize: null,
  settings: {},
};

const urls = {
  settingsUrl: '/actions/import/step-settings',
  mappingUrl: '/actions/import/step-mapping',
  nestedColsUrl: '/actions/import/nested-mapping-cols',
};

function emptyValues(): MappingValues {
  return {
    map: {},
    matchCriteria: {},
    clearableItems: {},
    keepMissingNestedElements: {},
  };
}

let applied: MappingValues | null;

function mount(
  destinationCols: MappingColEntry[],
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
    urls,
    apply: (next: MappingValues) => (applied = next),
  };

  app = createApp(StepMapping, {contextId: 'ctx', title: 'Edit mapping'});
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
}

/** What the panel would hand back to the step right now. */
function apply(): MappingValues {
  state.layout.mock.calls.at(-1)![0].onSave();

  return applied!;
}

/** The `td`s of the first body row, in column order. */
function cells(): HTMLTableCellElement[] {
  return [...container.querySelectorAll<HTMLTableCellElement>('tbody td')];
}

/**
 * Toggles the checkbox in the named column of the first row, the way
 * `craft-checkbox` reports one: the host carries the state, and
 * `model-value-changed` announces it.
 */
function toggleCheckbox(column: 'match' | 'clear', checked = true): void {
  // Destination is a `th`, so the `td`s are Incoming data, Match, Clear.
  const cell = cells()[column === 'match' ? 1 : 2]!;
  const checkbox = cell.querySelector<HTMLElement & {checked: boolean}>(
    'craft-checkbox'
  )!;
  checkbox.checked = checked;

  checkbox.dispatchEvent(
    new CustomEvent('model-value-changed', {bubbles: true, detail: {}})
  );
}

/**
 * `craft-combobox` rejects a value that doesn't match one of its options, and it only
 * renders those an animation frame or so after mounting, so the tests below have to
 * let it settle before reading or writing its value.
 */
function settle(): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, 50));
}

/** The combobox in the first row's Incoming data cell. */
function combobox(): HTMLElement & {modelValue?: string} {
  return cells()[0]!.querySelector(
    'craft-combobox'
  ) as unknown as HTMLElement & {modelValue?: string};
}

/** Picks a source column the way `craft-combobox` reports one. */
function chooseSource(value: string): void {
  const host = combobox();
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
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
});

it('writes a chosen source column to the destination column’s path', async () => {
  mount([title]);
  await settle();

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
  // The container's nested columns each carry their own decision, in the panel.
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

it('hands the step’s mapping trees back on apply', () => {
  mount([title], {...emptyValues(), map: {title: 'name'}});

  expect(apply()).toEqual({
    map: {title: 'name'},
    matchCriteria: {},
    clearableItems: {},
    keepMissingNestedElements: {},
  });
});

it('opens a container column’s nested mapping against the draft step', () => {
  mount([outerMatrix]);

  expect(container.querySelector('craft-combobox')).toBeNull();

  container.querySelector('craft-button')!.dispatchEvent(new Event('click'));

  expect(state.openNested).toHaveBeenCalledOnce();
  expect(state.openNested.mock.calls[0]![0]).toMatchObject({
    col: outerMatrix,
    step,
    colsUrl: urls.nestedColsUrl,
    editable: true,
  });
});

it('shows a suggested source column and flags it as a best guess', async () => {
  mount([title], {...emptyValues(), map: {title: 'name'}}, {title: true});
  await settle();

  expect(combobox().modelValue).toBe('name');
  expect(cells()[0]!.classList).toContain('best-guess');
});

it('clears the best-guess flag once the user chooses a column themselves', async () => {
  mount([title], {...emptyValues(), map: {title: 'name'}}, {title: true});
  await settle();

  chooseSource('email');
  await nextTick();

  expect(cells()[0]!.classList).not.toContain('best-guess');
});

it('keeps a freshly guessed column flagged once the combobox settles', async () => {
  // The context the way `step-mapping.ts` builds it: an empty map from the server,
  // filled by the real `applySuggestions`. The other best-guess tests hand the flags
  // in ready-made, which skips this — and it's where the flag was being lost.
  const values = cloneValues(emptyValues());
  const suggestedMap: SuggestedMap = {};
  applySuggestions(values.map, {title: 'name'}, suggestedMap);

  mount([title], values, suggestedMap);
  await settle();

  expect(combobox().modelValue).toBe('name');
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
