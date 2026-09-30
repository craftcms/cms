import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import ColorSelectControl from './ColorSelectControl.vue';
import type {FormControlPayload} from './types';

// The real element renders a `craft-select-rich`, whose Lion internals need
// layout happy-dom doesn't provide, so a bare element stands in for it.
vi.mock('@craftcms/ui/components/select-color/select-color', () => {
  class CraftSelectColor extends HTMLElement {
    modelValue: string | null = null;
    colors: string[] = [];
  }
  customElements.define('craft-select-color', CraftSelectColor);

  return {default: CraftSelectColor};
});

describe('ColorSelectControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  async function mount(value: string, onUpdate = vi.fn()) {
    const control: FormControlPayload<{
      allowTransparent: boolean;
      blankLabel: string;
      colors: string[];
    }> = {
      type: 'CraftCms\\Cms\\Form\\Controls\\ColorSelect',
      component: 'craft:color-select',
      props: {
        allowTransparent: true,
        blankLabel: 'No color',
        colors: ['red', 'blue'],
      },
      path: ['color'],
      mode: 'editable',
      deltaGroup: ['color'],
    };
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(ColorSelectControl, {
          control,
          value,
          editable: true,
          invalid: false,
          required: false,
          'onUpdate:value': onUpdate,
        }),
    });
    app.mount(container);
    await nextTick();

    return container.querySelector('craft-select-color') as HTMLElement & {
      modelValue: string | null;
      colors: string[];
    };
  }

  function pick(
    select: HTMLElement & {modelValue: string | null},
    modelValue: string
  ) {
    select.modelValue = modelValue;
    select.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true, composed: true})
    );
  }

  it('offers the control’s colors and selects "no color" for an empty value', async () => {
    const select = await mount('');

    expect(select.colors).toEqual(['red', 'blue']);
    expect(select.getAttribute('allow-transparent')).toBe('true');
    expect(select.getAttribute('blank-label')).toBe('No color');
    expect(select.modelValue).toBe('__blank__');
    expect(select.getAttribute('name')).toBe('color');
  });

  it('reports a picked color, and "no color" as an empty value', async () => {
    const onUpdate = vi.fn();
    const select = await mount('red', onUpdate);

    pick(select, 'blue');
    expect(onUpdate).toHaveBeenLastCalledWith('blue', 'discrete');

    pick(select, '__blank__');
    expect(onUpdate).toHaveBeenLastCalledWith('', 'discrete');
  });
});
