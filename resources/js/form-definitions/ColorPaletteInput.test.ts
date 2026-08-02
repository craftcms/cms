import {createApp, nextTick, reactive} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormDefinitionRenderer from './FormDefinitionRenderer.vue';
import {registerNativeFormElementRenderers} from './form-element-types';

const mountedApps: Array<ReturnType<typeof createApp>> = [];

afterEach(() => {
  mountedApps.splice(0).forEach((app) => app.unmount());
  document.body.innerHTML = '';
});

describe('Color Palette Form Element', () => {
  it('renders and edits ordered palette rows with Craft controls', async () => {
    const values = reactive({
      settings: {
        palette: [
          {color: '#ff0000', label: 'Red', default: true},
          {color: '#0000ff', label: '', default: false},
        ],
      },
    });
    const container = mount(values);
    const palette = container.querySelector<
      HTMLElementTagNameMap['craft-color-palette']
    >('craft-color-palette')!;
    await palette.updateComplete;
    const root = palette.shadowRoot!;

    expect(palette.value).toEqual(values.settings.palette);
    expect(root.querySelectorAll('craft-input-color')).toHaveLength(2);
    expect(root.querySelectorAll('craft-input')).toHaveLength(2);
    expect(root.querySelectorAll('craft-checkbox')).toHaveLength(2);
    expect(root.querySelectorAll('craft-reorder-button')).toHaveLength(2);
    expect(root.querySelectorAll('craft-button')).toHaveLength(3);
    expect(
      root.querySelector('[data-palette-color="0"]')?.getAttribute('aria-label')
    ).toBe('Color for Red');
    expect(
      root.querySelector('[data-palette-label="0"]')?.getAttribute('aria-label')
    ).toBe('Label for Red');
    expect(
      root.querySelector<HTMLElementTagNameMap['craft-reorder-button']>(
        'craft-reorder-button'
      )?.label
    ).toBe('Reorder Red');
    expect(root.querySelector('craft-button')?.getAttribute('aria-label')).toBe(
      'Delete Red'
    );
    expect(root.querySelector('th:last-child')?.textContent?.trim()).toBe(
      'Actions'
    );

    const label = root.querySelector<HTMLElementTagNameMap['craft-input']>(
      '[data-palette-label="0"]'
    )!;
    label.value = 'Crimson';
    label.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    const color = root.querySelector<
      HTMLElementTagNameMap['craft-input-color']
    >('[data-palette-color="0"]')!;
    color.value = '00ff00';
    color.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    root
      .querySelector<HTMLElement & {checked: boolean}>(
        '[data-palette-default="1"]'
      )!
      .dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();

    expect(values.settings.palette).toEqual([
      {color: '#00ff00', label: 'Crimson', default: false},
      {color: '#0000ff', label: '', default: true},
    ]);

    root
      .querySelector<HTMLElementTagNameMap['craft-reorder-button']>(
        '[data-palette-row="1"] craft-reorder-button'
      )!
      .dispatchEvent(
        new CustomEvent('reorder', {
          bubbles: true,
          detail: {direction: 'up'},
        })
      );
    await nextTick();

    expect(values.settings.palette.map(({label}) => label)).toEqual([
      '',
      'Crimson',
    ]);
  });

  it('disables every palette control when read-only', async () => {
    const container = mount(
      {
        settings: {
          palette: [{color: '#ff0000', label: 'Red', default: true}],
        },
      },
      true,
      {'settings.palette': ['Palette is invalid.']}
    );
    const palette = container.querySelector<
      HTMLElementTagNameMap['craft-color-palette']
    >('craft-color-palette')!;
    await palette.updateComplete;

    for (const control of palette.shadowRoot!.querySelectorAll<
      HTMLElement & {disabled: boolean}
    >(
      'craft-input-color, craft-input, craft-checkbox, craft-reorder-button, craft-button'
    )) {
      expect(control.disabled).toBe(true);
    }

    expect(
      container.querySelector('[data-form-element-errors]')?.textContent
    ).toContain('Palette is invalid.');
  });
});

function mount(
  values: Record<string, unknown>,
  readOnly = false,
  errors: Record<string, string[]> = {}
): HTMLElement {
  const registry = createCpComponentRegistry();
  const container = document.createElement('div');

  registerNativeFormElementRenderers(registry);
  (window as any).Cp = {$components: registry};
  document.body.appendChild(container);
  const app = createApp(FormDefinitionRenderer, {
    definition: {
      elements: [
        {
          type: 'craft:field',
          children: [{type: 'craft:color-palette-input', name: 'palette'}],
        },
      ],
    },
    bindingScope: 'settings',
    values,
    errors,
    readOnly,
  });

  mountedApps.push(app);
  app.mount(container);

  return container;
}
