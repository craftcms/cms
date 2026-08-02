import {
  createApp,
  defineComponent,
  h,
  nextTick,
  reactive,
  ref,
  toRaw,
} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import CraftFieldLayout from '@craftcms/ui/vue/CraftFieldLayout.vue';
import CraftKeyedTable from '@craftcms/ui/vue/CraftKeyedTable.vue';
import CraftObjectSelect from '@craftcms/ui/vue/CraftObjectSelect.vue';
import CraftSwitch from '@craftcms/ui/vue/CraftSwitch.vue';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormDefinitionRenderer from './FormDefinitionRenderer.vue';
import CheckboxSelectInputRenderer from './renderers/CheckboxSelectInputRenderer.vue';
import SelectInputRenderer from './renderers/SelectInputRenderer.vue';

const mountedApps: Array<ReturnType<typeof createApp>> = [];

afterEach(() => {
  mountedApps.splice(0).forEach((app) => app.unmount());
  document.body.innerHTML = '';
});

describe('nested content settings renderers', () => {
  it('updates object selections and exhausts available options without restricting host state', async () => {
    const entryTypeMarker = new Map([['source', 'host']]);
    const article = {uid: 'article', name: 'Article', marker: entryTypeMarker};
    const page = {uid: 'page', name: 'Page'};
    const values = reactive({
      settings: {
        entryTypes: [article, page],
      },
    });
    const container = mount(values);
    const objectSelect = container.querySelector<
      HTMLElementTagNameMap['craft-object-select']
    >('craft-object-select')!;

    await nextTick();
    await objectSelect.updateComplete;
    objectSelect
      .shadowRoot!.querySelector(
        '[data-object-select-row="article"] craft-reorder-button'
      )!
      .dispatchEvent(
        new CustomEvent('reorder', {
          bubbles: true,
          detail: {direction: 'down'},
        })
      );
    await nextTick();

    expect(values.settings.entryTypes.map(({uid}) => uid)).toEqual([
      'page',
      'article',
    ]);
    expect(
      (toRaw(values.settings.entryTypes[1]!) as {marker?: unknown}).marker
    ).toBe(entryTypeMarker);

    const select = objectSelect.shadowRoot!.querySelector<HTMLSelectElement>(
      '[data-object-select-available]'
    )!;

    select.value = 'news';
    select.dispatchEvent(new Event('change', {bubbles: true}));
    objectSelect
      .shadowRoot!.querySelector('[data-object-select-add]')!
      .dispatchEvent(new CustomEvent('activate', {bubbles: true}));
    await nextTick();

    expect(values.settings.entryTypes.map(({uid}) => uid)).toEqual([
      'page',
      'article',
      'news',
    ]);
    expect(
      (toRaw(values.settings.entryTypes[1]!) as {marker?: unknown}).marker
    ).toBe(entryTypeMarker);
    expect(
      objectSelect.shadowRoot!.querySelector('[data-object-select-add]')
    ).toBeNull();
  });

  it('reorders field layout elements without changing other layout state', async () => {
    const layoutMarker = Symbol('layout-host-state');
    const values = reactive({
      settings: {
        fieldLayouts: {
          'layout-1': {
            tabs: [
              {
                uid: 'content-tab',
                name: 'Content',
                elements: [
                  {uid: 'title-element', type: 'TitleField'},
                  {uid: 'body-element', type: 'BodyField'},
                ],
              },
            ],
            generatedFields: [],
            marker: layoutMarker,
          },
        },
      },
    });
    const container = mount(values);
    const fieldLayout =
      container.querySelector<HTMLElementTagNameMap['craft-field-layout']>(
        'craft-field-layout'
      )!;

    await nextTick();
    await fieldLayout.updateComplete;
    const layoutRows = fieldLayout.shadowRoot!.querySelectorAll<HTMLElement>(
      '[data-field-layout-element]'
    );

    layoutRows[0]!.querySelector('craft-reorder-button')!.dispatchEvent(
      new CustomEvent('reorder', {
        bubbles: true,
        detail: {direction: 'down'},
      })
    );
    await nextTick();

    expect(
      values.settings.fieldLayouts['layout-1'].tabs[0]!.elements.map(
        ({uid}) => uid
      )
    ).toEqual(['body-element', 'title-element']);
    expect(values.settings.fieldLayouts['layout-1'].marker).toBe(layoutMarker);
  });

  it('adds and removes field layout elements through the mounted renderer', async () => {
    const values = reactive({
      settings: {
        fieldLayouts: {
          'layout-1': {
            tabs: [
              {
                uid: 'content-tab',
                name: 'Content',
                elements: [{uid: 'title-element', type: 'TitleField'}],
              },
            ],
          },
        },
      },
    });
    const container = mount(values);
    const fieldLayout =
      container.querySelector<HTMLElementTagNameMap['craft-field-layout']>(
        'craft-field-layout'
      )!;

    await nextTick();
    await fieldLayout.updateComplete;
    const select = fieldLayout.shadowRoot!.querySelector<HTMLSelectElement>(
      '[data-field-layout-available]'
    )!;

    select.value = 'field:body';
    select.dispatchEvent(new Event('change', {bubbles: true}));
    fieldLayout
      .shadowRoot!.querySelector('[data-field-layout-add]')!
      .dispatchEvent(new CustomEvent('activate', {bubbles: true}));
    await nextTick();
    await fieldLayout.updateComplete;

    expect(
      values.settings.fieldLayouts['layout-1'].tabs[0]!.elements.map(
        ({type}) => type
      )
    ).toEqual(['TitleField', 'BodyField']);

    const bodyRow = Array.from(
      fieldLayout.shadowRoot!.querySelectorAll<HTMLElement>(
        '[data-field-layout-element]'
      )
    ).find((row) => row.textContent?.includes('Body'))!;

    bodyRow
      .querySelector('[data-field-layout-remove]')!
      .dispatchEvent(new CustomEvent('activate', {bubbles: true}));
    await nextTick();

    expect(
      values.settings.fieldLayouts['layout-1'].tabs[0]!.elements.map(
        ({type}) => type
      )
    ).toEqual(['TitleField']);
  });

  it('preserves field layout controls across complete definition refreshes', async () => {
    const registry = createCpComponentRegistry();
    const formDefinition = ref(accessibleFieldLayoutDefinition);
    const values = reactive({
      settings: {
        fieldLayouts: {
          'layout-1': {
            tabs: [
              {
                uid: 'content-tab',
                name: 'Content',
                elements: [{uid: 'title-element', type: 'TitleField'}],
              },
            ],
          },
        },
      },
    });
    const container = document.createElement('div');
    const Host = defineComponent(
      () => () =>
        h(FormDefinitionRenderer, {
          definition: formDefinition.value,
          bindingScope: 'settings',
          values,
          errors: {},
        })
    );

    registry.register(
      'form-element:craft:field-layout-input',
      CraftFieldLayout
    );
    (window as any).Cp = {$components: registry};
    document.body.append(container);
    const app = createApp(Host);

    mountedApps.push(app);
    app.mount(container);
    await nextTick();

    const fieldLayout =
      container.querySelector<HTMLElementTagNameMap['craft-field-layout']>(
        'craft-field-layout'
      )!;

    await fieldLayout.updateComplete;
    const tab = fieldLayout.shadowRoot!.querySelector(
      '[data-field-layout-tab="content-tab"]'
    );
    const row = fieldLayout.shadowRoot!.querySelector(
      '[data-field-layout-element="title-element"]'
    );

    formDefinition.value = structuredClone(accessibleFieldLayoutDefinition);
    await nextTick();
    await fieldLayout.updateComplete;

    expect(container.querySelector('craft-field-layout')).toBe(fieldLayout);
    expect(
      fieldLayout.shadowRoot!.querySelector(
        '[data-field-layout-tab="content-tab"]'
      )
    ).toBe(tab);
    expect(
      fieldLayout.shadowRoot!.querySelector(
        '[data-field-layout-element="title-element"]'
      )
    ).toBe(row);
  });

  it('reorders generated fields without changing their configuration', async () => {
    const values = reactive({
      settings: {
        fieldLayouts: {
          'layout-1': {
            tabs: [],
            generatedFields: [
              {uid: 'reading-time', name: 'Reading time', template: 'read'},
              {uid: 'summary', name: 'Summary', template: 'summary'},
            ],
          },
        },
      },
    });
    const container = mount(values);
    const fieldLayout =
      container.querySelector<HTMLElementTagNameMap['craft-field-layout']>(
        'craft-field-layout'
      )!;

    await nextTick();
    await fieldLayout.updateComplete;
    const generatedFields =
      fieldLayout.shadowRoot!.querySelectorAll<HTMLElement>(
        '[data-generated-field]'
      );

    generatedFields[0]!.querySelector('craft-reorder-button')!.dispatchEvent(
      new CustomEvent('reorder', {
        bubbles: true,
        detail: {direction: 'down'},
      })
    );
    await nextTick();

    expect(values.settings.fieldLayouts['layout-1'].generatedFields).toEqual([
      {uid: 'summary', name: 'Summary', template: 'summary'},
      {uid: 'reading-time', name: 'Reading time', template: 'read'},
    ]);
  });

  it('edits generated field templates as multiline text', async () => {
    const values = reactive({
      settings: {
        fieldLayouts: {
          'layout-1': {
            tabs: [],
            generatedFields: [
              {uid: 'summary', name: 'Summary', template: 'line one'},
            ],
          },
        },
      },
    });
    const container = mount(values);
    const fieldLayout =
      container.querySelector<HTMLElementTagNameMap['craft-field-layout']>(
        'craft-field-layout'
      )!;

    await nextTick();
    await fieldLayout.updateComplete;
    const template = fieldLayout.shadowRoot!.querySelector<HTMLTextAreaElement>(
      'textarea[aria-label="Template"]'
    )!;

    template.value = 'line one\nline two';
    template.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    expect(
      values.settings.fieldLayouts['layout-1'].generatedFields[0]!.template
    ).toBe('line one\nline two');
  });

  it('updates one keyed table cell without replacing sibling values', async () => {
    const values = reactive({
      settings: {
        siteSettings: {
          english: {uriFormat: 'news/{slug}', template: 'entries/article'},
        },
      },
    });
    const container = mount(values);
    const table =
      container.querySelector<HTMLElementTagNameMap['craft-keyed-table']>(
        'craft-keyed-table'
      )!;

    await nextTick();
    await table.updateComplete;
    const uriInput = table.shadowRoot!.querySelector<
      HTMLElementTagNameMap['craft-input']
    >('[data-keyed-table-cell="english:uriFormat"]')!;

    uriInput.value = 'stories/{slug}';
    uriInput.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();
    await table.updateComplete;

    expect(values.settings.siteSettings.english).toEqual({
      uriFormat: 'stories/{slug}',
      template: 'entries/article',
    });
  });

  it('uses generic visibility without clearing hidden nested settings', async () => {
    const values = reactive({
      settings: {
        viewMode: 'index',
        includeTableView: true,
        defaultTableColumns: ['title'],
      },
    });
    const container = mount(values);
    const viewMode = container.querySelector<HTMLSelectElement>(
      'craft-select select'
    )!;

    viewMode.value = 'cards';
    viewMode.dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();

    expect(
      container
        .querySelector('input[name="settings[defaultTableColumns][]"]')!
        .closest<HTMLElement>('[data-form-element]')!.style.display
    ).toBe('none');
    expect(values.settings.defaultTableColumns).toEqual(['title']);
  });

  it('cannot weaken host read-only state for complex controls', async () => {
    const values = reactive({
      settings: {
        fieldLayouts: {
          'layout-1': {
            tabs: [
              {
                uid: 'content-tab',
                elements: [{uid: 'title-element', type: 'TitleField'}],
              },
            ],
            generatedFields: [],
          },
        },
        entryTypes: [{uid: 'article'}],
        siteSettings: {english: {uriFormat: 'news/{slug}'}},
        viewMode: 'index',
        includeTableView: true,
        defaultTableColumns: ['title'],
      },
    });
    const container = mount(values, true);
    const keyedTable =
      container.querySelector<HTMLElementTagNameMap['craft-keyed-table']>(
        'craft-keyed-table'
      )!;
    const fieldLayout =
      container.querySelector<HTMLElementTagNameMap['craft-field-layout']>(
        'craft-field-layout'
      )!;

    await nextTick();
    await keyedTable.updateComplete;
    await fieldLayout.updateComplete;

    expect(
      [
        ...Array.from(
          container.querySelectorAll<
            HTMLElement & {disabled?: boolean; readOnly?: boolean}
          >('craft-reorder-button, craft-button, craft-input')
        ),
        ...Array.from(
          keyedTable.shadowRoot!.querySelectorAll<
            HTMLElement & {disabled?: boolean; readOnly?: boolean}
          >('craft-input')
        ),
      ].every((control) => control.disabled || control.readOnly)
    ).toBe(true);
    expect(fieldLayout.readOnly).toBe(true);
    expect(
      Array.from(
        fieldLayout.shadowRoot!.querySelectorAll<
          HTMLElement & {disabled?: boolean}
        >('craft-reorder-button, craft-button, craft-input, select, textarea')
      ).every((control) => control.disabled)
    ).toBe(true);
  });

  it('wires field layout errors, read-only state, and accessibility through the mounted field', async () => {
    const container = mount(
      {
        settings: {
          fieldLayouts: {
            'layout-1': {
              tabs: [{uid: 'content-tab', name: 'Content', elements: []}],
            },
          },
        },
      },
      true,
      {'settings.fieldLayouts.layout-1': ['Add at least one field.']},
      accessibleFieldLayoutDefinition
    );
    const field =
      container.querySelector<HTMLElementTagNameMap['craft-field']>(
        'craft-field'
      )!;
    const fieldLayout =
      container.querySelector<HTMLElementTagNameMap['craft-field-layout']>(
        'craft-field-layout'
      )!;

    await nextTick();
    await field.updateComplete;
    await field.updateComplete;
    await fieldLayout.updateComplete;

    const label = field.querySelector<HTMLElement>('[slot="label"]')!;
    const feedback = field.querySelector<HTMLElement>('[slot="feedback"]')!;

    expect(feedback.textContent).toContain('Add at least one field.');
    expect(fieldLayout.readOnly).toBe(true);
    expect(fieldLayout.getAttribute('aria-labelledby')?.split(/\s+/)).toContain(
      label.id
    );
    expect(
      fieldLayout.getAttribute('aria-describedby')?.split(/\s+/)
    ).toContain(feedback.id);
  });

  it('wires keyed table labels and errors through the mounted field', async () => {
    const container = mount(
      {settings: {siteSettings: {english: {uriFormat: ''}}}},
      false,
      {'settings.siteSettings': ['Enter a URI format.']},
      accessibleKeyedTableDefinition
    );
    const field =
      container.querySelector<HTMLElementTagNameMap['craft-field']>(
        'craft-field'
      )!;
    const table =
      container.querySelector<HTMLElementTagNameMap['craft-keyed-table']>(
        'craft-keyed-table'
      )!;

    await nextTick();
    await field.updateComplete;
    await field.updateComplete;

    const label = field.querySelector<HTMLElement>('[slot="label"]')!;
    const feedback = field.querySelector<HTMLElement>('[slot="feedback"]')!;

    expect(feedback.textContent).toContain('Enter a URI format.');
    expect(table.getAttribute('aria-labelledby')?.split(/\s+/)).toContain(
      label.id
    );
    expect(table.getAttribute('aria-describedby')?.split(/\s+/)).toContain(
      feedback.id
    );
  });

  it('wires object selection errors, read-only state, and accessibility through the mounted field', async () => {
    const container = mount(
      {settings: {entryTypes: [{uid: 'article', name: 'Article'}]}},
      true,
      {'settings.entryTypes': ['Choose another entry type.']},
      accessibleObjectSelectDefinition
    );
    const field =
      container.querySelector<HTMLElementTagNameMap['craft-field']>(
        'craft-field'
      )!;
    const objectSelect = container.querySelector<
      HTMLElementTagNameMap['craft-object-select']
    >('craft-object-select')!;

    await nextTick();
    await field.updateComplete;
    await field.updateComplete;
    await objectSelect.updateComplete;

    const label = field.querySelector<HTMLElement>('[slot="label"]')!;
    const feedback = field.querySelector<HTMLElement>('[slot="feedback"]')!;

    expect(feedback.textContent).toContain('Choose another entry type.');
    expect(objectSelect.readOnly).toBe(true);
    expect(
      objectSelect.getAttribute('aria-labelledby')?.split(/\s+/)
    ).toContain(label.id);
    expect(
      objectSelect.getAttribute('aria-describedby')?.split(/\s+/)
    ).toContain(feedback.id);
  });
});

