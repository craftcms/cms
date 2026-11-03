import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import ColorSelectControl from './ColorSelectControl.vue';
import type {UiControlPayload} from './types';

// A bare element stands in for the real one, so the test can raise its
// events directly.
vi.mock('@craftcms/ui/components/select-color/select-color', () => {
  class CraftSelectColor extends HTMLElement {
    value: string | null = null;
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
    const control: UiControlPayload<{
      allowTransparent: boolean;
      blankLabel: string;
      colors: string[];
    }> = {
      type: 'CraftCms\\Cms\\Ui\\Controls\\ColorSelect',
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

    return container.querySelector(
      'craft-select-color'
    ) as unknown as HTMLElement & {
      value: string | null;
      colors: string[];
      allowTransparent: boolean;
    };
  }

  function pick(select: HTMLElement & {value: string | null}, value: string) {
    select.value = value;
    select.dispatchEvent(new Event('input', {bubbles: true, composed: true}));
    select.dispatchEvent(new Event('change', {bubbles: true, composed: true}));
  }

  it('offers the control’s colors and selects "no color" for an empty value', async () => {
    const select = await mount('');

    expect(select.colors).toEqual(['red', 'blue']);
    expect(select.allowTransparent).toBe(true);
    expect(select.getAttribute('blank-label')).toBe('No color');
    expect(select.value).toBe('__blank__');
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
