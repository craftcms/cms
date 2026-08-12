import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import IconPickerControl from './IconPickerControl.vue';
import type {FormControlPayload} from './types';

describe('IconPickerControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  it('leaves the label to its Form field', async () => {
    const control: FormControlPayload = {
      type: 'CraftCms\\Cms\\Form\\Controls\\IconPicker',
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
          invalid: false,
        }),
    });
    app.mount(container);
    await nextTick();

    expect(container.querySelector('craft-input')?.getAttribute('label')).toBe(
      null
    );
  });
});