function mount(
  values: Record<string, unknown>,
  readOnly = false,
  errors: Record<string, string | string[]> = {},
  formDefinition: CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData = definition
): HTMLElement {
  const registry = createCpComponentRegistry();
  const container = document.createElement('div');

  registry.register(
    'form-element:craft:checkbox-select-input',
    CheckboxSelectInputRenderer
  );
  registry.register(
    'form-element:craft:field-layout-input',
    CraftFieldLayout
  );
  registry.register(
    'form-element:craft:keyed-table-input',
    CraftKeyedTable
  );
  registry.register('form-element:craft:lightswitch-input', CraftSwitch);
  registry.register(
    'form-element:craft:object-select-input',
    CraftObjectSelect
  );
  registry.register('form-element:craft:select-input', SelectInputRenderer);
  (window as any).Cp = {$components: registry};
  document.body.appendChild(container);
  const app = createApp(FormDefinitionRenderer, {
    definition: formDefinition,
    bindingScope: 'settings',
    values,
    errors,
    readOnly,
  });

  mountedApps.push(app);
  app.mount(container);

  return container;
}

const definition = {
  elements: [
    field('craft:field-layout-input', 'fieldLayouts.layout-1', {
      availableElements: [
        {
          key: 'field:title',
          label: 'Title',
          value: {type: 'TitleField'},
          multiple: false,
        },
        {
          key: 'field:body',
          label: 'Body',
          value: {type: 'BodyField'},
          multiple: false,
        },
      ],
      withGeneratedFields: true,
    }),
    field('craft:object-select-input', 'entryTypes', {
      options: [
        {key: 'article', label: 'Article', value: {uid: 'article'}},
        {key: 'page', label: 'Page', value: {uid: 'page'}},
        {key: 'news', label: 'News', value: {uid: 'news'}},
      ],
      identityKey: 'uid',
    }),
    field('craft:keyed-table-input', 'siteSettings', {
      columns: [
        {key: 'uriFormat', label: 'Entry URI Format', code: true},
        {key: 'template', label: 'Template', code: true},
      ],
      rows: [{key: 'english', label: 'English'}],
    }),
    field('craft:select-input', 'viewMode', {
      options: [
        {label: 'Cards', value: 'cards'},
        {label: 'Index', value: 'index'},
      ],
    }),
    field('craft:lightswitch-input', 'includeTableView', undefined, {
      name: 'viewMode',
      operator: 'equals',
      value: 'index',
    }),
    field(
      'craft:checkbox-select-input',
      'defaultTableColumns',
      {
        options: [
          {label: 'Title', value: 'title'},
          {label: 'Status', value: 'status'},
        ],
        sortable: true,
      },
      {
        all: [
          {name: 'viewMode', operator: 'equals', value: 'index'},
          {name: 'includeTableView', operator: 'equals', value: true},
        ],
      }
    ),
  ],
} satisfies CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData;

