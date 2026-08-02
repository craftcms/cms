import {createApp, nextTick, reactive} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import type CraftCheckbox from '@craftcms/ui/components/checkbox/checkbox';
import CraftCheckboxSelect from '@craftcms/ui/vue/CraftCheckboxSelect.vue';
import CraftEditableTable from '@craftcms/ui/vue/CraftEditableTable.vue';
import CraftSelect from '@craftcms/ui/vue/CraftSelect.vue';
import CraftSwitch from '@craftcms/ui/vue/CraftSwitch.vue';
import '@craftcms/ui/components/checkbox-select/checkbox-select';
import '@craftcms/ui/components/editable-table/editable-table';
import '@craftcms/ui/components/select/select';
import FormDefinitionRenderer from './FormDefinitionRenderer.vue';

const mountedApps: Array<ReturnType<typeof createApp>> = [];

beforeEach(() => {
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue(
      new Response('<svg xmlns="http://www.w3.org/2000/svg"></svg>', {
        status: 200,
      })
    )
  );
});

afterEach(() => {
  vi.unstubAllGlobals();
  mountedApps.splice(0).forEach((app) => app.unmount());
  document.body.innerHTML = '';
});

describe('specialized field settings renderer', () => {
  it('renders conditional Markdown settings while preserving hidden values', async () => {
    const values = reactive({
      settings: {
        flavor: 'gfm',
        showToolbar: true,
        toolbarButtons: ['bold', 'link'],
      },
    });
    const container = mount(markdownDefinition, values);
    const toolbarField = container
      .querySelector<HTMLInputElement>(
        'input[name="settings[toolbarButtons][]"]'
      )!
      .closest<HTMLElement>('[data-form-element="craft:field"]')!;
    const showToolbar =
      container.querySelector<HTMLElementTagNameMap['craft-switch']>(
        'craft-switch'
      )!;

    expect(
      container.querySelector<HTMLElementTagNameMap['craft-select']>(
        'craft-select'
      )!.modelValue
    ).toBe('gfm');
    expect(toolbarField.style.display).toBe('');
    expect(
      Array.from(
        toolbarField.querySelectorAll<HTMLElementTagNameMap['craft-icon']>(
          'craft-icon'
        ),
        (icon) => icon.name
      )
    ).toEqual(['bold', 'link']);

    showToolbar.checked = false;
    showToolbar.dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();

    expect(toolbarField.style.display).toBe('none');
    expect(values.settings.toolbarButtons).toEqual(['bold', 'link']);
  });

  it('edits keyed columns, nested dropdown options, and ordered default rows', async () => {
    const values = reactive({
      settings: {
        columns: {
          headline: {
            heading: 'Headline',
            handle: 'headline',
            width: '40%',
            type: 'singleline',
          },
          published: {
            heading: 'Published?',
            handle: 'published',
            width: '15%',
            type: 'checkbox',
          },
          category: {
            heading: 'Category',
            handle: 'category',
            width: '45%',
            type: 'select',
            options: [
              {label: 'News', value: 'news', default: true},
              {label: 'Opinion', value: 'opinion', default: false},
            ],
          },
        },
        defaults: [
          {
            rowId: 'first-row',
            headline: 'Lead story',
            published: true,
            category: 'news',
          },
          {
            rowId: 'second-row',
            headline: 'Analysis',
            published: false,
            category: 'opinion',
          },
        ],
      },
    });
    const container = mount(tableDefinition, values);
    const [columnsTable, defaultsTable] = Array.from(
      container.querySelectorAll<HTMLElementTagNameMap['craft-editable-table']>(
        'craft-editable-table'
      )
    ) as [
      HTMLElementTagNameMap['craft-editable-table'],
      HTMLElementTagNameMap['craft-editable-table'],
    ];

    await settleTables(columnsTable, defaultsTable);
    const columnsRoot = columnsTable.shadowRoot!;
    const defaultsRoot = defaultsTable.shadowRoot!;

    expect(columnsTable.name).toBe('settings[columns]');
    expect(columnsTable.sourceName).toBe('columnDefinitions');
    expect(defaultsTable.name).toBe('settings[defaults]');
    expect(defaultsTable.sourceName).toBe('defaultRows');
    expect(
      Array.from(
        columnsRoot.querySelectorAll<HTMLElement>('[data-editable-table-row]'),
        (row) => row.dataset.rowKey
      )
    ).toEqual(['headline', 'published', 'category']);
    expect(
      container.querySelector<HTMLInputElement>(
        'input[name="settings[defaults][0][rowId]"]'
      )!.value
    ).toBe('first-row');

    const optionRows = columnsRoot.querySelector<
      HTMLElementTagNameMap['craft-option-rows']
    >('[data-table-nested-options] craft-option-rows')!;
    await optionRows.updateComplete;
    const optionValue = optionRows.shadowRoot!.querySelector<
      HTMLElementTagNameMap['craft-input']
    >('[data-option-row="0"] [data-option-value]')!;
    optionValue.value = 'article';
    optionValue.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    expect(values.settings.columns.category.options[0]!.value).toBe('article');

    columnsRoot
      .querySelector<HTMLElement>(
        '[data-row-key="category"] craft-reorder-button'
      )!
      .dispatchEvent(
        new CustomEvent('reorder', {
          bubbles: true,
          detail: {direction: 'up'},
        })
      );
    await settleTables(columnsTable, defaultsTable);

    expect(Object.keys(values.settings.columns)).toEqual([
      'headline',
      'category',
      'published',
    ]);
    expect(Object.keys(values.settings.defaults[0]!)).toEqual([
      'rowId',
      'headline',
      'category',
      'published',
    ]);
    expect(
      Array.from(
        defaultsRoot.querySelectorAll('thead th:not(:last-child)'),
        (heading) => heading.textContent?.trim()
      )
    ).toEqual(['Headline', 'Category', 'Published?']);

    const categoryHeading = columnsRoot.querySelector<
      HTMLElementTagNameMap['craft-input']
    >('[data-row-key="category"] [data-table-cell="category:heading"]')!;
    categoryHeading.value = 'Topic';
    categoryHeading.dispatchEvent(new Event('input', {bubbles: true}));
    await settleTables(columnsTable, defaultsTable);

    expect(
      defaultsRoot.querySelector('thead th:nth-child(2)')?.textContent
    ).toContain('Topic');

    const published = defaultsRoot.querySelector<CraftCheckbox>(
      'craft-checkbox[name="settings[defaults][1][published]"]'
    )!;
    published.checked = true;
    published.dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();

    expect(values.settings.defaults[1]!.published).toBe(true);

    defaultsRoot
      .querySelector<HTMLElement>(
        '[data-row-key="second-row"] craft-reorder-button'
      )!
      .dispatchEvent(
        new CustomEvent('reorder', {
          bubbles: true,
          detail: {direction: 'up'},
        })
      );
    await settleTables(columnsTable, defaultsTable);

    expect(values.settings.defaults.map((row) => row.rowId)).toEqual([
      'second-row',
      'first-row',
    ]);

    while (columnsRoot.querySelector('[data-delete-row]')) {
      columnsRoot.querySelector<HTMLElement>('[data-delete-row]')!.click();
      await settleTables(columnsTable, defaultsTable);
    }

    expect(values.settings.defaults).toEqual([]);
    expect(
      defaultsRoot.querySelector<HTMLElementTagNameMap['craft-button']>(
        '[data-add-row]'
      )!.disabled
    ).toBe(true);
  });

  it('honors generic read-only state for editable tables', async () => {
    const container = mount(
      tableDefinition,
      {
        settings: {
          columns: {
            headline: {
              heading: 'Headline',
              handle: 'headline',
              width: '',
              type: 'singleline',
            },
          },
          defaults: [],
        },
      },
      true
    );
    const tables = Array.from(
      container.querySelectorAll<HTMLElementTagNameMap['craft-editable-table']>(
        'craft-editable-table'
      )
    );

    await settleTables(...tables);

    expect(
      tables
        .flatMap((table) =>
          Array.from(
            table.shadowRoot!.querySelectorAll<HTMLElement>(
              'craft-input, craft-select, craft-checkbox, craft-switch, craft-button, craft-reorder-button'
            )
          )
        )
        .filter(
          (control) =>
            !(control as {readOnly?: boolean}).readOnly &&
            !(control as {disabled?: boolean}).disabled
        )
        .map((control) => control.outerHTML)
    ).toEqual([]);
  });

  it('preserves default row identity when the saved row has no UUID', async () => {
    const values = reactive({
      settings: {
        columns: {},
        defaults: [{headline: 'Draft'}],
      },
    });
    const container = mount(tableDefinition, values);
    const defaultsTable = container.querySelectorAll<
      HTMLElementTagNameMap['craft-editable-table']
    >('craft-editable-table')[1]!;

    await settleTables(defaultsTable);
    const row = defaultsTable.shadowRoot!.querySelector<HTMLElement>(
      '[data-editable-table-row]'
    )!;
    const input =
      row.querySelector<HTMLElementTagNameMap['craft-input']>(
        '[data-table-cell]'
      )!;
    const rowId = container.querySelector<HTMLInputElement>(
      'input[name="settings[defaults][0][rowId]"]'
    )!.value;

    input.value = 'Ready';
    input.dispatchEvent(new Event('input', {bubbles: true}));
    await settleTables(defaultsTable);

    expect(
      defaultsTable.shadowRoot!.querySelector('[data-editable-table-row]')
    ).toBe(row);
    expect(values.settings.defaults[0]).toEqual({
      headline: 'Ready',
      rowId,
    });
  });

  it('isolates editable table column coordination by Form Definition', async () => {
    const first = mount(tableDefinition, {
      settings: {
        columns: {
          first: {
            heading: 'First heading',
            handle: 'first',
            width: '',
            type: 'singleline',
          },
        },
        defaults: [{rowId: 'first-row', first: 'First value'}],
      },
    });
    const second = mount(tableDefinition, {
      settings: {
        columns: {
          second: {
            heading: 'Second heading',
            handle: 'second',
            width: '',
            type: 'singleline',
          },
        },
        defaults: [{rowId: 'second-row', second: 'Second value'}],
      },
    });
    const firstTables = Array.from(
      first.querySelectorAll<HTMLElementTagNameMap['craft-editable-table']>(
        'craft-editable-table'
      )
    );
    const secondTables = Array.from(
      second.querySelectorAll<HTMLElementTagNameMap['craft-editable-table']>(
        'craft-editable-table'
      )
    );

    await settleTables(...firstTables, ...secondTables);

    expect(
      firstTables[1]!.shadowRoot!.querySelector('thead th')?.textContent
    ).toContain('First heading');
    expect(
      secondTables[1]!.shadowRoot!.querySelector('thead th')?.textContent
    ).toContain('Second heading');
  });

  it('wires editable table labels and errors through the mounted field', async () => {
    const container = mount(
      accessibleEditableTableDefinition,
      {settings: {rows: []}},
      false,
      {'settings.rows': ['Add at least one row.']}
    );
    const field =
      container.querySelector<HTMLElementTagNameMap['craft-field']>(
        'craft-field'
      )!;
    const table = container.querySelector<
      HTMLElementTagNameMap['craft-editable-table']
    >('craft-editable-table')!;

    await nextTick();
    await field.updateComplete;
    await field.updateComplete;

    const label = field.querySelector<HTMLElement>('[slot="label"]')!;
    const feedback = field.querySelector<HTMLElement>('[slot="feedback"]')!;

    expect(feedback.textContent).toContain('Add at least one row.');
    expect(table.getAttribute('aria-labelledby')?.split(/\s+/)).toContain(
      label.id
    );
    expect(table.getAttribute('aria-describedby')?.split(/\s+/)).toContain(
      feedback.id
    );
  });
});

