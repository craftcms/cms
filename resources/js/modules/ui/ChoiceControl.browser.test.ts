import {afterEach, expect, it, vi} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import {createApp, h, shallowRef, type App} from 'vue';
import {createCpComponentRegistry} from '@/bootstrap/components';
import UiRenderer from './UiRenderer.vue';
import {registerUiComponents} from './register';
import type {UiPayload} from './types';

let app: App | undefined;
let host: HTMLElement | undefined;

afterEach(() => {
  app?.unmount();
  host?.remove();
  vi.unstubAllGlobals();
});

it('keeps the native required choice invalid state in sync with server errors', async () => {
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue(new Response('<svg></svg>'))
  );
  const errors = shallowRef<UiPayload['errors']>([]);
  const payload: UiPayload = {
    scope: [],
    refreshable: false,
    errors: [],
    globalErrors: [],
    values: {entryId: ''},
    nodes: [
      {
        type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
        component: 'craft:field',
        props: {label: 'Entry', required: true},
        control: {
          type: 'CraftCms\\Cms\\Ui\\Controls\\Choice',
          component: 'craft:choice',
          path: ['entryId'],
          deltaGroup: ['entryId'],
          mode: 'editable',
          props: {
            multiple: false,
            presentation: 'select',
            options: [
              {label: 'Home', value: '1'},
              {label: 'About', value: '2'},
            ],
          },
        },
      },
    ],
  };
  host = document.createElement('div');
  document.body.append(host);
  let values: UiPayload['values'] | undefined;
  app = createApp({
    render: () =>
      h(UiRenderer, {
        payload,
        errors: errors.value,
        onChange: (_change: unknown, currentValues: UiPayload['values']) => {
          values = currentValues;
        },
      }),
  });
  const registry = createCpComponentRegistry();
  registerUiComponents(registry);
  registry.install(app);
  app.onUnmount(() => registry.uninstall(app!));
  app.mount(host);

  const select = page.getByRole('combobox', {name: 'Entry Required'});
  await expect.element(select).toHaveValue('');
  await expect.element(select).toHaveAttribute('required');
  errors.value = [{path: ['entryId'], messages: ['Choose an entry.']}];
  await expect.element(select).toHaveAttribute('aria-invalid', 'true');
  const native = host.querySelector('select')!;
  const describedBy = native.getAttribute('aria-describedby')!.split(' ');
  expect(
    describedBy.some(
      (id) => document.getElementById(id)?.textContent === 'Choose an entry.'
    )
  ).toBe(true);

  await userEvent.selectOptions(select, '2');
  await expect.element(select).toHaveValue('2');
  expect(values).toEqual({entryId: '2'});
  errors.value = [];
  await expect.element(select).not.toHaveAttribute('aria-invalid', 'true');

  errors.value = [
    {path: ['entryId'], messages: ['This entry is unavailable.']},
  ];
  await expect.element(select).toHaveAttribute('aria-invalid', 'true');
});
