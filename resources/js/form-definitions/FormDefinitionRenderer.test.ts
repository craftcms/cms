import {createApp, defineComponent, h, nextTick, reactive, ref} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import '@craftcms/ui/components/input/input';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormDefinitionRenderer from './FormDefinitionRenderer.vue';
import TextInputRenderer from './renderers/TextInputRenderer.vue';

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
  it('renders eager and lazy plugin renderers through the public renderer contract', async () => {
    for (const lazy of [false, true]) {
      const registry = createCpComponentRegistry();
      const values = reactive({settings: {palette: 'sunset'}});
      const container = document.createElement('div');
      const pluginRenderer = defineComponent({
        props: ['config', 'attributes', 'binding'],
        emits: ['update:value'],
        setup(props, {emit}) {
          return () =>
            h(
              'button',
              {
                ...props.attributes,
                'data-plugin-renderer': '',
                onClick: () => emit('update:value', 'ocean'),
              },
              `${props.config.colors.join(',')}:${props.binding.value}`
            );
        },
      });
      const pluginDefinition = {
        elements: [
          {
            type: 'craft:field',
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

      renderer.click();
      await nextTick();

      expect(values.settings.palette).toBe('ocean');
      app.unmount();
      mountedApps.pop();
      container.remove();
    }
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

  it('gives a plugin container its rendered children through the default slot', () => {
    const registry = createCpComponentRegistry();
    const container = document.createElement('div');
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
    registry.register('form-element:craft:text-input', TextInputRenderer);
    (window as any).Cp = {$components: registry};
    document.body.appendChild(container);
    const app = createApp(FormDefinitionRenderer, {
      definition: {
        elements: [
          {
            type: 'color-tools:palette-group',
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
      values: {settings: {handle: 'news'}},
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
  });

  it('preserves keyed tabs, focus, cursor, and unchanged DOM across a complete definition refresh', async () => {
    const registry = createCpComponentRegistry();
    const container = document.createElement('div');
    const values = reactive({settings: {slug: 'article', subtitle: ''}});
    const currentDefinition = ref(tabbedDefinition(false));
    const nativeInputRenderer = defineComponent({
      props: ['attributes', 'binding'],
      setup(props) {
        return () =>
          h('input', {
            ...props.attributes,
            value: props.binding.value,
            'data-native-input': props.binding.name,
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
    const metadataPanel = container.querySelector(
      '[data-form-tab-panel="metadata"]'
    )!;
    const slugInput = container.querySelector<HTMLInputElement>(
      '[data-native-input="slug"]'
    )!;
    slugInput.focus();
    slugInput.setSelectionRange(2, 5);

    currentDefinition.value = tabbedDefinition(true);
    await nextTick();

    const tabs = container.querySelectorAll<HTMLElement>('[role="tab"]');
    expect(Array.from(tabs, (tab) => tab.textContent?.trim())).toEqual([
      'New',
      'Content',
      'Metadata',
    ]);
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
    expect(container.querySelector('[data-native-input="slug"]')).toBe(
      slugInput
    );
    expect(document.activeElement).toBe(slugInput);
    expect(slugInput.selectionStart).toBe(2);
    expect(slugInput.selectionEnd).toBe(5);
    expect(
      container.querySelector('[data-native-input="subtitle"]')
    ).not.toBeNull();
    expect(slugInput.name).toBe('settings[slug]');

    selectedTab.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'Home', bubbles: true})
    );
    await nextTick();
    expect(
      container.querySelector('[role="tab"][aria-selected="true"]')!.textContent
    ).toContain('New');
  });

  it('suppresses tab navigation when only one tab is present', () => {
    const registry = createCpComponentRegistry();
    const container = document.createElement('div');

    registry.register('form-element:craft:text-input', TextInputRenderer);
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

    registry.register('form-element:craft:text-input', TextInputRenderer);
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
    input.value = 'articles';
    input.dispatchEvent(new Event('input', {bubbles: true}));
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

    registry.register('form-element:craft:text-input', TextInputRenderer);
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

    const field = container.querySelector<HTMLElement>(
      '[data-form-element="craft:field"]'
    )!;
    const label = field.querySelector('label')!;
    const instructions = field.querySelector<HTMLElement>(
      '[data-form-element-instructions]'
    )!;
    const feedback = field.querySelector<HTMLElement>(
      '[data-form-element-errors]'
    )!;
    const input =
      field.querySelector<HTMLElementTagNameMap['craft-input']>('craft-input')!;

    expect(resolve).toHaveBeenCalledWith('form-element:craft:text-input');
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

    input.value = 'articles';
    input.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    expect(values.settings.handle).toBe('articles');
  });

  it('combines host and field read-only state', () => {
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

      registry.register('form-element:craft:text-input', TextInputRenderer);
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
    }
  });
});

function tabbedDefinition(insertSubtitle: boolean) {
  const metadataTab = {
    type: 'craft:tab',
    key: 'metadata',
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
        children: [
          {
            type: 'craft:tabs',
            key: 'settings-tabs',
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