function mount(
  definition: CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData,
  values: Record<string, unknown>,
  readOnly = false,
  errors: Record<string, string | string[]> = {}
): HTMLElement {
  const registry = createCpComponentRegistry();
  const container = document.createElement('div');

  registry.register(
    'form-element:craft:checkbox-select-input',
    CraftCheckboxSelect
  );
  registry.register(
    'form-element:craft:editable-table-input',
    CraftEditableTable
  );
  registry.register('form-element:craft:lightswitch-input', CraftSwitch);
  registry.register('form-element:craft:select-input', CraftSelect);
  (window as any).Cp = {$components: registry};
  document.body.appendChild(container);
  const app = createApp(FormDefinitionRenderer, {
    definition,
    bindingScope: 'settings',
    values,
    errors,
    readOnly,
  });

  mountedApps.push(app);
  app.mount(container);

  return container;
}

async function settleTables(
  ...tables: Array<HTMLElementTagNameMap['craft-editable-table']>
): Promise<void> {
  await nextTick();
  await Promise.all(tables.map((table) => table.updateComplete));
}

const markdownDefinition = {
  elements: [
    field('craft:select-input', 'flavor', {
      options: [
        {label: 'Original', value: 'original'},
        {label: 'GitHub-Flavored Markdown', value: 'gfm'},
      ],
    }),
    field('craft:lightswitch-input', 'showToolbar'),
    {
      ...field('craft:checkbox-select-input', 'toolbarButtons', {
        options: [
          {label: 'Bold', value: 'bold', icon: 'bold'},
          {label: 'Link', value: 'link', icon: 'link'},
        ],
      }),
      visibleWhen: {
        name: 'showToolbar',
        operator: 'equals',
        value: true,
      } as const,
    },
  ],
} satisfies CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData;

