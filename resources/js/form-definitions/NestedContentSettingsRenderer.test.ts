import {createApp, nextTick, reactive, toRaw} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormDefinitionRenderer from './FormDefinitionRenderer.vue';
import CheckboxSelectInputRenderer from './renderers/CheckboxSelectInputRenderer.vue';
import FieldLayoutInputRenderer from './renderers/FieldLayoutInputRenderer.vue';
import KeyedTableInputRenderer from './renderers/KeyedTableInputRenderer.vue';
import LightswitchInputRenderer from './renderers/LightswitchInputRenderer.vue';
import ObjectSelectInputRenderer from './renderers/ObjectSelectInputRenderer.vue';
import SelectInputRenderer from './renderers/SelectInputRenderer.vue';

const mountedApps: Array<ReturnType<typeof createApp>> = [];

afterEach(() => {
  mountedApps.splice(0).forEach((app) => app.unmount());
  document.body.innerHTML = '';
});

describe('nested content settings renderers', () => {
  it('reorders object values without restricting renderer-specific host state', async () => {
    const entryTypeMarker = new Map([['source', 'host']]);
    const article = {uid: 'article', name: 'Article', marker: entryTypeMarker};
    const page = {uid: 'page', name: 'Page'};
    const values = reactive({
      settings: {
        entryTypes: [article, page],
      },
    });
    const container = mount(values);
    const entryTypeRows = container.querySelectorAll<HTMLElement>(
      '[data-object-select-row]'
    );

    entryTypeRows[0]!.querySelector('craft-reorder-button')!.dispatchEvent(
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
    const layoutRows = container.querySelectorAll<HTMLElement>(
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
    const generatedFields = container.querySelectorAll<HTMLElement>(
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
    const template = container.querySelector<HTMLTextAreaElement>(
      'textarea[name="settings[fieldLayouts][layout-1][generatedFields][0][template]"]'
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
    const uriInput = container.querySelector<
      HTMLElementTagNameMap['craft-input']
    >('[data-keyed-table-cell="english:uriFormat"]')!;

    uriInput.value = 'stories/{slug}';
    uriInput.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

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

  it('cannot weaken host read-only state for complex controls', () => {
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

    expect(
      Array.from(
        container.querySelectorAll<HTMLElement & {disabled: boolean}>(
          'craft-reorder-button, craft-button, craft-input'
        )
      ).every(({disabled}) => disabled)
    ).toBe(true);
  });
});

function mount(values: Record<string, unknown>, readOnly = false): HTMLElement {
  const registry = createCpComponentRegistry();
  const container = document.createElement('div');

  registry.register(
    'form-element:craft:checkbox-select-input',
    CheckboxSelectInputRenderer
  );
  registry.register(
    'form-element:craft:field-layout-input',
    FieldLayoutInputRenderer
  );
  registry.register(
    'form-element:craft:keyed-table-input',
    KeyedTableInputRenderer
  );
  registry.register(
    'form-element:craft:lightswitch-input',
    LightswitchInputRenderer
  );
  registry.register(
    'form-element:craft:object-select-input',
    ObjectSelectInputRenderer
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

function field(
  type: string,
  name: string,
  props?: Record<string, CraftCms.Cms.Cp.FormDefinitions.Data.JsonValue>,
  visibleWhen?: CraftCms.Cms.Cp.FormDefinitions.Data.VisibilityConditionData
) {
  return {type: 'craft:field', children: [{type, name, props}], visibleWhen};
}
