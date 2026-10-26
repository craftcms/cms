import '../../../css/cp.css';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser/context';
import {createApp, h, nextTick, type App} from 'vue';
import {createCpComponentRegistry} from '@/bootstrap/components';
import UiRenderer from './UiRenderer.vue';
import {registerUiComponents} from './register';
import type {UiNodePayload, UiPayload} from './types';

const options = [
  {label: 'Anthropic', value: 'anthropic'},
  {label: 'Azure', value: 'azure'},
];

function comboboxField(path: string[]): UiNodePayload {
  return {
    type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
    component: 'craft:field',
    props: {label: 'Provider', labelSrOnly: true},
    control: {
      type: 'CraftCms\\Cms\\Ui\\Controls\\Combobox',
      component: 'craft:combobox',
      props: {options, showAllOnEmpty: true, clearable: true},
      path,
      mode: 'editable',
      deltaGroup: [path[0]!],
    },
  };
}

const payload: UiPayload = {
  scope: [],
  refreshable: false,
  globalErrors: [],
  errors: [],
  values: {rows: [{provider: 'anthropic'}]},
  nodes: [
    {
      type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
      component: 'craft:field',
      props: {label: 'Rows'},
      control: {
        type: 'CraftCms\\Cms\\Ui\\Controls\\Table',
        component: 'craft:table',
        nestsUis: true,
        path: ['rows'],
        deltaGroup: ['rows'],
        mode: 'editable',
        props: {
          columns: {provider: {heading: 'Provider', type: 'autosuggest'}},
          rowTemplate: {
            scope: [],
            refreshable: false,
            nodes: [comboboxField(['provider'])],
          },
        },
        uis: [
          {
            scope: ['rows', '0'],
            refreshable: false,
            nodes: [comboboxField(['rows', '0', 'provider'])],
          },
        ],
      },
    },
  ],
};

let app: App | null = null;
let host: HTMLElement | null = null;

beforeEach(() => {
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue(new Response('<svg></svg>'))
  );
});

afterEach(() => {
  app?.unmount();
  app = null;
  host?.remove();
  host = null;
  vi.unstubAllGlobals();
});

async function mount() {
  host = document.createElement('div');
  document.body.append(host);
  app = createApp({render: () => h(UiRenderer, {payload})});
  const registry = createCpComponentRegistry();
  registerUiComponents(registry);
  registry.install(app);
  app.mount(host);
  await nextTick();

  for (const element of host.querySelectorAll('*')) {
    if ('updateComplete' in element) {
      await (element as {updateComplete: Promise<unknown>}).updateComplete;
    }
  }
  await nextTick();

  const cell = host.querySelector<HTMLTableCellElement>(
    'td[data-column="provider"]'
  )!;
  const combobox = cell.querySelector('craft-combobox')!;

  return {
    cell,
    combobox,
    input: combobox.querySelector<HTMLInputElement>('input')!,
    chevron: combobox.shadowRoot!.querySelector<HTMLElement>('.indicator')!,
  };
}

it('focuses a combobox cell’s textbox rather than its clear button', async () => {
  const {cell, combobox, input, chevron} = await mount();
  const cellBox = cell.getBoundingClientRect();

  expect(combobox.shadowRoot!.querySelector('.clear')).not.toBeNull();

  await userEvent.click(cell, {position: {x: 2, y: cellBox.height / 2}});
  expect(document.activeElement).toBe(input);

  input.blur();
  await userEvent.click(chevron);
  await new Promise((resolve) => setTimeout(resolve, 50));

  expect(document.activeElement).toBe(input);
  expect(combobox.opened).toBe(true);
});