const accessibleKeyedTableDefinition = {
  elements: [
    {
      type: 'craft:field',
      props: {
        label: 'Site settings',
        instructions: 'Configure the site routes.',
      },
      children: [
        {
          type: 'craft:keyed-table-input',
          name: 'siteSettings',
          props: {
            columns: [{key: 'uriFormat', label: 'URI format'}],
            rows: [{key: 'english', label: 'English'}],
          },
        },
      ],
    },
  ],
} satisfies CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData;

const accessibleFieldLayoutDefinition = {
  elements: [
    {
      type: 'craft:field',
      props: {
        label: 'Field layout',
        instructions: 'Arrange the content fields.',
      },
      children: [
        {
          type: 'craft:field-layout-input',
          name: 'fieldLayouts.layout-1',
          props: {
            availableElements: [],
            withGeneratedFields: false,
          },
        },
      ],
    },
  ],
} satisfies CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData;

const accessibleObjectSelectDefinition = {
  elements: [
    {
      type: 'craft:field',
      props: {
        label: 'Entry types',
        instructions: 'Choose the available entry types.',
      },
      children: [
        {
          type: 'craft:object-select-input',
          name: 'entryTypes',
          props: {
            options: [
              {
                key: 'article',
                label: 'Article',
                value: {uid: 'article'},
              },
            ],
            identityKey: 'uid',
          },
        },
      ],
    },
  ],
} satisfies CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData;

function field(
  type: string,
  name: string,
  props?: Record<string, CraftCms.Cms.Cp.FormDefinitions.Data.JsonValue>,
  visibleWhen?: CraftCms.Cms.Cp.FormDefinitions.Data.VisibilityConditionData
) {
  return {type: 'craft:field', children: [{type, name, props}], visibleWhen};
}
