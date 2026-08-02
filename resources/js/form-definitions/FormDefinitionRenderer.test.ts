import {createApp, defineComponent, h, nextTick, reactive, ref} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import CraftColorPalette from '@craftcms/ui/vue/CraftColorPalette.vue';
import CraftEditableTable from '@craftcms/ui/vue/CraftEditableTable.vue';
import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
import CraftInputMoney from '@craftcms/ui/vue/CraftInputMoney.vue';
import CraftSelect from '@craftcms/ui/vue/CraftSelect.vue';
import CraftSwitch from '@craftcms/ui/vue/CraftSwitch.vue';
import '@craftcms/ui/components/color-palette/color-palette';
import '@craftcms/ui/components/editable-table/editable-table';
import '@craftcms/ui/components/input/input';
import '@craftcms/ui/components/select/select';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormDefinitionRenderer from './FormDefinitionRenderer.vue';

const definition = {
  elements: [
    {
      type: 'craft:field',
      width: 50,
      props: {
        label: 'Handle',
        instructions: 'How templates refer to this component.',
      },
      children: [
        {
          type: 'craft:text-input',
          name: 'handle',
          props: {placeholder: 'myComponent'},
          attributes: {
            autocomplete: 'off',
            'data-setting': 'handle',
          },
        },
      ],
    },
  ],
} satisfies CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData;

const mountedApps: Array<ReturnType<typeof createApp>> = [];

afterEach(() => {
  mountedApps.splice(0).forEach((app) => app.unmount());
  document.body.innerHTML = '';
});

