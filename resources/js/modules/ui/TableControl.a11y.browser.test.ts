import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {page} from 'vite-plus/test/browser/context';
import {createApp, h, nextTick, type App} from 'vue';
import {createCpComponentRegistry} from '@/bootstrap/components';
import UiRenderer from './UiRenderer.vue';
import {registerUiComponents} from './register';
import type {UiNodePayload, UiPayload, UiProperties} from './types';

/**
 * Each editable table cell must be named after its column header and row
 * (WCAG 4.1.2), and tables sharing column keys must not label each other's
 * inputs. Runs in Chromium so the names are the browser's own.
 */

const columns = {
  title: {heading: 'Title', type: 'singleline'},
  enabled: {heading: 'Enabled', type: 'lightswitch'},
  homepage: {heading: 'Homepage', type: 'checkbox'},
  route: {
    heading: 'Route',
    type: 'singleline',
    prefixSelect: {
      key: 'routeType',
      label: 'Route type',
      options: [
        {label: 'Template', value: 'template'},
        {label: 'Route', value: 'route'},
      ],
    },
  },
};

const cellControls: Array<[string, string, string, string, UiProperties?]> = [
  ['title', 'Title', 'Text', 'craft:text'],
  ['enabled', 'Enabled', 'Lightswitch', 'craft:lightswitch'],
  ['homepage', 'Homepage', 'Checkbox', 'craft:checkbox'],
  ['route', 'Route', 'Text', 'craft:text'],
  [
    'routeType',
    'Route type',
    'Choice',
    'craft:choice',
    {
      options: columns.route.prefixSelect.options,
      multiple: false,
      presentation: 'select',
      placeholder: false,
    },
  ],
];

function cells(scope: string[], deltaGroup: string[]): UiNodePayload[] {
  return cellControls.map(([key, label, type, component, props]) => ({
    type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
    component: 'craft:field',
    props: {label, labelSrOnly: true},
    control: {
      type: `CraftCms\\Cms\\Ui\\Controls\\${type}`,
      component,
      props: props ?? {},
      path: [...scope, key],
      mode: 'editable',
      deltaGroup,
    },
  }));
}

function table(path: string, rowKeys: string[]): UiNodePayload {
  return {
    type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
    component: 'craft:field',
    props: {},
    control: {
      type: 'CraftCms\\Cms\\Ui\\Controls\\Table',
      component: 'craft:table',
      nestsUis: true,
      path: [path],
      deltaGroup: [path],
      mode: 'editable',
      props: {
        columns,
        keyed: true,
        defaultValues: row,
        rowTemplate: {scope: [], refreshable: false, nodes: cells([], [path])},
      },
      uis: rowKeys.map((key) => ({
        scope: [path, key],
        refreshable: false,
        nodes: cells([path, key], [path]),
      })),
    },
  };
}

const row = {
  title: 'Craft',
  enabled: true,
  homepage: false,
  route: '',
  routeType: 'template',
};

// Two tables with the same column keys, the way the section form has them.
const payload: UiPayload = {
  scope: [],
  refreshable: false,
  globalErrors: [],
  errors: [],
  values: {
    sites: {default: row, other: row},
    previewTargets: {default: row},
  },
  nodes: [
    table('sites', ['default', 'other']),
    table('previewTargets', ['default']),
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

async function mount(): Promise<HTMLElement> {
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

  return host;
}

const roles: Record<string, 'textbox' | 'switch' | 'checkbox' | 'combobox'> = {
  Title: 'textbox',
  Enabled: 'switch',
  Homepage: 'checkbox',
  Route: 'textbox',
  'Route type': 'combobox',
};

it('names each cell after its column header and row', async () => {
  await mount();

  // Row 1 appears in both tables, row 2 only in the first.
  const expected: Array<[role: string, name: string, count: number]> = [
    ...Object.entries(roles)
      .filter(([heading]) => heading !== 'Route type')
      .flatMap(
        ([heading, role]): Array<[string, string, number]> => [
          [role, `${heading}, row 1`, 2],
          [role, `${heading}, row 2`, 1],
        ]
      ),
    ['combobox', 'Route type', 3],
  ];

  for (const [role, name, count] of expected) {
    const matches = page
      .getByRole(role as (typeof roles)[string], {name, exact: true})
      .elements();

    expect.soft(matches, `${role} "${name}"`).toHaveLength(count);
  }
});

it('labels inputs from their own table when tables share column keys', async () => {
  const root = await mount();
  const tables = [...root.querySelectorAll('table')];
  expect(tables).toHaveLength(2);

  for (const tableElement of tables) {
    for (const element of tableElement.querySelectorAll('[aria-labelledby]')) {
      for (const id of element.getAttribute('aria-labelledby')!.split(/\s+/)) {
        const label = id ? document.getElementById(id) : null;

        if (label) {
          expect(
            tableElement.contains(label),
            `#${id} labels an input in another table`
          ).toBe(true);
        }
      }
    }
  }

  const ids = [...root.querySelectorAll('[id]')].map((element) => element.id);
  expect(ids.length).toBe(new Set(ids).size);
});
