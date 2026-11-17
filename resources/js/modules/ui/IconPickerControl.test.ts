import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import IconPickerControl from './IconPickerControl.vue';
import type {UiControlPayload} from './types';

describe('IconPickerControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  async function mount(invalid: boolean): Promise<HTMLElement> {
    const control: UiControlPayload = {
      type: 'CraftCms\\Cms\\Ui\\Controls\\IconPicker',
      component: 'craft:icon-picker',
      props: {freeOnly: false},
      path: ['icon'],
      mode: 'editable',
      deltaGroup: ['icon'],
    };
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(IconPickerControl, {
          control,
          value: '',
          label: 'Icon',
          editable: true,
          invalid,
        }),
    });
    app.mount(container);
    await nextTick();

    return container;
  }

  it('leaves the label to its UI field', async () => {
    const container = await mount(false);

    expect(container.querySelector('craft-input')?.getAttribute('label')).toBe(
      null
    );
  });

  it('leaves the error message to its UI field', async () => {
    const container = await mount(true);

    expect(
      (
        container.querySelector('craft-input') as HTMLElement & {
          invalid: boolean;
        }
      ).invalid
    ).toBe(true);
    expect(container.querySelector('.error-list')).toBeNull();
  });
});
