import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';

const mounted: Array<{props: any; element: HTMLElement}> = [];

vi.mock('vue', async () => {
  const actual = await vi.importActual<typeof import('vue')>('vue');

  return {
    ...actual,
    createApp: (_component: unknown, props: any) => ({
      mount: (element: HTMLElement) => {
        mounted.push({props, element});
        element.setAttribute('data-mounted', '');

        return {reset: vi.fn()};
      },
      unmount: vi.fn(),
    }),
  };
});

// The component itself is exercised separately; here only the decision of
// whether to render it matters.
vi.mock('@/modules/admin-table/components/LegacyAdminTable.vue', () => ({
  default: {name: 'LegacyAdminTable'},
}));

/** Stands in for the bundle's `Craft.VueAdminTable`. */
function legacyConstructor() {
  const calls: Array<any> = [];
  const Legacy = function (this: any, settings: any) {
    calls.push(settings);
    this.legacy = true;
  } as any;
  Legacy.defaults = {perPage: 100};

  return {Legacy, calls};
}

async function install() {
  vi.resetModules();
  mounted.length = 0;

  const {installLegacyAdminTableShim} = await import('./legacy-admin-table');
  installLegacyAdminTableShim();
}

beforeEach(() => {
  document.body.innerHTML = '<div id="table"></div>';
  (window as any).Craft = {sendActionRequest: vi.fn(), cp: {}};
});

afterEach(() => {
  delete (window as any).Craft;
  document.body.innerHTML = '';
});

it('renders a static-data table with the current component', async () => {
  await install();

  const table = new window.Craft.VueAdminTable({
    container: '#table',
    columns: [{name: 'status', title: 'Status'}],
    tableData: [{id: 1, status: 'ok'}],
    deleteAction: 'plugin/sync/delete',
  });

  expect(mounted).toHaveLength(1);
  expect(mounted[0]!.element).toBe(document.querySelector('#table'));
  expect(mounted[0]!.props.deleteAction).toBe('plugin/sync/delete');
  expect(table.settings.container).toBe('#table');
  expect(typeof table.reload).toBe('function');
});

it('hands unsupported options to the legacy implementation', async () => {
  const {Legacy, calls} = legacyConstructor();
  (window as any).Craft.VueAdminTable = Legacy;

  await install();

  for (const option of [
    'tableDataEndpoint',
    'search',
    'reorderAction',
    'checkboxes',
    'actions',
    'onRowClicked',
  ]) {
    new window.Craft.VueAdminTable({
      container: '#table',
      columns: [],
      tableData: [],
      [option]: option === 'search' || option === 'checkboxes' ? true : 'x',
    } as any);
  }

  expect(calls).toHaveLength(6);
  expect(mounted).toHaveLength(0);
});

/**
 * The load order isn't fixed: the legacy bundle is a classic script on a full
 * page load (so it runs first) and an appended script on a control panel visit
 * (so it runs second). Both have to end up with the shim in front.
 */
it('wins when the legacy bundle loaded first', async () => {
  const {Legacy} = legacyConstructor();
  (window as any).Craft.VueAdminTable = Legacy;

  await install();

  new window.Craft.VueAdminTable({
    container: '#table',
    columns: [],
    tableData: [],
  });

  expect(mounted).toHaveLength(1);
});

it('wins when the legacy bundle loads afterwards', async () => {
  await install();

  const {Legacy, calls} = legacyConstructor();
  // What the bundle's own `Craft.VueAdminTable = …` does.
  (window as any).Craft.VueAdminTable = Legacy;

  new window.Craft.VueAdminTable({
    container: '#table',
    columns: [],
    tableData: [],
  });

  expect(mounted).toHaveLength(1);

  // …and is still reachable to delegate to.
  new window.Craft.VueAdminTable({
    container: '#table',
    tableDataEndpoint: 'plugin/items',
  } as any);

  expect(calls).toHaveLength(1);
});

/**
 * The legacy constructor merges its settings with
 * `Craft.VueAdminTable.defaults`, read off the global — which is now the shim.
 */
it('forwards the legacy defaults, whichever order they arrive in', async () => {
  const {Legacy} = legacyConstructor();
  (window as any).Craft.VueAdminTable = Legacy;
  await install();

  expect(window.Craft.VueAdminTable.defaults).toEqual({perPage: 100});

  await install();
  (window as any).Craft.VueAdminTable = Legacy;

  expect(window.Craft.VueAdminTable.defaults).toEqual({perPage: 100});
});

it('delegates when the container cannot be found', async () => {
  const {Legacy, calls} = legacyConstructor();
  (window as any).Craft.VueAdminTable = Legacy;

  await install();

  new window.Craft.VueAdminTable({
    container: '#missing',
    columns: [],
    tableData: [],
  });

  expect(calls).toHaveLength(1);
  expect(mounted).toHaveLength(0);
});

it('throws a clear error when neither path is available', async () => {
  await install();

  expect(
    () =>
      new window.Craft.VueAdminTable({
        container: '#table',
        tableDataEndpoint: 'plugin/items',
      } as any)
  ).toThrow(/legacy admin table bundle/);
});

/**
 * The exact settings Shopify's sync utility passes, which is the case this
 * shim exists for. It must render rather than delegate, with no change to the
 * plugin.
 */
it('renders the Shopify sync utility’s table', async () => {
  await install();

  const table = new window.Craft.VueAdminTable({
    columns: [
      {name: 'objects', title: 'Objects'},
      {name: 'status', title: 'Status', callback: (value: string) => value},
      {name: 'shopifyStatus', title: 'Shopify Status'},
      {name: 'dateCreated', title: 'Date Created'},
      {name: 'dateUpdated', title: 'Date Updated'},
    ],
    container: '#table',
    tableData: [{id: 3, objects: '12', status: '<span>Done</span>'}],
    deleteAction: 'shopify/sync/delete',
    deleteConfirmationMessage: 'Are you sure?',
    deleteFailMessage: 'Sync could not be deleted',
    deleteSuccessMessage: 'Sync deleted',
    padded: true,
  } as any);

  expect(mounted).toHaveLength(1);
  expect(mounted[0]!.props.columns).toHaveLength(5);
  expect(mounted[0]!.props.deleteAction).toBe('shopify/sync/delete');
  expect(mounted[0]!.props.deleteConfirmationMessage).toBe('Are you sure?');
  expect(table.settings.padded).toBe(true);
});