describe('Form Definition renderer', () => {
  it('renders scalar controls with browser-native form values', async () => {
    const registry = createCpComponentRegistry();
    const values = reactive({
      settings: {
        uiMode: 'enlarged',
        minuteIncrement: 15,
        title: 'Craft',
        charLimit: null as number | null,
        code: true,
        minDate: '2026-01-02T00:00:00+00:00',
        minTime: '08:30',
        defaultValue: 1234,
      },
    });
    const container = document.createElement('div');

    registry.register('form-element:craft:select-input', CraftSelect);
    registry.register('form-element:craft:text-input', CraftInput);
    registry.register('form-element:craft:number-input', CraftInput);
    registry.register('form-element:craft:lightswitch-input', CraftSwitch);
    registry.register('form-element:craft:date-input', CraftInput);
    registry.register('form-element:craft:time-input', CraftInput);
    registry.register('form-element:craft:money-input', CraftInputMoney);
    (window as any).Cp = {$components: registry};
    document.body.appendChild(container);
    const app = createApp(FormDefinitionRenderer, {
      definition: {
        elements: [
          {
            type: 'craft:field',
            props: {label: 'UI Mode', required: true},
            children: [
              {
                type: 'craft:select-input',
                name: 'uiMode',
                props: {
                  options: [
                    {label: 'Normal', value: 'normal'},
                    {label: 'Enlarged', value: 'enlarged'},
                  ],
                },
              },
            ],
          },
          scalarField('craft:select-input', 'minuteIncrement', {
            options: [
              {label: '15', value: 15},
              {label: '30', value: 30},
            ],
          }),
          scalarField('craft:text-input', 'title', {
            placeholder: 'Article title',
          }),
          scalarField('craft:number-input', 'charLimit', {
            type: 'number',
            min: 1,
          }),
          scalarField('craft:lightswitch-input', 'code'),
          {
            type: 'craft:field',
            props: {tip: 'Dates use the project time zone.'},
            children: [
              {
                type: 'craft:date-input',
                name: 'minDate',
                props: {type: 'date'},
              },
            ],
          },
          scalarField('craft:time-input', 'minTime', {type: 'time'}),
          scalarField('craft:money-input', 'defaultValue', {
            currency: 'USD',
            fractionDigits: 2,
            minorUnits: true,
            placeholder: '0.00',
          }),
        ],
      },
      bindingScope: 'settings',
      values,
      errors: {},
    });

    mountedApps.push(app);
    app.mount(container);

    const select =
      container.querySelector<HTMLElementTagNameMap['craft-select']>(
        'craft-select'
      )!;
    const numericSelect = Array.from(
      container.querySelectorAll('craft-select')
    ).find((select) => select.name === 'settings[minuteIncrement]')!;
    const lightswitch =
      container.querySelector<HTMLElementTagNameMap['craft-switch']>(
        'craft-switch'
      )!;
    const inputs = Array.from(container.querySelectorAll('craft-input'));
    const text = inputs.find((input) => input.name === 'settings[title]')!;
    const number = inputs.find(
      (input) => input.name === 'settings[charLimit]'
    )!;
    const date = inputs.find((input) => input.type === 'date')!;
    const time = inputs.find((input) => input.type === 'time')!;
    const money =
      container.querySelector<HTMLElementTagNameMap['craft-input-money']>(
        'craft-input-money'
      )!;
    const uiModeField =
      select.closest<HTMLElementTagNameMap['craft-field']>('craft-field')!;
    await uiModeField.updateComplete;

    expect(select.modelValue).toBe('enlarged');
    expect(select.getAttribute('aria-required')).toBe('true');
    expect(
      container
        .querySelector('craft-field > label[slot="label"]')
        ?.querySelector('.required')
    ).not.toBeNull();
    expect(number.value).toBe('');
    expect(number.type).toBe('number');
    expect(number.getAttribute('min')).toBe('1');
    expect(text.value).toBe('Craft');
    expect(text.placeholder).toBe('Article title');
    expect(lightswitch.checked).toBe(true);
    expect(date.formatter(date.modelValue)).toBe('2026-01-02');
    expect(time.formatter(time.modelValue)).toBe('08:30');
    expect(money.formatter(money.modelValue)).toBe('12.34');
    expect(money.currency).toBe('USD');
    expect(money.fractionDigits).toBe(2);
    expect(money.placeholder).toBe('0.00');
    expect(
      container.querySelector('[data-form-element-tip]')?.textContent
    ).toBe('Dates use the project time zone.');

    text.modelValue = 'Craft CMS';
    text.dispatchEvent(new Event('model-value-changed', {bubbles: true}));
    number.modelValue = '120';
    number.dispatchEvent(new Event('model-value-changed', {bubbles: true}));
    lightswitch.checked = false;
    lightswitch.dispatchEvent(
      new Event('model-value-changed', {bubbles: true})
    );
    money.modelValue = 5678;
    money.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    date.modelValue = '2027-03-04';
    date.dispatchEvent(new Event('model-value-changed', {bubbles: true}));
    time.modelValue = '09:45';
    time.dispatchEvent(new Event('model-value-changed', {bubbles: true}));
    const nativeNumericSelect = numericSelect.querySelector('select')!;
    nativeNumericSelect.value = '30';
    nativeNumericSelect.dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();

    expect(values.settings.title).toBe('Craft CMS');
    expect(values.settings.charLimit).toBe('120');
    expect(values.settings.code).toBe(false);
    expect(values.settings.defaultValue).toBe(5678);
    expect(values.settings.minuteIncrement).toBe('30');
    expect(values.settings.minDate).toBe('2027-03-04');
    expect(values.settings.minTime).toBe('09:45');

    number.modelValue = '';
    number.dispatchEvent(new Event('model-value-changed', {bubbles: true}));
    money.modelValue = '';
    money.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    date.modelValue = '';
    date.dispatchEvent(new Event('model-value-changed', {bubbles: true}));
    time.modelValue = '';
    time.dispatchEvent(new Event('model-value-changed', {bubbles: true}));
    await nextTick();

    expect(values.settings.charLimit).toBe('');
    expect(values.settings.defaultValue).toBe('');
    expect(values.settings.minDate).toBe('');
    expect(values.settings.minTime).toBe('');
  });

  it('applies host errors, read-only state, and accessibility to money inputs', async () => {
    const registry = createCpComponentRegistry();
    const container = document.createElement('div');

    registry.register('form-element:craft:money-input', CraftInputMoney);
    (window as any).Cp = {$components: registry};
    document.body.appendChild(container);
    const app = createApp(FormDefinitionRenderer, {
      definition: {
        elements: [
          {
            type: 'craft:field',
            props: {label: 'Minimum amount', required: true},
            children: [
              {
                type: 'craft:money-input',
                name: 'minimum',
                props: {
                  currency: 'EUR',
                  fractionDigits: 2,
                  minorUnits: true,
                },
              },
            ],
          },
        ],
      },
      bindingScope: 'settings',
      values: {settings: {minimum: 250}},
      errors: {'settings.minimum': ['Enter a valid amount.']},
      readOnly: true,
    });

    mountedApps.push(app);
    app.mount(container);

    const money =
      container.querySelector<HTMLElementTagNameMap['craft-input-money']>(
        'craft-input-money'
      )!;

    await money.updateComplete;

    expect(money.formatter(money.modelValue)).toBe('2.50');
    expect(money.name).toBe('settings[minimum]');
    expect(money.readOnly).toBe(true);
    expect(money.getAttribute('aria-required')).toBe('true');
    expect(
      money.shadowRoot
        ?.querySelector('[data-money-currency]')
        ?.hasAttribute('aria-hidden')
    ).toBe(false);
    expect(
      container.querySelector('[data-form-element-errors]')?.textContent
    ).toContain('Enter a valid amount.');
  });

  it('renders eager and lazy plugin renderers through the public renderer contract', async () => {
    for (const lazy of [false, true]) {
      const registry = createCpComponentRegistry();
      const values = reactive({settings: {palette: 'sunset'}});
      const container = document.createElement('div');
      const pluginRenderer = defineComponent({
        inheritAttrs: false,
        props: {
          colors: {type: Array, required: true},
          modelValue: {type: String, required: true},
          readonly: Boolean,
        },
        emits: ['update:modelValue'],
        setup(props, {attrs, emit}) {
          return () =>
            h(
              'button',
              {
                ...attrs,
                'data-plugin-renderer': '',
                'data-readonly': String(props.readonly),
                onClick: () => emit('update:modelValue', 'ocean'),
              },
              `${props.colors.join(',')}:${props.modelValue}`
            );
        },
      });
      const pluginDefinition = {
        elements: [
          {
            type: 'craft:field',
            props: {required: true},
            children: [
              {
                type: 'color-tools:color-map',
                name: 'palette',
                props: {colors: ['red', 'blue']},
                attributes: {'data-setting': 'palette'},
                plugin: {
                  handle: 'color-tools',
                  name: 'Color Tools',
                  packageName: 'vendor/color-tools',
                },
              },
            ],
          },
        ],
      } satisfies CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData;

      registry.register(
        'form-element:color-tools:color-map',
        lazy ? async () => ({default: pluginRenderer}) : pluginRenderer
      );
      (window as any).Cp = {$components: registry};
      document.body.appendChild(container);
      const app = createApp(FormDefinitionRenderer, {
        definition: pluginDefinition,
        bindingScope: 'settings',
        values,
        errors: {},
      });

      mountedApps.push(app);
      app.mount(container);

      await vi.waitFor(() => {
        expect(
          container.querySelector('[data-plugin-renderer]')
        ).not.toBeNull();
      });

      const renderer = container.querySelector<HTMLButtonElement>(
        '[data-plugin-renderer]'
      )!;
      expect(renderer.textContent).toBe('red,blue:sunset');
      expect(renderer.dataset.setting).toBe('palette');
      expect(renderer.dataset.readonly).toBe('false');
      expect(renderer.name).toBe('settings[palette]');
      expect(renderer.id).toBe('form-element-settings--palette');
      expect(renderer.getAttribute('aria-required')).toBe('true');
      expect(renderer.hasAttribute('required')).toBe(true);
      expect(renderer.hasAttribute('config')).toBe(false);
      expect(renderer.hasAttribute('binding')).toBe(false);

      renderer.click();
      await nextTick();

      expect(values.settings.palette).toBe('ocean');
      app.unmount();
      mountedApps.pop();
      container.remove();
    }
  });

  it('runs a generated wrapper through the registry without setup events changing host state', async () => {
    const registry = createCpComponentRegistry();
    const initial = [{color: '#ff0000', label: 'Red', default: true}];
    const values = reactive({settings: {palette: initial}});
    const container = document.createElement('div');

    registry.register('form-element:color-tools:color-map', CraftColorPalette);
    (window as any).Cp = {$components: registry};
    document.body.appendChild(container);
    const app = createApp(FormDefinitionRenderer, {
      definition: {
        elements: [
          {
            type: 'craft:field',
            children: [
              {
                type: 'color-tools:color-map',
                name: 'palette',
                plugin: {
                  handle: 'color-tools',
                  name: 'Color Tools',
                  packageName: 'vendor/color-tools',
                },
              },
            ],
          },
        ],
      },
      bindingScope: 'settings',
      values,
      errors: {},
    });

    mountedApps.push(app);
    app.mount(container);

    const palette = container.querySelector<
      HTMLElementTagNameMap['craft-color-palette']
    >('craft-color-palette')!;

    expect(palette.value).toEqual(initial);

    palette.value = [];
    palette.dispatchEvent(
      new CustomEvent('input', {
        bubbles: true,
        detail: {initialize: true},
      })
    );
    await nextTick();

    expect(values.settings.palette).toEqual(initial);

    const edited = [{color: '#00ff00', label: 'Green', default: false}];
    palette.value = edited;
    palette.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    expect(values.settings.palette).toEqual(edited);
  });

  it('updates final attributes on a preserved adapter during reconciliation', async () => {
    const registry = createCpComponentRegistry();
    const inputName = ref('primaryRows');
    const values = reactive({
      settings: {
        primaryRows: [{label: 'Primary'}],
        secondaryRows: [{label: 'Secondary'}],
      },
    });
    const container = document.createElement('div');
    const host = defineComponent({
      setup() {
        return () =>
          h(FormDefinitionRenderer, {
            definition: {
              elements: [
                {
                  type: 'craft:field',
                  key: 'rows-field',
                  children: [
                    {
                      type: 'craft:editable-table-input',
                      key: 'rows-input',
                      name: inputName.value,
                      props: {sourceName: 'rows'},
                    },
                  ],
                },
              ],
            },
            bindingScope: 'settings',
            values,
            errors: {},
          });
      },
    });

    registry.register(
      'form-element:craft:editable-table-input',
      CraftEditableTable
    );
    (window as any).Cp = {$components: registry};
    document.body.appendChild(container);
    const app = createApp(host);

    mountedApps.push(app);
    app.mount(container);

    const table = container.querySelector<
      HTMLElementTagNameMap['craft-editable-table']
    >('craft-editable-table')!;

    expect(table.name).toBe('settings[primaryRows]');
    expect(table.value).toEqual([{label: 'Primary'}]);

    inputName.value = 'secondaryRows';
    await nextTick();

    expect(container.querySelector('craft-editable-table')).toBe(table);
    expect(table.name).toBe('settings[secondaryRows]');
    expect(table.id).toBe('form-element-settings--secondaryRows');
    expect(table.value).toEqual([{label: 'Secondary'}]);
  });

  it('shows plugin ownership when its renderer is unavailable', () => {
    const registry = createCpComponentRegistry();
    const container = document.createElement('div');

    (window as any).Cp = {$components: registry};
    document.body.appendChild(container);
    const app = createApp(FormDefinitionRenderer, {
      definition: {
        elements: [
          {
            type: 'craft:field',
            children: [
              {
                type: 'color-tools:color-map',
                name: 'palette',
                plugin: {
                  handle: 'color-tools',
                  name: 'Color Tools',
                  packageName: 'vendor/color-tools',
                },
              },
            ],
          },
        ],
      },
      bindingScope: 'settings',
      values: {settings: {palette: 'sunset'}},
      errors: {},
    });

    mountedApps.push(app);
    app.mount(container);

    const diagnostic = container.querySelector(
      '[data-form-element-missing-renderer]'
    )!;
    expect(diagnostic.textContent).toContain('color-tools:color-map');
    expect(diagnostic.textContent).toContain('Color Tools');
    expect(diagnostic.textContent).toContain('color-tools');
    expect(diagnostic.textContent).toContain('vendor/color-tools');
  });

  it('gives a plugin container its rendered children while retaining generic presentation state', async () => {
    const registry = createCpComponentRegistry();
    const container = document.createElement('div');
    const values = reactive({settings: {enabled: true, handle: 'news'}});
    const pluginContainer = defineComponent({
      inheritAttrs: false,
      setup(_, {slots}) {
        return () =>
          h('section', {'data-plugin-container': ''}, slots.default?.());
      },
    });

    registry.register(
      'form-element:color-tools:palette-group',
      pluginContainer
    );
    registry.register('form-element:craft:text-input', CraftInput);
    (window as any).Cp = {$components: registry};
    document.body.appendChild(container);
    const app = createApp(FormDefinitionRenderer, {
      definition: {
        elements: [
          {
            type: 'color-tools:palette-group',
            width: 50,
            visibleWhen: {name: 'enabled', operator: 'equals', value: true},
            plugin: {
              handle: 'color-tools',
              name: 'Color Tools',
              packageName: 'vendor/color-tools',
            },
            children: definition.elements,
          },
        ],
      },
      bindingScope: 'settings',
      values,
      errors: {},
    });

    mountedApps.push(app);
    app.mount(container);

    expect(
      container.querySelector('[data-plugin-container] craft-input')
    ).not.toBeNull();
    expect(container.querySelector('craft-input')!.name).toBe(
      'settings[handle]'
    );
    const plugin = container.querySelector<HTMLElement>(
      '[data-plugin-container]'
    )!;
    const wrapper = plugin.parentElement!;
    expect(wrapper.style.width).toBe('50%');

    values.settings.enabled = false;
    await nextTick();

    expect(wrapper.style.display).toBe('none');
    expect(container.querySelector('[data-plugin-container]')).toBe(plugin);
  });

  it('preserves keyed tabs, focus, cursor, and unchanged DOM across a complete definition refresh', async () => {
    const registry = createCpComponentRegistry();
    const container = document.createElement('div');
    const values = reactive({settings: {slug: 'article', subtitle: ''}});
    const currentDefinition = ref(tabbedDefinition(false));
    const nativeInputRenderer = defineComponent({
      inheritAttrs: false,
      props: ['modelValue'],
      emits: ['update:modelValue'],
      setup(props, {attrs, emit}) {
        return () =>
          h('input', {
            ...attrs,
            value: props.modelValue,
            'data-native-input': attrs.name,
            onInput: (event) =>
              emit(
                'update:modelValue',
                (event.target as HTMLInputElement).value
              ),
          });
      },
    });
    const host = defineComponent({
      setup() {
        return () =>
          h(FormDefinitionRenderer, {
            definition: currentDefinition.value,
            bindingScope: 'settings',
            values,
            errors: {},
          });
      },
    });

    registry.register(
      'form-element:application:native-input',
      nativeInputRenderer
    );
    (window as any).Cp = {$components: registry};
    document.body.appendChild(container);
    const app = createApp(host);

    mountedApps.push(app);
    app.mount(container);

    expect(container.querySelector('[role="tablist"]')).toBeNull();

    const tabsElement = container.querySelector(
      '[data-form-element="craft:tabs"]'
    )!;
    expect(tabsElement.tagName).toBe('CRAFT-TABS');
    expect(
      container.querySelector('[data-form-element="craft:group"]')?.tagName
    ).toBe('CRAFT-FIELD-GROUP');
    expect(
      container.querySelector<HTMLElement>('[data-form-element="craft:group"]')
        ?.dataset.container
    ).toBe('group');
    expect((tabsElement as HTMLElement).dataset.container).toBe('tabs');
    const metadataPanel = container.querySelector(
      '[data-form-tab-panel="metadata"]'
    )!;
    expect(metadataPanel.tagName).toBe('CRAFT-FIELD-GROUP');
    const slugInput = container.querySelector<HTMLInputElement>(
      '[data-native-input="settings[slug]"]'
    )!;
    slugInput.focus();
    slugInput.setSelectionRange(2, 5);

    currentDefinition.value = tabbedDefinition(true);
    await nextTick();
    await vi.waitFor(() => {
      expect(container.querySelectorAll('[role="tab"]')).toHaveLength(3);
    });

    const tabs = container.querySelectorAll<HTMLElement>('[role="tab"]');
    expect(Array.from(tabs, (tab) => tab.textContent?.trim())).toEqual([
      'New',
      'Content',
      'Metadata',
    ]);
    expect(tabs[2]!.dataset.container).toBe('tab');
    expect(
      tabs[2]!.querySelector<HTMLElementTagNameMap['craft-indicator']>(
        'craft-indicator'
      )!.label
    ).toBe('Contains errors');
    const selectedTab = container.querySelector(
      '[role="tab"][aria-selected="true"]'
    )!;
    expect(selectedTab.textContent).toContain('Metadata');
    expect(container.querySelector('[data-form-element="craft:tabs"]')).toBe(
      tabsElement
    );
    expect(container.querySelector('[data-form-tab-panel="metadata"]')).toBe(
      metadataPanel
    );
    expect(
      container.querySelector('[data-native-input="settings[slug]"]')
    ).toBe(slugInput);
    expect(document.activeElement).toBe(slugInput);
    expect(slugInput.selectionStart).toBe(2);
    expect(slugInput.selectionEnd).toBe(5);
    expect(
      container.querySelector('[data-native-input="settings[subtitle]"]')
    ).not.toBeNull();
    expect(slugInput.name).toBe('settings[slug]');

    selectedTab.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'Home', bubbles: true})
    );
    selectedTab.dispatchEvent(
      new KeyboardEvent('keyup', {key: 'Home', bubbles: true})
    );
    await nextTick();
    expect(
      container.querySelector('[role="tab"][aria-selected="true"]')!.textContent
    ).toContain('New');
  });

  it('suppresses tab navigation when only one tab is present', () => {
    const registry = createCpComponentRegistry();
    const container = document.createElement('div');

    registry.register('form-element:craft:text-input', CraftInput);
    (window as any).Cp = {$components: registry};
    document.body.appendChild(container);
    const app = createApp(FormDefinitionRenderer, {
      definition: {
        elements: [
          {
            type: 'craft:tabs',
            key: 'settings-tabs',
            children: [
              {
                type: 'craft:tab',
                key: 'content',
                props: {label: 'Content'},
                children: definition.elements,
              },
            ],
          },
        ],
      },
      bindingScope: 'settings',
      values: {settings: {handle: 'news'}},
      errors: {},
    });

    mountedApps.push(app);
    app.mount(container);

    expect(container.querySelector('[role="tablist"]')).toBeNull();
    expect(container.querySelector('craft-input')).not.toBeNull();
  });

  it('preserves projected tab attributes, visibility, and width', async () => {
    const registry = createCpComponentRegistry();
    const container = document.createElement('div');
    const values = reactive({settings: {enabled: true}});

    (window as any).Cp = {$components: registry};
    document.body.appendChild(container);
    const app = createApp(FormDefinitionRenderer, {
      definition: {
        elements: [
          {
            type: 'craft:tabs',
            children: [
              {
                type: 'craft:tab',
                key: 'content',
                width: 60,
                attributes: {data: {container: 'content'}},
                props: {label: 'Content'},
                visibleWhen: {
                  name: 'enabled',
                  operator: 'equals',
                  value: true,
                },
                children: [],
              },
              {
                type: 'craft:tab',
                key: 'metadata',
                props: {label: 'Metadata'},
                visibleWhen: {
                  name: 'enabled',
                  operator: 'equals',
                  value: false,
                },
                children: [],
              },
              {
                type: 'craft:tab',
                key: 'general',
                props: {label: 'General'},
                children: [],
              },
            ],
          },
        ],
      },
      bindingScope: 'settings',
      values,
      errors: {},
    });

    mountedApps.push(app);
    app.mount(container);

    await vi.waitFor(() => {
      expect(container.querySelectorAll('craft-tab')).toHaveLength(3);
    });

    const contentTab = Array.from(
      container.querySelectorAll<HTMLElement>('craft-tab')
    ).find((tab) => tab.textContent?.includes('Content'))!;
    const metadataTab = Array.from(
      container.querySelectorAll<HTMLElement>('craft-tab')
    ).find((tab) => tab.textContent?.includes('Metadata'))!;
    const contentPanel = container.querySelector<HTMLElement>(
      '[data-form-tab-panel="content"]'
    )!;
    const metadataPanel = container.querySelector<HTMLElement>(
      '[data-form-tab-panel="metadata"]'
    )!;

    expect(contentTab.dataset.container).toBe('content');
    expect(contentTab.style.display).not.toBe('none');
    expect(metadataTab.style.display).toBe('none');
    expect(contentPanel.style.width).toBe('60%');
    expect(metadataPanel.style.display).toBe('none');

    values.settings.enabled = false;
    await nextTick();

    expect(contentTab.style.display).toBe('none');
    expect(contentPanel.style.display).toBe('none');
    expect(metadataTab.style.display).not.toBe('none');
    expect(metadataPanel.style.display).not.toBe('none');
    expect(
      container.querySelector<HTMLElementTagNameMap['craft-tabs']>(
        'craft-tabs'
      )!.selectedIndex
    ).toBe(1);
  });

  it('throws for an unavailable application renderer inside a plugin container', () => {
    const registry = createCpComponentRegistry();
    const container = document.createElement('div');
    const pluginContainer = defineComponent({
      inheritAttrs: false,
      setup(_, {slots}) {
        return () => h('section', slots.default?.());
      },
    });

    registry.register(
      'form-element:color-tools:palette-group',
      pluginContainer
    );
    (window as any).Cp = {$components: registry};

    expect(() =>
      createApp(FormDefinitionRenderer, {
        definition: {
          elements: [
            {
              type: 'color-tools:palette-group',
              plugin: {
                handle: 'color-tools',
                name: 'Color Tools',
                packageName: 'vendor/color-tools',
              },
              children: [{type: 'application:control', name: 'setting'}],
            },
          ],
        },
        bindingScope: 'settings',
        values: {settings: {setting: null}},
        errors: {},
      }).mount(container)
    ).toThrow('Missing Form Element Renderer for application:control.');

    expect(
      container.querySelector('[data-form-element-failed-renderer]')
    ).toBeNull();
  });

  it('throws when a core or application renderer is unavailable', () => {
    const registry = createCpComponentRegistry();
    const container = document.createElement('div');

    (window as any).Cp = {$components: registry};

    expect(() =>
      createApp(FormDefinitionRenderer, {
        definition: {
          elements: [
            {
              type: 'craft:field',
              children: [{type: 'application:control', name: 'setting'}],
            },
          ],
        },
        bindingScope: 'settings',
        values: {settings: {setting: null}},
        errors: {},
      }).mount(container)
    ).toThrow('Missing Form Element Renderer for application:control.');
  });

  it('distinguishes a failed registered renderer from a missing plugin', async () => {
    const registry = createCpComponentRegistry();
    const container = document.createElement('div');
    const failedRenderer = defineComponent({
      setup() {
        throw new Error('Renderer exploded.');
      },
    });

    registry.register('form-element:color-tools:color-map', failedRenderer);
    (window as any).Cp = {$components: registry};
    document.body.appendChild(container);
    const app = createApp(FormDefinitionRenderer, {
      definition: {
        elements: [
          {
            type: 'craft:field',
            children: [
              {
                type: 'color-tools:color-map',
                name: 'palette',
                plugin: {
                  handle: 'color-tools',
                  name: 'Color Tools',
                  packageName: 'vendor/color-tools',
                },
              },
            ],
          },
        ],
      },
      bindingScope: 'settings',
      values: {settings: {palette: 'sunset'}},
      errors: {},
    });

    mountedApps.push(app);
    app.mount(container);
    await nextTick();

    expect(
      container.querySelector('[data-form-element-missing-renderer]')
    ).toBeNull();
    const diagnostic = container.querySelector(
      '[data-form-element-failed-renderer]'
    )!;
    expect(diagnostic.textContent).toContain('color-tools:color-map');
    expect(diagnostic.textContent).toContain('Color Tools');
    expect(diagnostic.textContent).toContain('Renderer exploded.');
  });

  it('reactively hides a complete field and restores its unchanged control state', async () => {
    const registry = createCpComponentRegistry();
    const values = reactive({settings: {enabled: true, handle: 'news'}});
    const container = document.createElement('div');
    const conditionalDefinition: CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData =
      {
        elements: [
          {
            ...definition.elements[0]!,
            visibleWhen: {name: 'enabled', operator: 'equals', value: true},
          },
        ],
      };

    registry.register('form-element:craft:text-input', CraftInput);
    (window as any).Cp = {$components: registry};
    document.body.appendChild(container);
    const app = createApp(FormDefinitionRenderer, {
      definition: conditionalDefinition,
      bindingScope: 'settings',
      values,
      errors: {'settings.handle': ['Keep this field complete.']},
    });

    mountedApps.push(app);
    app.mount(container);

    const input =
      container.querySelector<HTMLElementTagNameMap['craft-input']>(
        'craft-input'
      )!;
    const field = container.querySelector<HTMLElement>(
      '[data-form-element="craft:field"]'
    )!;
    input.dataset.transientState = 'preserved';
    input.modelValue = 'articles';
    input.dispatchEvent(new Event('model-value-changed', {bubbles: true}));
    await nextTick();

    values.settings.enabled = false;
    await nextTick();

    expect(field.style.display).toBe('none');
    expect(field.querySelector('label')).not.toBeNull();
    expect(field.querySelector('[data-form-element-errors]')).not.toBeNull();
    expect(values.settings.handle).toBe('articles');

    values.settings.enabled = true;
    await nextTick();

    expect(field.style.display).toBe('');
    expect(container.querySelector('craft-input')).toBe(input);
    expect(input.value).toBe('articles');
    expect(input.dataset.transientState).toBe('preserved');
    expect(
      container.querySelector('[data-form-element-errors]')
    ).not.toBeNull();
  });

  it('renders and edits a scoped text setting with accessible field presentation', async () => {
    const registry = createCpComponentRegistry();
    const resolve = vi.spyOn(registry, 'resolve');
    const values = reactive({settings: {handle: 'news'}});
    const errors = reactive({
      'settings.handle': ['A handle is required.'],
    });
    const container = document.createElement('div');

    registry.register('form-element:craft:text-input', CraftInput);
    (window as any).Cp = {$components: registry};
    document.body.appendChild(container);
    const app = createApp(FormDefinitionRenderer, {
      definition,
      bindingScope: 'settings',
      values,
      errors,
    });

    mountedApps.push(app);
    app.mount(container);

    const field = container.querySelector<HTMLElementTagNameMap['craft-field']>(
      '[data-form-element="craft:field"]'
    )!;
    await field.updateComplete;
    await field.updateComplete;
    const label = field.querySelector<HTMLLabelElement>(
      ':scope > label[slot="label"]'
    )!;
    const instructions = field.querySelector<HTMLElement>(
      '[data-form-element-instructions]'
    )!;
    const feedback = field.querySelector<HTMLElement>(
      '[data-form-element-errors]'
    )!;
    const input =
      field.querySelector<HTMLElementTagNameMap['craft-input']>('craft-input')!;

    expect(resolve).toHaveBeenCalledWith('form-element:craft:text-input');
    expect(field.tagName).toBe('CRAFT-FIELD');
    expect(field.style.width).toBe('50%');
    expect(label.textContent).toBe('Handle');
    expect(label.htmlFor).toBe(input.id);
    expect(instructions.textContent).toBe(
      'How templates refer to this component.'
    );
    expect(feedback.textContent).toContain('A handle is required.');
    expect(input.getAttribute('aria-labelledby')).toBe(label.id);
    expect(input.getAttribute('aria-describedby')?.split(' ')).toEqual([
      instructions.id,
      feedback.id,
    ]);
    expect(input.id).toBe('form-element-settings--handle');
    expect(input.name).toBe('settings[handle]');
    expect(input.value).toBe('news');
    expect(input.placeholder).toBe('myComponent');
    expect(input.autocomplete).toBe('off');
    expect(input.dataset.setting).toBe('handle');
    expect(input.slot).toBe('input');
    expect(feedback.slot).toBe('feedback');

    input.modelValue = 'articles';
    input.dispatchEvent(new Event('model-value-changed', {bubbles: true}));
    await nextTick();

    expect(values.settings.handle).toBe('articles');

    errors['settings.handle'] = ['Handles must be unique.'];
    await nextTick();
    expect(feedback.textContent).toContain('Handles must be unique.');
    expect(input.getAttribute('aria-describedby')?.split(' ')).toContain(
      feedback.id
    );

    errors['settings.handle'] = [];
    await nextTick();
    await field.updateComplete;
    expect(field.querySelector('[data-form-element-errors]')).toBeNull();
    expect(input.getAttribute('aria-describedby')?.split(' ')).toEqual([
      instructions.id,
    ]);
  });

  it('renders a projected Field and Lightswitch with host-owned state and accessibility', async () => {
    const registry = createCpComponentRegistry();
    const values = reactive({settings: {enabled: true}});
    const container = document.createElement('div');

    registry.register('form-element:craft:lightswitch-input', CraftSwitch);
    (window as any).Cp = {$components: registry};
    document.body.appendChild(container);
    const vueApp = createApp(FormDefinitionRenderer, {
      definition: {
        elements: [
          {
            type: 'craft:field',
            props: {
              label: 'Feature',
              instructions: 'Controls the feature.',
              required: true,
              readOnly: true,
            },
            children: [
              {
                type: 'craft:lightswitch-input',
                name: 'enabled',
                props: {
                  label: 'Feature state',
                  onLabel: 'Enabled',
                  offLabel: 'Disabled',
                  size: 'small',
                },
              },
            ],
          },
        ],
      },
      bindingScope: 'settings',
      values,
      errors: {'settings.enabled': ['Choose a feature state.']},
    });

    mountedApps.push(vueApp);
    vueApp.mount(container);

    const field = container.querySelector<HTMLElementTagNameMap['craft-field']>(
      '[data-form-element="craft:field"]'
    )!;
    const lightswitch =
      field.querySelector<HTMLElementTagNameMap['craft-switch']>(
        'craft-switch'
      )!;
    await field.updateComplete;
    await field.updateComplete;
    await lightswitch.updateComplete;
    await lightswitch.updateComplete;
    const label = field.querySelector<HTMLLabelElement>(
      ':scope > label[slot="label"]'
    )!;
    const instructions = field.querySelector<HTMLElement>(
      '[data-form-element-instructions]'
    )!;
    const feedback = field.querySelector<HTMLElement>(
      '[data-form-element-errors]'
    )!;

    expect(label.textContent).toContain('Feature');
    expect(label.htmlFor).toBe(lightswitch.id);
    expect(instructions.textContent).toBe('Controls the feature.');
    expect(feedback.textContent).toContain('Choose a feature state.');
    expect(feedback.getAttribute('aria-label')).toBe('Validation errors');
    expect(field.required).toBe(true);
    expect(field.readOnly).toBe(true);
    expect(field.hasErrors).toBe(true);
    expect(lightswitch.checked).toBe(true);
    expect(lightswitch.disabled).toBe(true);
    expect(lightswitch.label).toBe('Feature state');
    expect(lightswitch.onLabel).toBe('Enabled');
    expect(lightswitch.offLabel).toBe('Disabled');
    expect(lightswitch.size).toBe('small');
    expect(lightswitch.name).toBe('settings[enabled]');
    expect(lightswitch.id).toBe('form-element-settings--enabled');
    expect(lightswitch.getAttribute('aria-required')).toBe('true');
    expect(lightswitch.getAttribute('aria-labelledby')).toBe(label.id);
    expect(lightswitch.getAttribute('aria-describedby')?.split(' ')).toEqual([
      instructions.id,
      feedback.id,
    ]);
  });

  it('combines host and field read-only state', async () => {
    for (const [hostReadOnly, fieldReadOnly] of [
      [true, false],
      [false, true],
    ] as const) {
      const registry = createCpComponentRegistry();
      const container = document.createElement('div');
      const renderedDefinition: CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData =
        {
          elements: definition.elements.map((element) => ({
            ...element,
            props: {...element.props, readOnly: fieldReadOnly},
          })),
        };

      registry.register('form-element:craft:text-input', CraftInput);
      (window as any).Cp = {$components: registry};
      document.body.appendChild(container);
      const app = createApp(FormDefinitionRenderer, {
        definition: renderedDefinition,
        bindingScope: 'settings',
        values: {settings: {handle: 'news'}},
        errors: {},
        readOnly: hostReadOnly,
      });

      mountedApps.push(app);
      app.mount(container);

      expect(
        container.querySelector('craft-input')!.hasAttribute('readonly')
      ).toBe(true);
      const field =
        container.querySelector<HTMLElementTagNameMap['craft-field']>(
          'craft-field'
        )!;
      await field.updateComplete;
      expect(
        field.shadowRoot?.querySelector('.read-only-badge')?.textContent
      ).toBe('Read Only');
    }
  });
});