const tableDefinition = {
  elements: [
    field('craft:editable-table-input', 'columns', {
      columns: [
        {
          key: 'heading',
          label: 'Column Heading',
          type: 'text',
          autoPopulate: 'handle',
        },
        {key: 'handle', label: 'Handle', type: 'text', code: true},
        {key: 'width', label: 'Width', type: 'text', width: 50},
        {
          key: 'type',
          label: 'Type',
          type: 'select',
          nestedOptions: true,
          options: [
            {label: 'Checkbox', value: 'checkbox'},
            {label: 'Dropdown', value: 'select'},
            {label: 'Single-line text', value: 'singleline'},
          ],
        },
      ],
      addRowLabel: 'Add a column',
      defaultRow: {heading: '', handle: '', width: '', type: 'singleline'},
      keyed: true,
      definesColumns: true,
      sourceName: 'columnDefinitions',
    }),
    field('craft:editable-table-input', 'defaults', {
      columns: [
        {key: 'headline', label: 'Headline', type: 'text', width: '40%'},
        {
          key: 'published',
          label: 'Published?',
          type: 'checkbox',
          width: '15%',
        },
        {
          key: 'category',
          label: 'Category',
          type: 'select',
          width: '45%',
          options: [
            {label: 'News', value: 'news', default: true},
            {label: 'Opinion', value: 'opinion', default: false},
          ],
        },
      ],
      addRowLabel: 'Add a row',
      includeRowId: true,
      columnsFrom: 'columnDefinitions',
      sourceName: 'defaultRows',
    }),
  ],
} satisfies CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData;

const accessibleEditableTableDefinition = {
  elements: [
    {
      type: 'craft:field',
      props: {label: 'Rows', instructions: 'Add the table rows.'},
      children: [
        {
          type: 'craft:editable-table-input',
          name: 'rows',
          props: {
            columns: [{key: 'title', label: 'Title', type: 'text'}],
          },
        },
      ],
    },
  ],
} satisfies CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData;

function field(
  type: string,
  name: string,
  props?: Record<string, CraftCms.Cms.Cp.FormDefinitions.Data.JsonValue>
) {
  return {type: 'craft:field', children: [{type, name, props}]};
}
