import {createApp, h, nextTick, type App} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import {computeAccessibleName} from 'dom-accessibility-api';
import AddressControl from './AddressControl.vue';
import type {UiControlPayload} from './types';

let app: App | undefined;
let container: HTMLElement | undefined;

afterEach(() => {
  app?.unmount();
  container?.remove();
  app = undefined;
  container = undefined;
});

type AddressPayload = InstanceType<typeof AddressControl>['$props']['control'];

/** Mounts an address form like a United States one: three required fields. */
async function mountAddress(): Promise<HTMLElement> {
  const control = {
    type: 'CraftCms\\Cms\\Ui\\Controls\\Address',
    component: 'craft:address',
    path: ['address'],
    mode: 'editable',
    deltaGroup: ['address'],
    props: {
      countryCode: 'US',
      belongsToCurrentUser: true,
      fields: [
        {
          name: 'addressLine1',
          label: 'Address Line 1',
          type: 'text',
          visible: true,
          required: true,
          autocomplete: 'address-line1',
        },
        {
          name: 'addressLine2',
          label: 'Address Line 2',
          type: 'text',
          visible: true,
          required: false,
          autocomplete: 'address-line2',
        },
        {
          name: 'administrativeArea',
          label: 'State',
          type: 'select',
          visible: true,
          required: true,
          spinner: true,
          autocomplete: 'address-level1',
          options: {CA: 'California', NY: 'New York'},
        },
        {
          name: 'locality',
          label: 'City',
          type: 'text',
          visible: true,
          required: true,
          autocomplete: 'address-level2',
        },
        {
          name: 'postalCode',
          label: 'Zip Code',
          type: 'text',
          visible: true,
          required: false,
          autocomplete: 'postal-code',
        },
      ],
    },
  } satisfies AddressPayload;

  container = document.createElement('div');
  document.body.append(container);
  app = createApp({
    render: () => h(AddressControl, {control, value: {}, editable: true}),
  });
  app.mount(container);
  await nextTick();
  await Promise.all(
    [
      ...container.querySelectorAll('craft-field, craft-input, craft-select'),
    ].map(
      (el) =>
        (el as HTMLElement & {updateComplete: Promise<unknown>}).updateComplete
    )
  );
  await nextTick();

  return container;
}

/** The native control each field's label belongs to, by its name. */
function controlNamed(root: HTMLElement, name: string): HTMLElement {
  const control = [...root.querySelectorAll<HTMLElement>('input, select')].find(
    (el) => computeAccessibleName(el).startsWith(name)
  );
  if (!control) {
    throw new Error(`No control named “${name}”.`);
  }
  return control;
}

it('names each field by its visible label', async () => {
  const root = await mountAddress();

  for (const name of [
    'Address Line 1',
    'Address Line 2',
    'State',
    'City',
    'Zip Code',
  ]) {
    expect(controlNamed(root, name)).toBeDefined();
  }
});

it('marks the required fields as required, and only those', async () => {
  const root = await mountAddress();

  for (const name of ['Address Line 1', 'State', 'City']) {
    expect(controlNamed(root, name).getAttribute('aria-required')).toBe('true');
  }
  for (const name of ['Address Line 2', 'Zip Code']) {
    expect(controlNamed(root, name).hasAttribute('aria-required')).toBe(false);
  }
});

it('focuses each field when its label is clicked', async () => {
  const root = await mountAddress();

  for (const field of root.querySelectorAll('craft-field')) {
    field.querySelector<HTMLElement>(':scope > [slot="label"]')!.click();

    const focused = document.activeElement!;
    expect(field.contains(focused)).toBe(true);
    expect(focused.matches('input, select')).toBe(true);
  }
});

it('labels each control, not a group around it', async () => {
  const root = await mountAddress();

  for (const field of root.querySelectorAll('craft-field')) {
    expect(field.getAttribute('role')).not.toBe('group');
  }
});

it('keeps each field’s name and autocomplete value', async () => {
  const root = await mountAddress();

  const line1 = controlNamed(root, 'Address Line 1') as HTMLInputElement;
  expect(line1.name).toBe('address[addressLine1]');
  expect(line1.getAttribute('autocomplete')).toBe('address-line1');

  expect(controlNamed(root, 'State').getAttribute('autocomplete')).toBe(
    'address-level1'
  );
});

describe('AddressControl empty selects', () => {
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
