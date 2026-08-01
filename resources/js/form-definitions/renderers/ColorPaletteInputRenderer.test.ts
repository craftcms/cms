import {createApp, nextTick, reactive} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormDefinitionRenderer from '../FormDefinitionRenderer.vue';
import ColorPaletteInputRenderer from './ColorPaletteInputRenderer.vue';

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

    expect(container.querySelectorAll('craft-input-color')).toHaveLength(2);
    expect(container.querySelectorAll('craft-input')).toHaveLength(2);
    expect(container.querySelectorAll('craft-checkbox')).toHaveLength(2);
    expect(container.querySelectorAll('craft-reorder-button')).toHaveLength(2);
    expect(container.querySelectorAll('craft-button')).toHaveLength(3);
    expect(
      container
        .querySelector('[data-palette-color="0"]')
        ?.getAttribute('aria-label')
    ).toBe('Color for Red');
    expect(
      container
        .querySelector('[data-palette-label="0"]')
        ?.getAttribute('aria-label')
    ).toBe('Label for Red');
    expect(
      container.querySelector<HTMLElementTagNameMap['craft-reorder-button']>(
        'craft-reorder-button'
      )?.label
    ).toBe('Reorder Red');
    expect(
      container.querySelector('craft-button')?.getAttribute('aria-label')
    ).toBe('Delete Red');
    expect(container.querySelector('th:last-child')?.textContent).toBe(
      'Actions'
    );

    const label = container.querySelector<HTMLElementTagNameMap['craft-input']>(
      '[data-palette-label="0"]'
    )!;
    label.value = 'Crimson';
    label.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    const color = container.querySelector<
      HTMLElementTagNameMap['craft-input-color']
    >('[data-palette-color="0"]')!;
    color.value = '00ff00';
    color.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    container
      .querySelector<HTMLElement & {checked: boolean}>(
        '[data-palette-default="1"]'
      )!
      .dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();

    expect(values.settings.palette).toEqual([
      {color: '#00ff00', label: 'Crimson', default: false},
      {color: '#0000ff', label: '', default: true},
    ]);

    container
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

  it('disables every palette control when read-only', () => {
    const container = mount(
      {
        settings: {
          palette: [{color: '#ff0000', label: 'Red', default: true}],
        },
      },
      true
    );

    for (const control of container.querySelectorAll<
      HTMLElement & {disabled: boolean}
    >(
      'craft-input-color, craft-input, craft-checkbox, craft-reorder-button, craft-button'
    )) {
      expect(control.disabled).toBe(true);
    }
  });
});

function mount(values: Record<string, unknown>, readOnly = false): HTMLElement {
  const registry = createCpComponentRegistry();
  const container = document.createElement('div');

  registry.register(
    'form-element:craft:color-palette-input',
    ColorPaletteInputRenderer
  );
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
    errors: {},
    readOnly,
  });

  mountedApps.push(app);
  app.mount(container);

  return container;
}
