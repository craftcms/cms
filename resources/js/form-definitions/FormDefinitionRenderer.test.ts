import {createApp, nextTick, reactive} from 'vue';
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