function tabbedDefinition(insertSubtitle: boolean) {
  const metadataTab = {
    type: 'craft:tab',
    key: 'metadata',
    attributes: {data: {container: 'tab'}},
    props: {label: 'Metadata', hasErrors: true},
    children: [
      ...(insertSubtitle
        ? [nativeInputField('subtitle', 'subtitle-field')]
        : []),
      nativeInputField('slug'),
    ],
  } satisfies CraftCms.Cms.Cp.FormDefinitions.Data.FormElementData;

  return {
    elements: [
      {
        type: 'craft:group',
        key: 'settings',
        attributes: {data: {container: 'group'}},
        children: [
          {
            type: 'craft:tabs',
            key: 'settings-tabs',
            attributes: {data: {container: 'tabs'}},
            children: insertSubtitle
              ? [
                  {
                    type: 'craft:tab',
                    key: 'new-tab',
                    props: {label: 'New'},
                    children: [],
                  },
                  {
                    type: 'craft:tab',
                    key: 'content',
                    props: {label: 'Content'},
                    children: [],
                  },
                  metadataTab,
                ]
              : [metadataTab],
          },
        ],
      },
    ],
  } satisfies CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData;
}

function nativeInputField(name: string, key?: string) {
  return {
    type: 'craft:field',
    key,
    children: [{type: 'application:native-input', name}],
  } satisfies CraftCms.Cms.Cp.FormDefinitions.Data.FormElementData;
}

function scalarField(
  type: string,
  name: string,
  props?: Record<string, CraftCms.Cms.Cp.FormDefinitions.Data.JsonValue>
) {
  return {
    type: 'craft:field',
    children: [{type, name, props}],
  } satisfies CraftCms.Cms.Cp.FormDefinitions.Data.FormElementData;
}
