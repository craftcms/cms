import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import ColorControl from './ColorControl.vue';
import type {UiControlPayload} from './types';

// The real select renders a `craft-select-rich`, whose Lion internals need
// layout happy-dom doesn't provide, so bare elements stand in for both.
vi.mock('@craftcms/ui/components/select-color/select-color', () => {
  class CraftSelectColor extends HTMLElement {
    modelValue: string | null = null;
  }
  customElements.define('craft-select-color', CraftSelectColor);

  return {default: CraftSelectColor};
});
vi.mock('@craftcms/ui/components/input-color/input-color', () => {
  class CraftInputColor extends HTMLElement {
    modelValue: string | null = null;
  }
  customElements.define('craft-input-color', CraftInputColor);

  return {default: CraftInputColor};
});

type ColorSelect = HTMLElement & {
  modelValue: string | null;
  colors: string[];
  labels: Record<string, string>;
  allowTransparent: boolean;
};

describe('ColorControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  async function mount(
    props: Record<string, unknown>,
    value: string,
    {required = false, onUpdate = vi.fn()} = {}
  ) {
    const control: UiControlPayload<{presets: string[]}> = {
      type: 'CraftCms\\Cms\\Ui\\Controls\\Color',
      component: 'craft:color',
      props: {presets: ['#e5422b', '#2563eb'], ...props},
      path: ['color'],
      mode: 'editable',
      deltaGroup: ['color'],
    };
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(ColorControl, {
          control,
          value,
          editable: true,
          invalid: false,
          required,
          'onUpdate:value': onUpdate,
        }),
    });
    app.mount(container);
    await nextTick();

    return container;
  }

  function pick(select: ColorSelect, modelValue: string) {
    select.modelValue = modelValue;
    select.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true, composed: true})
    );
  }

  it('takes any color in a color input when custom colors are allowed', async () => {
    const root = await mount({}, 'e5422c');

    expect(root.querySelector('craft-input-color')).not.toBeNull();
    expect(root.querySelector('craft-select-color')).toBeNull();
  });

  it('picks from the presets, by their labels, when custom colors aren’t allowed', async () => {
    const onUpdate = vi.fn();
    const root = await mount(
      {custom: false, presetLabels: {'#e5422b': 'Craft red'}},
      'E5422B',
      {required: true, onUpdate}
    );
    const select = root.querySelector('craft-select-color') as ColorSelect;

    expect(root.querySelector('craft-input-color')).toBeNull();
    expect(select.colors).toEqual(['#e5422b', '#2563eb']);
    expect(select.labels).toEqual({'#e5422b': 'Craft red'});
    expect(select.modelValue).toBe('#e5422b');
    expect(select.allowTransparent).toBe(false);

    pick(select, '#2563eb');
    expect(onUpdate).toHaveBeenLastCalledWith('#2563eb', 'discrete');
  });

  it('lists a value that isn’t a preset, with "no color", so the select shows it', async () => {
    const onUpdate = vi.fn();
    const root = await mount({custom: false}, '#123456', {
      required: true,
      onUpdate,
    });
    const select = root.querySelector('craft-select-color') as ColorSelect;

    expect(select.colors).toEqual(['#e5422b', '#2563eb', '#123456']);
    expect(select.modelValue).toBe('#123456');
    expect(select.allowTransparent).toBe(true);

    pick(select, '__blank__');
    expect(onUpdate).toHaveBeenLastCalledWith('', 'discrete');
  });
});
