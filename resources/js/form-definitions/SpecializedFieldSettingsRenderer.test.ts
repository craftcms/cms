import {createApp, nextTick, reactive} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import type CraftCheckbox from '@craftcms/ui/components/checkbox/checkbox';
import FormDefinitionRenderer from './FormDefinitionRenderer.vue';
import CheckboxSelectInputRenderer from './renderers/CheckboxSelectInputRenderer.vue';
import EditableTableInputRenderer from './renderers/EditableTableInputRenderer.vue';
import LightswitchInputRenderer from './renderers/LightswitchInputRenderer.vue';
import SelectInputRenderer from './renderers/SelectInputRenderer.vue';

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

    expect(
      Array.from(
        container.querySelectorAll<HTMLElement>(
          '[data-editable-table="columns"] [data-editable-table-row]'
        ),
        (row) => row.dataset.rowKey
      )
    ).toEqual(['headline', 'published', 'category']);
    expect(
      container.querySelector<HTMLInputElement>(
        'input[name="settings[defaults][0][rowId]"]'
      )!.value
    ).toBe('first-row');

    const optionValue = container.querySelector<
      HTMLElementTagNameMap['craft-input']
    >('[data-table-nested-options] [data-option-row="0"] [data-option-value]')!;
    optionValue.value = 'article';
    optionValue.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    expect(values.settings.columns.category.options[0]!.value).toBe('article');

    container
      .querySelector<HTMLElement>(
        '[data-editable-table="columns"] [data-row-key="category"] craft-reorder-button'
      )!
      .dispatchEvent(
        new CustomEvent('reorder', {
          bubbles: true,
          detail: {direction: 'up'},
        })
      );
    await nextTick();

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
        container.querySelectorAll(
          '[data-editable-table="defaults"] thead th:not(:last-child)'
        ),
        (heading) => heading.textContent?.trim()
      )
    ).toEqual(['Headline', 'Category', 'Published?']);

    const categoryHeading = container.querySelector<
      HTMLElementTagNameMap['craft-input']
    >('[data-row-key="category"] [data-table-cell="category:heading"]')!;
    categoryHeading.value = 'Topic';
    categoryHeading.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    expect(
      container.querySelector(
        '[data-editable-table="defaults"] thead th:nth-child(2)'
      )?.textContent
    ).toContain('Topic');

    const published = container.querySelector<CraftCheckbox>(
      'craft-checkbox[name="settings[defaults][1][published]"]'
    )!;
    published.checked = true;
    published.dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();

    expect(values.settings.defaults[1]!.published).toBe(true);

    container
      .querySelector<HTMLElement>(
        '[data-editable-table="defaults"] [data-row-key="second-row"] craft-reorder-button'
      )!
      .dispatchEvent(
        new CustomEvent('reorder', {
          bubbles: true,
          detail: {direction: 'up'},
        })
      );
    await nextTick();

    expect(values.settings.defaults.map((row) => row.rowId)).toEqual([
      'second-row',
      'first-row',
    ]);

    while (
      container.querySelector(
        '[data-editable-table="columns"] [data-delete-row]'
      )
    ) {
      container
        .querySelector<HTMLElement>(
          '[data-editable-table="columns"] [data-delete-row]'
        )!
        .click();
      await nextTick();
    }

    expect(values.settings.defaults).toEqual([]);
    expect(
      container.querySelector<HTMLElementTagNameMap['craft-button']>(
        '[data-editable-table="defaults"] [data-add-row]'
      )!.disabled
    ).toBe(true);
  });

  it('honors generic read-only state for editable tables', () => {
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

    expect(
      Array.from(
        container.querySelectorAll<HTMLElement>(
          'craft-input, craft-select, craft-checkbox, craft-switch, craft-button, craft-reorder-button'
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
    const row = container.querySelector<HTMLElement>(
      '[data-editable-table="defaults"] [data-editable-table-row]'
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
    await nextTick();

    expect(
      container.querySelector(
        '[data-editable-table="defaults"] [data-editable-table-row]'
      )
    ).toBe(row);
    expect(values.settings.defaults[0]).toEqual({
      headline: 'Ready',
      rowId,
    });
  });
});

function mount(
  definition: CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData,
  values: Record<string, unknown>,
  readOnly = false
): HTMLElement {
  const registry = createCpComponentRegistry();
  const container = document.createElement('div');

  registry.register(
    'form-element:craft:checkbox-select-input',
    CheckboxSelectInputRenderer
  );
  registry.register(
    'form-element:craft:editable-table-input',
    EditableTableInputRenderer
  );
  registry.register(
    'form-element:craft:lightswitch-input',
    LightswitchInputRenderer
  );
  registry.register('form-element:craft:select-input', SelectInputRenderer);
  (window as any).Cp = {$components: registry};
  document.body.appendChild(container);
  const app = createApp(FormDefinitionRenderer, {
    definition,
    bindingScope: 'settings',
    values,
    errors: {},
    readOnly,
  });

  mountedApps.push(app);
  app.mount(container);

  return container;
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
      columnsFrom: 'columns',
    }),
  ],
} satisfies CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData;

function field(
  type: string,
  name: string,
  props?: Record<string, CraftCms.Cms.Cp.FormDefinitions.Data.JsonValue>
) {
  return {type: 'craft:field', children: [{type, name, props}]};
}
