import {createApp, h, nextTick, ref} from 'vue';
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
    value = '';
  }
  customElements.define('craft-input-color', CraftInputColor);

  return {default: CraftInputColor};
});

type ColorSelect = HTMLElement & {
  modelValue: string | null;
  colors: string[];
  labels: Record<string, string>;
  allowTransparent: boolean;
  allowCustom: boolean;
};

type ColorInput = HTMLElement & {value: string};

describe('ColorControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  const value = ref('');

  async function mount(
    props: Record<string, unknown>,
    initialValue: string,
    {required = false, onUpdate = vi.fn()} = {}
  ) {
    value.value = initialValue;
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
          value: value.value,
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

  function type(input: ColorInput, value: string) {
    input.value = value;
    input.dispatchEvent(new Event('input', {bubbles: true}));
  }

  it('takes any color in a color input when there’s no palette', async () => {
    const root = await mount({presets: []}, 'e5422c');

    expect(root.querySelector('craft-input-color')).not.toBeNull();
    expect(root.querySelector('craft-select-color')).toBeNull();
  });

  it('offers "Custom…" beside the palette, which takes any color', async () => {
    const onUpdate = vi.fn();
    const root = await mount({}, '#2563eb', {required: true, onUpdate});
    const select = root.querySelector('craft-select-color') as ColorSelect;

    expect(select.allowCustom).toBe(true);
    expect(select.colors).toEqual(['#e5422b', '#2563eb']);
    expect(select.modelValue).toBe('#2563eb');
    expect(select.getAttribute('name')).toBe('color[color]');
    expect(root.querySelector('craft-input-color')).toBeNull();

    // Picking "Custom…" keeps the color until another is typed in.
    pick(select, '__custom__');
    await nextTick();
    const input = root.querySelector('craft-input-color') as ColorInput;

    expect(onUpdate).not.toHaveBeenCalled();
    expect(input.getAttribute('slot')).toBe('after');
    expect(input.getAttribute('name')).toBe('color[custom]');
    expect(input.value).toBe('#2563eb');

    type(input, '123456');
    expect(onUpdate).toHaveBeenLastCalledWith('123456', 'discrete');

    // Typing a preset's color in doesn't take "Custom…" away mid-edit.
    type(input, 'e5422b');
    value.value = 'e5422b';
    await nextTick();
    expect(select.modelValue).toBe('__custom__');
    expect(root.querySelector('craft-input-color')).not.toBeNull();
  });

  it('takes a pasted color as "Custom…" after typing the same one', async () => {
    const root = await mount({}, '#2563eb');
    const select = root.querySelector('craft-select-color') as ColorSelect;
    pick(select, '__custom__');
    await nextTick();
    type(root.querySelector('craft-input-color') as ColorInput, '123456');
    value.value = '123456';
    await nextTick();

    // As when a palette color is pasted in, then the typed one again.
    value.value = '#e5422b';
    await nextTick();
    expect(select.modelValue).toBe('#e5422b');

    value.value = '123456';
    await nextTick();
    expect(select.modelValue).toBe('__custom__');
    expect((root.querySelector('craft-input-color') as ColorInput).value).toBe(
      '123456'
    );
  });

  it('shows a color from elsewhere as a preset or as "Custom…"', async () => {
    const root = await mount({}, '#123456', {required: true});
    const select = root.querySelector('craft-select-color') as ColorSelect;

    expect(select.modelValue).toBe('__custom__');
    expect(select.colors).toEqual(['#e5422b', '#2563eb']);
    expect(select.allowTransparent).toBe(false);
    expect((root.querySelector('craft-input-color') as ColorInput).value).toBe(
      '#123456'
    );

    // As when a palette color is pasted in.
    value.value = 'E5422B';
    await nextTick();

    expect(select.modelValue).toBe('#e5422b');
    expect(root.querySelector('craft-input-color')).toBeNull();
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
    expect(select.allowCustom).toBe(false);
    expect(select.getAttribute('name')).toBe('color');
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
