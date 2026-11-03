import {createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {applySuggestions, cloneValues} from '@/modules/import/mapping/paths';
import {
  type MappingColumn,
  type MappingColumnEntry,
  type MappingValues,
  type ImportStep,
  type SuggestedMap,
  TEMPLATE_RULE,
} from '@/modules/import/mapping/types';
import StepMappingEditor from './StepMappingEditor.vue';

const state = vi.hoisted(() => ({
  fetchNested: vi.fn(),
  testMatch: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({usePage: () => ({props: {}})}));

vi.mock('@/modules/import/mapping/test-match', async (importOriginal) => ({
  ...(await importOriginal<
    typeof import('@/modules/import/mapping/test-match')
  >()),
  testMatch: state.testMatch,
}));

async function flushPromises(): Promise<void> {
  for (let i = 0; i < 3; i++) {
    await Promise.resolve();
    await nextTick();
  }
}

vi.mock('@/modules/import/mapping/nested-mapping', () => ({
  fetchNestedMapping: state.fetchNested,
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
  enabled: true,
  settings: {},
};

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

let changes: MappingValues[];

const templateTextExpanderTriggers = [
  {
    trigger: '{',
    boundary: 'anywhere' as const,
    label: 'Object Template Variables',
    options: [{label: 'Name', value: '{name}'}],
  },
];

function mount(
  destinationCols: MappingColumnEntry[],
  values: MappingValues = emptyValues(),
  suggestedMap: SuggestedMap = {},
  templateErrors: Record<string, string[]> = {},
  editable = true,
  extraProps: Record<string, unknown> = {}
) {
  app = createApp({
    render: () =>
      h(StepMappingEditor, {
        mapping: {
          destinationCols,
          sourceDataCols: [
            {label: 'Please select', value: ''},
            {label: 'Use template', value: TEMPLATE_RULE},
            {label: 'Name', value: 'name'},
            {label: 'Email', value: 'email'},
          ],
          templateTextExpanderTriggers,
          values,
          suggestedMap,
          targetName: 'entries',
        },
        step,
        editable,
        templateErrors,
        ...extraProps,
        onChange: (next: MappingValues) => changes.push(next),
      }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
}

function templateInput(): HTMLElement & {modelValue?: string} {
  return cells()[0]!.querySelector('craft-input') as unknown as HTMLElement & {
    modelValue?: string;
  };
}

function typeTemplate(value: string): void {
  const host = templateInput();
  host.modelValue = value;

  host.dispatchEvent(
    new CustomEvent('model-value-changed', {bubbles: true, detail: {}})
  );
}

/** The trees the editor last reported, once a change has had a tick to settle. */
async function latest(): Promise<MappingValues> {
  await nextTick();

  return changes.at(-1)!;
}

function cells(): HTMLTableCellElement[] {
  return [
    ...container.querySelectorAll<HTMLTableCellElement>(
      '.mapping-table tbody td'
    ),
  ];
}

/** A row's cells after its destination, by the column of its own handle. */
/** Whether the table has a Match column; a heading's text runs on into its tip. */
function hasMatchColumn(): boolean {
  return [...container.querySelectorAll('thead th')].some((th) =>
    th.textContent!.trim().startsWith('Match')
  );
}

function cellsOf(handle: string): HTMLTableCellElement[] {
  const row = [...container.querySelectorAll('.mapping-table tbody tr')].find(
    (tr) => tr.querySelector('th code')?.textContent?.trim() === handle
  )!;

  return [...row.querySelectorAll<HTMLTableCellElement>('td')];
}

/** Match only shows once a container's own columns do, so it's the last but one. */
function toggleCheckbox(
  column: 'match' | 'clear',
  checked = true,
  handle?: string
): void {
  const rowCells = handle ? cellsOf(handle) : cells();
  const cell =
    column === 'clear' ? rowCells.at(-1)! : rowCells[rowCells.length - 2]!;
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
  state.fetchNested.mockReset();
  state.testMatch.mockReset();
  changes = [];
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ok: false}));
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
  vi.unstubAllGlobals();
});

it('renders the mapping table inline', async () => {
  mount([title]);

  expect(container.querySelector('.step-mapping-editor table')).not.toBeNull();
});

it('writes a chosen source column to the destination column’s path', async () => {
  mount([title]);

  chooseSource('name');
  await nextTick();

  expect((await latest()).map).toEqual({title: 'name'});
});

it('keeps the Match and Clear columns on their own trees', async () => {
  mount([body]);

  chooseSource('name');
  await nextTick();
  toggleCheckbox('match');
  toggleCheckbox('clear');

  expect((await latest()).matchCriteria).toEqual({body: '1'});
  expect((await latest()).clearableItems).toEqual({body: '1'});
});

function checkbox(
  column: 'match' | 'clear'
): HTMLElement & {disabled: boolean} {
  return cells()[column === 'match' ? 1 : 2]!.querySelector(
    'craft-checkbox'
  ) as unknown as HTMLElement & {disabled: boolean};
}

it('only lets a mapped row be matched on, while Clear works either way', async () => {
  mount([body]);

  expect(hasMatchColumn()).toBe(true);
  expect(checkbox('match').disabled).toBe(true);
  expect(checkbox('clear').disabled).toBe(false);

  chooseSource('name');
  await nextTick();

  expect(checkbox('match').disabled).toBe(false);
});

it('drops a row’s match criterion when it’s unmapped, but keeps Clear', async () => {
  mount([body]);

  chooseSource('name');
  await nextTick();
  toggleCheckbox('match');
  toggleCheckbox('clear');
  chooseSource('');
  await nextTick();

  expect(checkbox('match').disabled).toBe(true);
  expect((await latest()).matchCriteria).toEqual({body: ''});
  expect((await latest()).clearableItems).toEqual({body: '1'});
});
it('keeps Clear on its own tree', async () => {
  mount([body]);

  chooseSource('name');
  await nextTick();
  toggleCheckbox('clear');

  expect((await latest()).clearableItems).toEqual({body: '1'});
});

it('offers no Match or Clear on a container row, just a button to map its columns', () => {
  mount([outerMatrix]);

  const [incoming, match, clear] = cells();

  // the same label whether or not anything inside is mapped yet
  expect(incoming!.querySelector('craft-button')!.textContent!.trim()).toBe(
    'Edit mapping'
  );
  expect(match!.querySelector('craft-checkbox')).toBeNull();
  expect(clear!.querySelector('craft-checkbox')).toBeNull();
});

const innerTitle = col({
  prefixedHandle: 'outerMatrix[outerEt][title]',
  label: 'Inner title',
  prefixedHandleAsArray: ['outerMatrix', 'outerEt', 'fields', 'title'],
});

function nestedGroups(providerName: string | null = 'Outer ET') {
  return {
    groups: [{providerName, destinationCols: [innerTitle]}],
    suggestions: {outerMatrix: {outerEt: {fields: {title: 'name'}}}},
  };
}

function containerButton(): HTMLElement {
  return container.querySelector<HTMLElement>(
    '.mapping-container-cell craft-button'
  )!;
}

async function expand(): Promise<void> {
  containerButton().dispatchEvent(new Event('click'));
  await nextTick();
  await nextTick();
}

function rowLabels(): string[] {
  return [
    ...container.querySelectorAll<HTMLElement>('.mapping-table tbody tr'),
  ].map(
    (row) =>
      `${row.dataset.rowDepth ?? 0}:${row
        .querySelector('th, td')!
        .textContent!.replace(/\s+/g, ' ')
        .trim()}`
  );
}

it('expands a container’s columns beneath it, grouped by entry type', async () => {
  state.fetchNested.mockResolvedValue(nestedGroups());
  mount([outerMatrix, title]);

  await expand();

  expect(state.fetchNested).toHaveBeenCalledWith(
    step,
    expect.objectContaining({matchCriteria: {}}),
    outerMatrix
  );
  expect(rowLabels()).toEqual([
    '0:outerMatrix outerMatrix',
    '1:Outer ET',
    '2:Inner title outerMatrix[outerEt][title]',
    '0:title title',
  ]);
  expect(containerButton().getAttribute('aria-expanded')).toBe('true');
  // the entry type's heading lines up with the columns under it
  expect(
    [...container.querySelectorAll<HTMLElement>('.mapping-table tbody tr')].map(
      (row) => row.style.getPropertyValue('--mapping-level')
    )
  ).toEqual(['', '1', '1', '']);
  expect(
    container.querySelectorAll('tbody tr.mapping-table__group-row')
  ).toHaveLength(1);
});

it('leaves out the group heading for a container with one unnamed group', async () => {
  state.fetchNested.mockResolvedValue(nestedGroups(null));
  mount([outerMatrix]);

  await expand();

  expect(rowLabels()).toEqual([
    '0:outerMatrix outerMatrix',
    '1:Inner title outerMatrix[outerEt][title]',
  ]);
});

it('fills the container’s columns with the server’s guesses, and reports them', async () => {
  state.fetchNested.mockResolvedValue(nestedGroups());
  mount([outerMatrix]);

  await expand();

  expect((await latest()).map).toEqual({
    outerMatrix: {outerEt: {fields: {title: 'name'}}},
  });
  expect(
    container.querySelector('.mapping-container-cell')!.textContent
  ).toContain('1 field mapped');
});

it('collapses a container without asking for its columns again', async () => {
  state.fetchNested.mockResolvedValue(nestedGroups());
  mount([outerMatrix]);

  await expand();
  await expand();

  expect(rowLabels()).toEqual(['0:outerMatrix outerMatrix']);
  expect(containerButton().getAttribute('aria-expanded')).toBe('false');

  await expand();

  expect(state.fetchNested).toHaveBeenCalledOnce();
  expect(rowLabels()).toHaveLength(3);
});

const innerBody = col({
  prefixedHandle: 'outerMatrix[outerEt][body]',
  handle: 'innerBody',
  label: 'Inner body',
  prefixedHandleAsArray: ['outerMatrix', 'outerEt', 'fields', 'body'],
  canBeMatchCriteria: true,
  canBeCleared: true,
});

it('matches a container’s own columns from the table, once one is mapped', async () => {
  state.fetchNested.mockResolvedValue({
    groups: [{providerName: 'Outer ET', destinationCols: [innerBody]}],
    suggestions: {},
  });
  mount([outerMatrix]);

  await expand();

  const match = () =>
    cellsOf('innerBody')
      .at(-2)!
      .querySelector<HTMLElement & {disabled: boolean}>('craft-checkbox')!;

  expect(hasMatchColumn()).toBe(true);
  expect(match().disabled).toBe(true);

  const select = cellsOf('innerBody')[0]!.querySelector(
    'craft-select-rich'
  ) as unknown as HTMLElement & {modelValue?: string};
  select.modelValue = 'name';
  select.dispatchEvent(
    new CustomEvent('model-value-changed', {bubbles: true, detail: {}})
  );
  await nextTick();
  toggleCheckbox('match', true, 'innerBody');

  expect(match().disabled).toBe(false);
  expect((await latest()).matchCriteria).toEqual({
    outerMatrix: {outerEt: {fields: {body: '1'}}},
  });
});

it('offers to keep missing nested elements under the button, open or not', async () => {
  state.fetchNested.mockResolvedValue(nestedGroups());
  mount([outerMatrix]);

  const checkbox = () =>
    container.querySelector<HTMLElement & {checked: boolean}>(
      '.mapping-container-cell craft-checkbox'
    );

  expect(checkbox()).not.toBeNull();

  await expand();

  checkbox()!.checked = true;
  checkbox()!.dispatchEvent(
    new CustomEvent('model-value-changed', {bubbles: true, detail: {}})
  );

  expect((await latest()).keepMissingNestedElements).toEqual({
    outerMatrix: {__keep__: '1'},
  });
});

it('leaves the keep-missing option out for a container that can’t keep them', () => {
  mount([{...outerMatrix, canKeepMissingNestedElements: false}]);

  expect(
    container.querySelector('.mapping-container-cell craft-checkbox')
  ).toBeNull();
});

it('says why a container’s columns couldn’t be loaded', async () => {
  state.fetchNested.mockRejectedValue(new Error('Nope.'));
  mount([outerMatrix]);

  await expand();
  await nextTick();

  expect(
    container
      .querySelector('.mapping-container-cell [role="alert"]')!
      .textContent!.trim()
  ).toBe('Nope.');
  expect(rowLabels()).toEqual(['0:outerMatrix outerMatrix']);
});

it('leaves the option cells empty when neither applies', async () => {
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

  expect((await latest()).fieldSettings).toEqual({
    photos: {fileConflict: 'replace'},
  });
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

it('reports the step’s mapping trees as they change', async () => {
  mount([title], {...emptyValues(), map: {title: 'name'}});
  chooseSource('email');

  expect(await latest()).toEqual({
    map: {title: 'email'},
    matchCriteria: {},
    clearableItems: {},
    keepMissingNestedElements: {},
    fieldSettings: {},
    templates: {},
  });
});

/** The source columns whose options carry the “Suggested” badge. */
function badgedOptions(): unknown[] {
  return [...cells()[0]!.querySelectorAll('craft-option')]
    .filter((option) => option.querySelector('craft-badge[slot="badge"]'))
    .map(
      (option) => (option as HTMLElement & {choiceValue?: unknown}).choiceValue
    );
}

it('badges the suggested option, for its list and the trigger while it’s selected', async () => {
  mount([title], {...emptyValues(), map: {title: 'name'}}, {title: true});

  expect(sourceSelect().modelValue).toBe('name');
  expect(badgedOptions()).toEqual(['name']);
});

it('still badges the suggestion once the user chooses another column', async () => {
  mount([title], {...emptyValues(), map: {title: 'name'}}, {title: true});

  chooseSource('email');
  await nextTick();

  expect(badgedOptions()).toEqual(['name']);
});

it('badges no option for a column nothing was suggested for', () => {
  mount([title], {...emptyValues(), map: {title: 'name'}});

  expect(badgedOptions()).toEqual([]);
});

it('keeps a freshly guessed column flagged once the select renders', async () => {
  const values = cloneValues(emptyValues());
  const suggestedMap: SuggestedMap = {};
  applySuggestions(values.map, {title: 'name'}, suggestedMap);

  mount([title], values, suggestedMap);

  expect(sourceSelect().modelValue).toBe('name');
  expect(badgedOptions()).toEqual(['name']);
  await nextTick();
  expect(changes.at(-1)?.map ?? values.map).toEqual({title: 'name'});
});

function legends(): string[] {
  return [...container.querySelectorAll('.mapping-table__legend')].map(
    (legend) => legend.querySelector('craft-badge')!.textContent!.trim()
  );
}

it('explains the suggested badge below the table, with the badge as its icon', () => {
  mount([title], {...emptyValues(), map: {title: 'name'}}, {title: true});

  const legend = container.querySelector('.mapping-table__legend')!;

  expect(legends()).toEqual(['Suggested']);
  expect(legend.textContent).toContain(
    'These selections were auto-selected as the closest matches'
  );
  // below the table, not above it as the panel's own note
  expect(
    container.querySelector('table')!.compareDocumentPosition(legend) &
      Node.DOCUMENT_POSITION_FOLLOWING
  ).toBeTruthy();
});

it('leaves the suggested legend out when nothing was suggested', () => {
  mount([title], {...emptyValues(), map: {title: 'name'}});

  expect(legends()).toEqual([]);
});

it('leaves the suggested legend out of a read-only mapping', () => {
  mount(
    [title],
    {...emptyValues(), map: {title: 'name'}},
    {title: true},
    {},
    false
  );

  expect(legends()).toEqual([]);
});

it('shows a template input only once “Use template” is chosen', async () => {
  mount([title]);

  expect(templateInput()).toBeNull();

  chooseSource(TEMPLATE_RULE);
  await nextTick();

  expect(templateInput()).not.toBeNull();
  expect(
    cells()[0]!.querySelector<HTMLElement & {triggers?: unknown}>(
      'craft-text-expander'
    )!.triggers
  ).toEqual(templateTextExpanderTriggers);
});

it('explains how to insert a variable in the template’s tip', async () => {
  mount([title]);

  chooseSource(TEMPLATE_RULE);
  await nextTick();

  const field = cells()[0]!.querySelector('craft-field')!;

  expect(field.querySelector('[slot="tip"]')!.textContent!.trim()).toBe(
    'Type { to choose an object template variable.'
  );
  expect(field.querySelector('craft-info-icon')).toBeNull();
});

it('shows the server’s template errors on the column', async () => {
  mount(
    [title],
    {
      ...emptyValues(),
      map: {title: TEMPLATE_RULE},
      templates: {title: '{{ name'},
    },
    {},
    {title: ['The template couldn’t be parsed.']}
  );
  await nextTick();

  expect(
    [...cells()[0]!.querySelectorAll('.error-list li')].map((li) =>
      li.textContent!.trim()
    )
  ).toEqual(['The template couldn’t be parsed.']);
  expect(templateInput().getAttribute('aria-invalid')).toBe('true');
});

it('writes a column’s template to the templates tree', async () => {
  mount([title]);

  chooseSource(TEMPLATE_RULE);
  await nextTick();
  typeTemplate('{name} <{email}>');

  const values = await latest();

  expect(values.map).toEqual({title: TEMPLATE_RULE});
  expect(values.templates).toEqual({title: '{name} <{email}>'});
});

function matchSummary(): string | null {
  return (
    container.querySelector('.match-summary__text')?.textContent?.trim() ?? null
  );
}

it('leaves out the summary of what’s matched on, and its test, when asked to', () => {
  mount(
    [col({...title, label: 'Title'})],
    {...emptyValues(), map: {title: 'name'}, matchCriteria: {title: '1'}},
    {},
    {},
    true,
    {hideMatchSummary: true}
  );

  expect(container.querySelector('.match-summary')).toBeNull();
  expect(container.querySelector('.mapping-table')).not.toBeNull();
});

it('sums up below the legend what existing elements are matched by', () => {
  const slug = col({
    prefixedHandle: 'slug',
    label: 'Slug',
    canBeMatchCriteria: true,
  });
  mount([col({...title, label: 'Title'}), slug], {
    ...emptyValues(),
    map: {title: 'name', slug: 'email'},
    matchCriteria: {title: '1', slug: '1'},
  });

  expect(matchSummary()).toBe(
    'The import will match existing entries by Title and Slug.'
  );
});

function testButton(): HTMLElement & {disabled: boolean; loading: boolean} {
  return container.querySelector(
    '.match-summary__test-button'
  ) as unknown as HTMLElement & {disabled: boolean; loading: boolean};
}

/** The results table's rows, as each cell's text. */
function resultRows(): string[][] {
  return [
    ...container.querySelectorAll('.match-summary__results tbody tr'),
  ].map((row) =>
    [...row.querySelectorAll('th, td')].map((cell) => cell.textContent!.trim())
  );
}

function results(): string {
  return container
    .querySelector('.match-summary__results')!
    .textContent!.replace(/\s+/g, ' ')
    .trim();
}

const matched = (): MappingValues => ({
  ...emptyValues(),
  map: {title: 'name'},
  matchCriteria: {title: '1'},
});

it('leaves the summary and its test out until something’s matched on', () => {
  mount([title]);

  expect(matchSummary()).toBeNull();
  expect(container.querySelector('.match-summary__test-button')).toBeNull();
});

it('follows the Match checkboxes in its summary', async () => {
  mount([body]);

  chooseSource('name');
  await nextTick();
  toggleCheckbox('match');
  await nextTick();

  expect(matchSummary()).toBe(
    'The import will match existing entries by body.'
  );
});

it('leaves a container’s own matching out of the summary', () => {
  mount([outerMatrix], {
    ...emptyValues(),
    matchCriteria: {outerMatrix: {outerEt: {fields: {body: '1'}}}},
  });

  expect(matchSummary()).toBeNull();
});

it('puts its summary of what’s matched after the suggested legend', () => {
  mount([title], matched(), {title: true});

  const legends = container.querySelector('.mapping-table__legends')!;
  const summary = container.querySelector('.match-summary')!;

  expect(
    legends.compareDocumentPosition(summary) & Node.DOCUMENT_POSITION_FOLLOWING
  ).toBeTruthy();
});

it('sets a test aside once what’s matched changes', async () => {
  state.testMatch.mockResolvedValue({matches: [], checked: 1, total: 1});
  mount([title, body], {...matched(), map: {title: 'name', body: 'email'}});

  testButton().dispatchEvent(new Event('click'));
  await flushPromises();
  expect(results()).not.toBe('');

  const bodyCells = cells().slice(3);
  const match = bodyCells[1]!.querySelector<HTMLElement & {checked: boolean}>(
    'craft-checkbox'
  )!;
  match.checked = true;
  match.dispatchEvent(
    new CustomEvent('model-value-changed', {bubbles: true, detail: {}})
  );
  await nextTick();

  expect(results()).toBe('');
});

it('tests the matching with the trees as they stand', async () => {
  state.testMatch.mockResolvedValue({
    matches: [{row: 2, id: 7, label: 'Hello', url: '/admin/entries/7'}],
    checked: 3,
    total: 3,
  });
  mount([title], matched());

  testButton().dispatchEvent(new Event('click'));
  await flushPromises();

  expect(state.testMatch).toHaveBeenCalledWith(
    step,
    expect.objectContaining({map: {title: 'name'}, matchCriteria: {title: '1'}})
  );
  expect(results()).not.toContain('row matches');
  expect(resultRows()).toEqual([['Hello', '2']]);
  expect(
    container.querySelector('.match-summary__results a')!.getAttribute('href')
  ).toBe('/admin/entries/7');
});

it('says when no row matches', async () => {
  state.testMatch.mockResolvedValue({matches: [], checked: 4, total: 4});
  mount([title], matched());

  testButton().dispatchEvent(new Event('click'));
  await flushPromises();

  expect(results()).toBe('None of the 4 rows match any existing entries.');
});

it('says when it stopped at the most matches it lists', async () => {
  state.testMatch.mockResolvedValue({
    matches: Array.from({length: 10}, (_, i) => ({
      row: i + 1,
      id: i + 1,
      label: `Entry ${i + 1}`,
      url: null,
    })),
    checked: 12,
    total: 50,
  });
  mount([title], matched());

  testButton().dispatchEvent(new Event('click'));
  await flushPromises();

  expect(resultRows()).toHaveLength(10);
});

it('says why the matching couldn’t be tested', async () => {
  state.testMatch.mockRejectedValue({
    response: {data: {message: 'The file couldn’t be read.'}},
  });
  mount([title], matched());

  testButton().dispatchEvent(new Event('click'));
  await flushPromises();

  expect(results()).toBe('The file couldn’t be read.');
});

describe('when it’s searchable', () => {
  function shownHandles(): string[] {
    return [...container.querySelectorAll('.mapping-table tbody tr')].map(
      (tr) => tr.querySelector('th code')!.textContent!.trim()
    );
  }

  function mountSearchable(values: MappingValues = emptyValues()): void {
    mount([title, body], values, {}, {}, true, {
      searchable: true,
      hideMatch: true,
    });
  }

  function filterButton(label: string): HTMLElement {
    return [
      ...container.querySelectorAll<HTMLElement>(
        '.step-mapping-editor__filters craft-button'
      ),
    ].find((button) => button.textContent!.trim() === label)!;
  }

  it('keeps each field’s settings in its row, with a column to clear it when empty', () => {
    mountSearchable({...emptyValues(), map: {title: 'name'}});

    const headers = [
      ...container.querySelectorAll('.mapping-table thead th'),
    ].map((th) => th.textContent!.trim());

    expect(headers.some((header) => header.startsWith('Clear on empty'))).toBe(
      true
    );
    expect(headers.some((header) => header.startsWith('Match'))).toBe(false);
    expect(cellsOf('body')[1]!.querySelector('craft-checkbox')).not.toBeNull();
  });

  it('narrows the fields to what’s mapped, or to a search', async () => {
    mountSearchable({...emptyValues(), map: {title: 'name'}});

    filterButton('Mapped').click();
    await nextTick();

    expect(shownHandles()).toEqual(['title']);

    filterButton('All').click();
    const search = container.querySelector(
      '.step-mapping-editor__search'
    ) as HTMLElement & {modelValue: string};
    search.modelValue = 'bod';
    search.dispatchEvent(new CustomEvent('model-value-changed'));
    await nextTick();

    expect(shownHandles()).toEqual(['body']);
  });
});
