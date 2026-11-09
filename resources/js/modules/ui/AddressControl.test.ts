import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import AddressControl from './AddressControl.vue';
import type {UiControlPayload} from './types';

describe('AddressControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  async function mount(administrativeArea: string | null): Promise<void> {
    const control: UiControlPayload<any> = {
      type: 'CraftCms\\Cms\\Ui\\Controls\\Address',
      component: 'craft:address',
      props: {
        countryCode: 'CA',
        belongsToCurrentUser: true,
        fields: [
          {
            name: 'administrativeArea',
            label: 'Province',
            type: 'select',
            visible: true,
            required: true,
            autocomplete: 'address-level1',
            options: {AB: 'Alberta', NS: 'Nova Scotia'},
          },
        ],
      },
      path: ['address'],
      mode: 'editable',
      deltaGroup: ['address'],
    };
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(AddressControl, {
          control,
          value: {administrativeArea},
          editable: true,
          invalid: false,
        }),
    });
    app.mount(container);
    await nextTick();
  }

  it('leaves a select with no value empty, rather than on its first option', async () => {
    await mount(null);

    const select = container!.querySelector('select')!;
    expect(select.value).toBe('');
    expect(select.options[0]!.value).toBe('');
  });

  it('keeps a chosen option selected', async () => {
    await mount('NS');

    expect(container!.querySelector('select')!.value).toBe('NS');
  });
});
