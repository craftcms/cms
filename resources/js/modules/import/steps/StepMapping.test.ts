import {createApp, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {applySuggestions, cloneValues} from '@/modules/import/mapping/paths';
import {
  type MappingColumn,
  type MappingColumnEntry,
  type MappingValues,
  type ImportStep,
  type SuggestedMap,
  TEMPLATE_RULE,
} from '@/modules/import/mapping/types';
import StepMapping from './StepMapping.vue';

const state = vi.hoisted(() => ({
  layout: vi.fn(),
  openNested: vi.fn(),
  validateStep: vi.fn(),
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

vi.mock('./step-slideout', () => ({
  validateStep: state.validateStep,
}));

vi.mock('./step-mapping', () => ({
  takeStepMappingContext: () => state.context,
}));

// The real element is Lion's select-rich, whose options register before its invoker
// exists under happy-dom, so a bare element stands in for it.
vi.mock('@craftcms/ui/components/select-rich/select-rich', async () => {
  const {LitElement} = await import('lit');
  class CraftSelectRich extends LitElement {
    static override styles = [];
    modelValue: unknown = '';
  }
  if (!customElements.get('craft-select-rich')) {
    customElements.define('craft-select-rich', CraftSelectRich);
  }

  return {default: CraftSelectRich};
});

// happy-dom has no `attachInternals()`, which the real element needs for form participation.
vi.mock('@craftcms/ui/components/button-group/button-group', async () => {
  const {LitElement} = await import('lit');
  class CraftButtonGroup extends LitElement {}
  if (!customElements.get('craft-button-group')) {
    customElements.define('craft-button-group', CraftButtonGroup);
  }

  return {default: CraftButtonGroup};
});

// Lion's radio group reads its value from radios that don't register under happy-dom, so a bare
// element that holds the value stands in for it.
vi.mock('@craftcms/ui/components/radio-group/radio-group', async () => {
  const {LitElement} = await import('lit');
  class CraftRadioGroup extends LitElement {
    modelValue: unknown = '';
  }
  if (!customElements.get('craft-radio-group')) {
    customElements.define('craft-radio-group', CraftRadioGroup);
  }

  return {default: CraftRadioGroup};
});

// The same goes for the checkbox group and its checkboxes.
vi.mock('@craftcms/ui/components/checkbox-group/checkbox-group', async () => {
  const {LitElement} = await import('lit');
  class CraftCheckboxGroup extends LitElement {
    modelValue: unknown = [];
  }
  if (!customElements.get('craft-checkbox-group')) {
    customElements.define('craft-checkbox-group', CraftCheckboxGroup);
  }

  return {default: CraftCheckboxGroup};
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
    // as the server sends for a text field
    defaultValueInput: {type: 'text'},
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
    defaultValues: {},
    keepMissingNestedElements: {},
    fieldSettings: {},
    templates: {},
    matchMode: 'createAndUpdate',
  };
}

let applied: MappingValues | null;

const templateTextExpanderTriggers = [
  {
    trigger: '{',
    boundary: 'anywhere',
    label: 'Object Template Variables',
    options: [{label: 'Name', value: '{name}'}],
  },
];

function mount(
  destinationCols: MappingColumnEntry[],
  values: MappingValues = emptyValues(),
  suggestedMap: SuggestedMap = {}
) {
  state.context = {
    destinationCols,
    sourceDataCols: [
      {label: 'Please select', value: ''},
      {label: 'Use template', value: TEMPLATE_RULE},
      {label: 'Name', value: 'name', hint: 'Ada Lovelace'},
      {label: 'Email', value: 'email'},
      {label: 'Active', value: 'active', hint: 'false'},
      {label: 'Archived', value: 'archived', hint: '0'},
    ],
    templateTextExpanderTriggers,
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

async function applyAsync(): Promise<MappingValues | null> {
  await state.layout.mock.calls.at(-1)![0].onSave();

  return applied;
}

function templateInput(): HTMLElement & {modelValue?: string} {
  return cell('preview').querySelector(
    'craft-input'
  ) as unknown as HTMLElement & {
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

function apply(): MappingValues {
  state.layout.mock.calls.at(-1)![0].onSave();

  return applied!;
}

type ColumnId = 'incoming' | 'preview' | 'match' | 'whenEmpty';

/** The first row's cell in a column. */
function cell(column: ColumnId): HTMLTableCellElement {
  return container.querySelector<HTMLTableCellElement>(
    `tbody .mapping-table__cell--${column}`
  )!;
}

function headers(): string[] {
  return [...container.querySelectorAll('thead th')].map((th) =>
    th.textContent!.trim()
  );
}

function modeRadios(): HTMLInputElement[] {
  return [
    ...container.querySelectorAll<HTMLInputElement>(
      '.matching-section input[type="radio"]'
    ),
  ];
}

function chooseMode(mode: string): void {
  const radio = modeRadios().find((input) => input.value === mode)!;
  radio.checked = true;
  radio.dispatchEvent(new Event('change', {bubbles: true}));
}

/** The When empty popover's choices, in the row's pill. */
function emptyValueSelect(): (HTMLElement & {modelValue?: string}) | null {
  return cell('whenEmpty').querySelector('craft-radio-group');
}

function chooseEmptyValueMode(mode: string): void {
  const host = emptyValueSelect()!;
  host.modelValue = mode;

  host.dispatchEvent(
    new CustomEvent('model-value-changed', {bubbles: true, detail: {}})
  );
}

function sourceSelect(): HTMLElement & {modelValue?: string} {
  return cell('incoming').querySelector(
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
  state.validateStep.mockReset().mockResolvedValue(undefined);
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

it('renders the mapping table edge to edge in the field group, with what to do when matched contained below', () => {
  mount([title]);

  expect(
    container.querySelector(
      'craft-field-group > .cp-container .matching-section'
    )
  ).not.toBeNull();
  expect(container.querySelector('craft-field-group table')).not.toBeNull();
  expect(container.querySelector('.cp-container table')).toBeNull();
});

it('writes a chosen source column to the destination column’s path', async () => {
  mount([title]);

  chooseSource('name');
  await nextTick();

  expect(apply().map).toEqual({title: 'name'});
});

it('lets the incoming data be searched for a source column', () => {
  mount([title]);

  expect((sourceSelect() as {searchable?: boolean}).searchable).toBe(true);
});

it('drops a row’s When empty rule, and the column for it, once rows are matched on it', async () => {
  mount([body]);

  chooseSource('name');
  await nextTick();
  chooseEmptyValueMode('erase');
  await nextTick();
  tickMatch(true);
  await nextTick();

  expect(apply().matchCriteria).toEqual({body: '1'});
  expect(apply().clearableItems).toEqual({body: ''});
  expect(emptyValueSelect()).toBeNull();

  tickMatch(false);
  await nextTick();

  expect(emptyValueSelect()).not.toBeNull();
});

it('only lets a mapped row be matched on or given a When empty rule', async () => {
  mount([body]);

  expect(matchBox()).toBeNull();
  expect(emptyValueSelect()).toBeNull();

  chooseSource('name');
  await nextTick();

  expect(matchBox()).not.toBeNull();
  expect(emptyValueSelect()!.modelValue).toBe('skip');
});

it('drops a row’s match criterion and When empty rule when it’s unmapped', async () => {
  mount([body]);

  chooseSource('name');
  await nextTick();
  chooseEmptyValueMode('erase');
  chooseSource('');
  await nextTick();

  expect(apply().clearableItems).toEqual({body: ''});

  chooseSource('name');
  await nextTick();
  tickMatch(true);
  chooseSource('');
  await nextTick();

  expect(matchBox()).toBeNull();
  expect(apply().matchCriteria).toEqual({body: ''});
});

it('offers no Match or When empty on a container row', () => {
  mount([outerMatrix], {...emptyValues(), map: {outerMatrix: {x: 'name'}}});

  expect(cell('incoming').querySelector('craft-button')).not.toBeNull();
  expect(cell('match').querySelector('craft-radio-group')).toBeNull();
  expect(cell('whenEmpty').querySelector('craft-select')).toBeNull();
});

it('leaves the When empty cell empty when there’s nothing to choose', () => {
  mount([relation]);

  expect(cell('whenEmpty').textContent!.trim()).toBe('');
});

it('only lets the step update matched elements once it has something to match against', async () => {
  mount([title], {...emptyValues(), map: {title: 'name'}});

  expect(modeRadios().map((radio) => radio.disabled)).toEqual([
    true,
    true,
    true,
  ]);
  expect(modeRadios().find((radio) => radio.checked)!.value).toBe('createOnly');

  tickMatch(true);
  await nextTick();

  expect(modeRadios().some((radio) => radio.disabled)).toBe(false);
  expect(modeRadios().find((radio) => radio.checked)!.value).toBe(
    'createAndUpdate'
  );

  chooseMode('updateOnly');

  expect(apply().matchMode).toBe('updateOnly');
});

it('falls back to only creating once nothing is matched against', async () => {
  mount([title], {
    ...emptyValues(),
    map: {title: 'name'},
    matchCriteria: {title: '1'},
    matchMode: 'updateOnly',
  });

  tickMatch(false);
  await nextTick();

  expect(apply().matchMode).toBe('createOnly');
});

it('leaves out what to do when matched for an importer that doesn’t match', () => {
  mount([title], {...emptyValues(), map: {title: 'name'}, matchMode: null});

  expect(modeRadios()).toHaveLength(0);
});

it('only offers erasing to a column that can be cleared', async () => {
  mount([title, body]);

  chooseSource('name');
  await nextTick();

  const options = (group: Element) =>
    [...group.querySelectorAll('craft-radio')].map(
      (radio) => (radio as HTMLElement & {choiceValue?: string}).choiceValue
    );
  const [titleSelect, bodySelect] = container.querySelectorAll(
    '.mapping-table__cell--whenEmpty craft-radio-group'
  );

  expect(options(titleSelect!)).toEqual(['skip', 'default']);
  expect(bodySelect).toBeUndefined();
});

it('offers no default for a column without an input for one', async () => {
  mount([col({prefixedHandle: 'author', defaultValueInput: undefined})]);

  chooseSource('name');
  await nextTick();

  expect(cell('whenEmpty').textContent!.trim()).toBe('');
});

it('enters a multi-choice default as its options, kept as a JSON list', async () => {
  mount([
    col({
      prefixedHandle: 'tags',
      defaultValueInput: {
        type: 'multiselect',
        options: [
          {value: 'news', label: 'News'},
          {value: 'craft', label: 'Craft'},
        ],
      },
    }),
  ]);

  chooseSource('name');
  await nextTick();
  chooseEmptyValueMode('default');
  await nextTick();

  const group = cell('whenEmpty').querySelector<
    HTMLElement & {modelValue?: string[]}
  >('craft-checkbox-group')!;
  expect(
    [...group.querySelectorAll('craft-checkbox')].map((box) =>
      box.textContent!.trim()
    )
  ).toEqual(['News', 'Craft']);

  group.modelValue = ['news', 'craft'];
  group.dispatchEvent(
    new CustomEvent('model-value-changed', {bubbles: true, detail: {}})
  );
  await nextTick();

  expect(apply().defaultValues).toEqual({tags: '["news","craft"]'});
  expect(
    cell('whenEmpty').querySelector('.setting-popover__summary')!.textContent
  ).toContain('Default · News, Craft');
});

it('sums up a lightswitch default with the field’s own labels', async () => {
  mount(
    [
      col({
        prefixedHandle: 'featured',
        defaultValueInput: {
          type: 'lightswitch',
          onLabel: 'Yes',
          offLabel: 'No',
        },
      }),
    ],
    {...emptyValues(), map: {featured: 'name'}, defaultValues: {featured: '1'}}
  );

  expect(
    cell('whenEmpty').querySelector('.setting-popover__summary')!.textContent
  ).toContain('Default · Yes');
});

it('previews the first value of the chosen source column', async () => {
  mount([title]);

  expect(cell('preview').textContent!.trim()).toBe('');

  chooseSource('name');
  await nextTick();

  expect(cell('preview').textContent!.trim()).toBe('Ada Lovelace');

  chooseSource('email');
  await nextTick();

  expect(cell('preview').textContent!.trim()).toBe('No value');
});

it('previews a lightswitch as the switch its value turns into, beside the value', async () => {
  mount([
    col({
      prefixedHandle: 'enabled',
      defaultValueInput: {type: 'lightswitch'},
    }),
  ]);

  // cast as the import casts it, so only empty and "0" are off
  chooseSource('active');
  await nextTick();

  const preview = cell('preview');
  const toggle = preview.querySelector('craft-switch') as HTMLElement & {
    checked?: boolean;
  };
  expect(toggle.checked).toBe(true);
  expect(toggle.hasAttribute('inert')).toBe(true);
  expect(preview.textContent).toContain('false');

  chooseSource('archived');
  await nextTick();

  expect(
    (preview.querySelector('craft-switch') as {checked?: boolean}).checked
  ).toBe(false);
  expect(preview.textContent).toContain('0');
});

it('previews a match-only column like any other', async () => {
  mount([col({prefixedHandle: 'id', canBeSet: false})]);

  expect(cell('preview').textContent!.trim()).toBe('');

  chooseSource('name');
  await nextTick();

  expect(cell('preview').textContent!.trim()).toBe('Ada Lovelace');
});

it('sets a default in the When empty popover, with a pill that sums the rule up, and no Default value column', async () => {
  mount([title]);

  const pill = () =>
    cell('whenEmpty')
      .querySelector('.setting-popover__summary')
      ?.textContent!.trim();

  expect(headers()).not.toContain('Default value');
  expect(pill()).toBeUndefined();

  chooseSource('name');
  await nextTick();

  expect(pill()).toBe('Skip');
  expect(cell('whenEmpty').querySelector('craft-input')).toBeNull();

  chooseEmptyValueMode('default');
  await nextTick();

  const input = cell('whenEmpty').querySelector<
    HTMLElement & {modelValue?: string}
  >('.setting-popover__body craft-input')!;
  input.modelValue = 'Untitled';
  input.dispatchEvent(
    new CustomEvent('model-value-changed', {bubbles: true, detail: {}})
  );
  await nextTick();

  expect(apply().defaultValues).toEqual({title: 'Untitled'});
  expect(pill()).toBe('Default · Untitled');

  chooseEmptyValueMode('skip');
  await nextTick();

  expect(pill()).toBe('Skip');
  expect(cell('whenEmpty').querySelector('craft-input')).toBeNull();
  expect(apply().defaultValues).toEqual({});
});

/** Each shown row's handle, or a heading row's text. */
function rowLabels(): string[] {
  return [...container.querySelectorAll('tbody tr:has(th)')].map((tr) =>
    (tr.querySelector('th pre') ?? tr.querySelector('th'))!.textContent!.trim()
  );
}

function searchFields(term: string): void {
  const input = container.querySelector<HTMLElement & {modelValue?: string}>(
    'craft-input-search'
  )!;
  input.modelValue = term;
  input.dispatchEvent(
    new CustomEvent('model-value-changed', {bubbles: true, detail: {}})
  );
}

it('searches the fields by name or handle', async () => {
  mount([title, col({prefixedHandle: 'summary', label: 'Excerpt'})]);

  searchFields('excerpt');
  await nextTick();

  expect(rowLabels()).toEqual(['summary']);

  searchFields('summ');
  await nextTick();

  expect(rowLabels()).toEqual(['summary']);

  searchFields('nothing like it');
  await nextTick();

  expect(container.querySelector('tbody')!.textContent).toContain(
    'No fields match.'
  );
});

it('keeps a compound column’s heading only while one of its subfields is shown', async () => {
  mount([
    {
      heading: 'Location',
      subfields: [
        col({prefixedHandle: 'lat', label: 'Latitude'}),
        col({prefixedHandle: 'lng', label: 'Longitude'}),
      ],
    } as MappingColumnEntry,
    title,
  ]);

  searchFields('latitude');
  await nextTick();

  expect(rowLabels()).toEqual(['Location', 'lat']);

  searchFields('title');
  await nextTick();

  expect(rowLabels()).toEqual(['title']);
});

/** The first row's Match checkbox. */
function matchBox(): (HTMLElement & {checked?: boolean}) | null {
  return container.querySelector(
    'tbody .mapping-table__cell--match craft-checkbox'
  );
}

function tickMatch(checked: boolean): void {
  const box = matchBox()!;
  box.checked = checked;
  box.dispatchEvent(
    new CustomEvent('model-value-changed', {bubbles: true, detail: {}})
  );
}

it('heads a Match column after Preview, empty until a row is mapped, then ticked to match rows on it', async () => {
  mount([title]);

  expect(headers()[3]).toMatch(/^Match/);
  expect(headers()[3]).toContain(
    'Existing elements are found by the ticked fields'
  );
  expect(cell('match').textContent!.trim()).toBe('');

  chooseSource('name');
  await nextTick();

  expect(matchBox()!.textContent!.trim()).toBe('Match on title');
  expect(matchBox()!.checked).toBe(false);

  tickMatch(true);
  await nextTick();

  expect(apply().matchCriteria).toEqual({title: '1'});
  expect(matchBox()!.checked).toBe(true);

  tickMatch(false);
  await nextTick();

  expect(apply().matchCriteria).toEqual({title: ''});
});

function destinationBadges(): string[] {
  return [...container.querySelectorAll('tbody th craft-badge')].map((badge) =>
    badge.textContent!.trim()
  );
}

it('badges a column rows are matched on as a Match Key, in place of Match only', async () => {
  mount([
    col({prefixedHandle: 'id', canBeSet: false, canBeMatchCriteria: true}),
  ]);

  expect(destinationBadges()).toEqual(['Match only']);

  chooseSource('name');
  await nextTick();
  tickMatch(true);
  await nextTick();

  expect(destinationBadges()).toEqual(['Match Key']);
  expect(
    container.querySelector('tbody th craft-badge')!.getAttribute('fill')
  ).toBe('blue');

  tickMatch(false);
  await nextTick();

  expect(destinationBadges()).toEqual(['Match only']);
});

it('offers no Match checkbox to a mapped column that can’t be matched on, or to one that isn’t mapped', () => {
  mount([relation, outerMatrix], {
    ...emptyValues(),
    map: {outerMatrix: {outerEt: {title: 'name'}}},
  });

  expect(
    [...container.querySelectorAll('tbody .mapping-table__cell--match')].map(
      (td) => td.textContent!.trim()
    )
  ).toEqual(['', '']);
  expect(matchBox()).toBeNull();
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

  expect(cell('incoming').querySelector('craft-select')).toBeNull();

  chooseSource('name');
  await nextTick();

  const select = cell('incoming').querySelector<
    HTMLElement & {modelValue?: string}
  >('craft-select')!;

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
    cell('incoming').querySelectorAll('craft-select')
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
    defaultValues: {},
    keepMissingNestedElements: {},
    fieldSettings: {},
    templates: {},
    matchMode: 'createAndUpdate',
  });
});

it('opens a container column’s nested mapping against the draft step', () => {
  mount([outerMatrix]);

  expect(container.querySelector('craft-select-rich')).toBeNull();

  container
    .querySelector('tbody craft-button')!
    .dispatchEvent(new Event('click'));

  expect(state.openNested).toHaveBeenCalledOnce();
  expect(state.openNested.mock.calls[0]![0]).toMatchObject({
    col: outerMatrix,
    step,
    editable: true,
  });
});

function badgedOptions(): string[] {
  return [
    ...sourceSelect().querySelectorAll(
      'craft-option:has(> craft-badge[slot="badge"])'
    ),
  ].map(
    (option) => (option as HTMLElement & {choiceValue: string}).choiceValue
  );
}

it('badges the suggested source column’s option, and goes on badging it once another is chosen', async () => {
  mount([title], {...emptyValues(), map: {title: 'name'}}, {title: true});

  expect(sourceSelect().modelValue).toBe('name');
  expect(badgedOptions()).toEqual(['name']);
  expect(
    sourceSelect()
      .querySelector('craft-badge[slot="badge"]')!
      .getAttribute('size')
  ).toBe('small');
  expect(
    sourceSelect()
      .querySelector('craft-badge[slot="badge"]')!
      .textContent!.trim()
  ).toBe('Suggested');

  chooseSource('email');
  await nextTick();

  expect(badgedOptions()).toEqual(['name']);
});

it('badges no option for a column the user mapped themselves', () => {
  mount([title], {...emptyValues(), map: {title: 'name'}});

  expect(badgedOptions()).toEqual([]);
});

it('keeps a freshly guessed column selected and badged once the select renders', async () => {
  const values = cloneValues(emptyValues());
  const suggestedMap: SuggestedMap = {};
  applySuggestions(values.map, {title: 'name'}, suggestedMap);

  mount([title], values, suggestedMap);

  expect(sourceSelect().modelValue).toBe('name');
  expect(badgedOptions()).toEqual(['name']);
  expect(apply().map).toEqual({title: 'name'});
});

it('merges a nested panel’s result back into the trees', async () => {
  mount([outerMatrix]);

  container
    .querySelector('tbody craft-button')!
    .dispatchEvent(new Event('click'));

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

it('isn’t dirty when a row is switched to Match and back again', async () => {
  mount([title], {...emptyValues(), map: {title: 'name'}});

  tickMatch(true);
  tickMatch(false);
  await nextTick();

  expect(state.layout.mock.calls.at(-1)![0].form.isDirty).toBe(false);
});

it('is dirty once a column is mapped', async () => {
  mount([title]);

  chooseSource('name');
  await nextTick();

  expect(state.layout.mock.calls.at(-1)![0].form.isDirty).toBe(true);
});

it('shows a template input only once “Use template” is chosen', async () => {
  mount([title]);

  expect(templateInput()).toBeNull();

  chooseSource(TEMPLATE_RULE);
  await nextTick();

  expect(templateInput()).not.toBeNull();
  expect(
    cell('preview').querySelector<HTMLElement & {triggers?: unknown}>(
      'craft-text-expander'
    )!.triggers
  ).toEqual(templateTextExpanderTriggers);
});

it('explains how to insert a variable in a tip below the template', async () => {
  mount([title]);

  chooseSource(TEMPLATE_RULE);
  await nextTick();

  const field = cell('preview').querySelector('craft-field')!;

  expect(field.querySelector('[slot="tip"]')!.textContent!.trim()).toBe(
    'Type { to choose an object template variable.'
  );
  expect(field.querySelector('craft-info-icon')).toBeNull();
});

it('writes a column’s template to the templates tree, validated before it’s handed back', async () => {
  mount([title]);

  chooseSource(TEMPLATE_RULE);
  await nextTick();
  typeTemplate('{name} <{email}>');

  const values = await applyAsync();

  expect(values!.map).toEqual({title: TEMPLATE_RULE});
  expect(values!.templates).toEqual({title: '{name} <{email}>'});
  expect(state.validateStep).toHaveBeenCalledWith(
    expect.objectContaining({
      uid: 'step-uid',
      settings: expect.objectContaining({
        map: {title: TEMPLATE_RULE},
        templates: {title: '{name} <{email}>'},
      }),
    })
  );
});

it('keeps the panel open and shows the server’s template errors on the column', async () => {
  state.validateStep.mockRejectedValue({
    response: {
      data: {
        errors: {
          'settings.templates.title': ['The template couldn’t be parsed.'],
          source: ['Not about templates.'],
        },
      },
    },
  });
  mount([title], {
    ...emptyValues(),
    map: {title: TEMPLATE_RULE},
    templates: {title: '{{ name'},
  });

  expect(await applyAsync()).toBeNull();
  await nextTick();

  expect(
    [...cell('preview').querySelectorAll('.error-list li')].map((li) =>
      li.textContent!.trim()
    )
  ).toEqual(['The template couldn’t be parsed.']);
  expect(templateInput().getAttribute('aria-invalid')).toBe('true');
  expect(document.activeElement).toBe(
    cell('preview').querySelector('craft-input input')
  );
});

it('doesn’t validate a mapping that uses no templates before handing it back', async () => {
  mount([title], {...emptyValues(), map: {title: 'name'}});

  await applyAsync();

  expect(state.validateStep).not.toHaveBeenCalled();
});

it('hangs each destination’s field type icon off to the left of its label and handle', () => {
  mount([
    col({prefixedHandle: 'title', label: 'Title', icon: 'i-cursor'}),
    col({prefixedHandle: 'other'}),
  ]);

  const cells = [...container.querySelectorAll('tbody th .destination-cell')];
  const icon = (cell: Element) =>
    cell.querySelector('.destination-cell__icon craft-icon') as
      | (HTMLElement & {name?: string})
      | null;

  // the slot is there either way, so every label and handle start at the same place
  expect(
    cells.map((cell) =>
      cell.firstElementChild!.classList.contains('destination-cell__icon')
    )
  ).toEqual([true, true]);
  expect(icon(cells[0]!)!.name ?? icon(cells[0]!)!.getAttribute('name')).toBe(
    'i-cursor'
  );
  expect(icon(cells[1]!)).toBeNull();
});
