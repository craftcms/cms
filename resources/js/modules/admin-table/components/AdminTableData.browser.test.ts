import '../../../../css/cp.css';
import {createApp, defineComponent, h, nextTick, reactive, ref} from 'vue';
import {
  mergeDataIntoQueryString,
  router as inertiaRouter,
} from '@inertiajs/core';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import AdminTable from './AdminTable.vue';
import AdminTableNode from '@/modules/ui/AdminTableNode.vue';
import {actionClient} from '@craftcms/ui';
import type {TableProps} from '@/modules/ui/table-types';
import type {
  AdminTableHandle,
  AdminTablePage,
  AdminTableRequest,
} from '../types';

const {reload, get, visit} = vi.hoisted(() => ({
  reload: vi.fn(),
  get: vi.fn(),
  visit: vi.fn(),
}));
const slideout = vi.hoisted(() => vi.fn().mockResolvedValue(null));
let inertiaPage: {url: string; props: {readOnly: boolean}};

vi.mock('@/common/slideouts', async () => ({
  ...(await vi.importActual('@/common/slideouts')),
  openSlideout: slideout,
}));

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => inertiaPage,
  router: {reload, get, visit},
}));

const records = [
  {id: 1, name: 'Beta', status: 'enabled', count: 2},
  {id: 2, name: 'Alpha', status: 'disabled', count: 10},
];
const columns = [
  {accessorKey: 'name', header: 'Name', enableSorting: true},
  {accessorKey: 'count', header: 'Count'},
];
let teardown: (() => void) | undefined;
beforeEach(() => {
  localStorage.clear();
  vi.stubGlobal('Craft', {systemUid: 'admin-table-test'});
  reload.mockClear();
  get.mockReset();
  slideout.mockClear();
  visit.mockClear();
  inertiaPage = reactive({url: '/', props: {readOnly: false}});
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue(new Response('<svg></svg>'))
  );
});
afterEach(() => {
  teardown?.();
  document.body.innerHTML = '';
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
});

function mountTable(options: Record<string, unknown> = {}) {
  const host = document.createElement('div');
  document.body.append(host);
  const handle = ref<AdminTableHandle>();
  const app = createApp({
    render: () =>
      h(AdminTable, {
        ref: handle,
        rows: records,
        columns,
        searchable: true,
        selectable: true,
        columnsToggleable: true,
        getRowStatus: (row: (typeof records)[number]) => row.status,
        statusFilterOptions: [
          {label: 'All', value: ''},
          {label: 'Enabled', value: 'enabled'},
          {label: 'Disabled', value: 'disabled'},
        ],
        ...options,
      } as never),
  });
  app.mount(host);
  teardown = () => app.unmount();
  return {host, handle};
}

function mountNode(
  options: Partial<TableProps> = {},
  nodeProps: {onRefresh?: () => void} = {}
) {
  const host = document.createElement('div');
  document.body.append(host);
  const app = createApp(() =>
    h(AdminTableNode, {
      node: {
        uid: 'settings-table',
        type: 'CraftCms\\Cms\\Ui\\Nodes\\Table',
        component: 'craft:admin-table',
        children: [],
        props: {
          columns: [{key: 'name', label: 'Name'}],
          rows: records,
          dataUrl: null,
          perPage: 50,
          perPageOptions: [50, 100],
          moveToPageUrl: null,
          emptyMessage: null,
          createLabel: null,
          createUrl: null,
          createMenuItems: null,
          createActionInPageHeader: false,
          reorderUrl: null,
          reorderSuccessMessage: null,
          reorderFailMessage: null,
          deleteUrl: null,
          deleteConfirmMessage: null,
          deleteModalUrl: null,
          bulkDeleteUrl: null,
          bulkDeleteConfirmMessage: null,
          bulkActions: [],
          statusActions: [],
          statusFilterOptions: [],
          columnsToggleable: false,
          hiddenColumnsByDefault: [],
          searchable: false,
          searchPlaceholder: null,
          appearance: null,
          ...options,
        },
      },
      ...nodeProps,
    })
  );
  app.mount(host);
  teardown = () => app.unmount();
  return host;
}

it('renders HTML icon descriptions and hides only their column header text', async () => {
  mountNode({
    columns: [
      {key: 'name', label: 'Name'},
      {
        key: 'searchable',
        label: 'Searchable',
        headerSrOnly: true,
      },
    ],
    rows: [
      {
        id: 1,
        name: 'Body',
        searchable: {
          html: '<craft-icon name="magnifying-glass" appearance="badge" label="Used as search keywords"></craft-icon>',
        },
      },
    ],
  });
  await nextTick();

  const header = page.getByRole('columnheader', {name: 'Searchable'});
  await expect.element(header).toBeInTheDocument();
  const label = header.element().querySelector('.sr-only')!;
  expect(label).not.toBeNull();
  expect(label.getBoundingClientRect().width).toBeLessThanOrEqual(1);
  expect(label.getBoundingClientRect().height).toBeLessThanOrEqual(1);
  await expect
    .element(page.getByRole('img', {name: 'Used as search keywords'}))
    .toBeInTheDocument();
});

it.each([false, true])(
  'offers creation in the empty state with page-header placement %s',
  async (createActionInPageHeader) => {
    const host = mountNode({
      columns: [
        'Name',
        'Handle',
        'Mode',
        'Dimensions',
        'Interlace',
        'Format',
      ].map((label) => ({key: label.toLowerCase(), label})),
      rows: [],
      emptyMessage: 'No image transforms exist yet.',
      createLabel: 'New image transform',
      createUrl: '/settings/assets/transforms/new',
      createActionInPageHeader,
    });
    host.style.width = '320px';
    await nextTick();

    const empty =
      host.querySelector<HTMLElementTagNameMap['craft-empty']>('craft-empty')!;
    await empty.updateComplete;
    const link =
      empty.querySelector<HTMLElementTagNameMap['craft-button']>(
        'craft-button[href]'
      )!;
    expect(empty.shadowRoot!.textContent).toContain(
      'No image transforms exist yet.'
    );
    expect(link?.textContent?.trim()).toBe('New image transform');
    expect(link?.getAttribute('href')).toBe('/settings/assets/transforms/new');
    const body = host.querySelector('.admin-table__body')!;
    body.scrollLeft = body.scrollWidth;
    const labelBounds = empty
      .shadowRoot!.querySelector('p')!
      .getBoundingClientRect();
    const bounds = host.getBoundingClientRect();
    expect(labelBounds.left).toBeGreaterThanOrEqual(bounds.left);
    expect(labelBounds.right).toBeLessThanOrEqual(bounds.right);
    await link.updateComplete;
    const anchor = link.shadowRoot!.querySelector('a')!;
    anchor.focus();
    expect(link.shadowRoot!.activeElement).toBe(anchor);
  }
);

async function setControl(host: HTMLElement, selector: string, value: string) {
  const control = host.querySelector<HTMLElement & {modelValue: string}>(
    selector
  )!;
  await (control as typeof control & {updateComplete: Promise<boolean>})
    .updateComplete;
  control.modelValue = value;
  control.dispatchEvent(
    new CustomEvent('model-value-changed', {bubbles: true})
  );
  await nextTick();
}
function rowNames(host: HTMLElement) {
  return Array.from(host.querySelectorAll('tbody tr')).map((row) =>
    row
      .querySelector('td.cp-table-cell:not(.cp-table-cell--select)')
      ?.textContent?.trim()
  );
}
async function moveFirstRowDown(host: HTMLElement) {
  const handle = host.querySelector<
    HTMLElementTagNameMap['craft-reorder-button']
  >('tbody craft-reorder-button')!;
  await handle.updateComplete;
  await handle.shadowRoot!.querySelector('craft-action-menu')!.show();
  Array.from(handle.shadowRoot!.querySelectorAll('craft-action-item'))
    .find((item) => item.textContent?.trim() === 'Move down')!
    .click();
}
function result(
  name: string,
  page = 1
): AdminTablePage<(typeof records)[number]> {
  return {
    rows: [{id: page, name, status: 'enabled', count: 1}],
    pagination: {
      current_page: page,
      last_page: 2,
      per_page: 50,
      total: 100,
      from: (page - 1) * 50 + 1,
      to: page * 50,
      next_page_url: null,
      prev_page_url: null,
    },
  };
}

it('insets a flush table’s controls and footer by the container gutter, and pads rows’ outer cells to match', async () => {
  const {host} = mountTable({appearance: 'flush'});
  host.style.setProperty('--cp-container-padding', '24px');
  await nextTick();

  for (const region of ['header', 'footer']) {
    const element = host.querySelector(`.admin-table__${region}`)!;
    expect(getComputedStyle(element).paddingInlineStart).toBe('24px');
    expect(getComputedStyle(element).paddingInlineEnd).toBe('24px');
  }

  // The table spans the gutter, so its rows' rules reach the container's edges.
  const body = host.querySelector('.admin-table__body')!;
  expect(getComputedStyle(body).paddingInlineStart).toBe('0px');
  expect(getComputedStyle(body).paddingInlineEnd).toBe('0px');

  for (const row of host.querySelectorAll('tr')) {
    const cells = row.querySelectorAll(':scope > th, :scope > td');
    expect(getComputedStyle(cells[0]!).paddingInlineStart).toBe('24px');
    expect(getComputedStyle(cells[cells.length - 1]!).paddingInlineEnd).toBe(
      '24px'
    );
  }
});

it('explains blocked deletion on keyboard focus while keeping the row editable and unselectable', async () => {
  const remove = vi
    .spyOn(actionClient, 'delete')
    .mockResolvedValue({data: {}} as never);
  const host = mountNode({
    deletable: true,
    deleteUrl: '/settings/transformers/delete',
    bulkDeleteUrl: '/settings/transformers/delete-many',
    rows: [
      {
        id: 'craft',
        name: {label: 'Craft (Default)', url: '/settings/transformers/craft'},
        _deletable: false,
        _deleteDisabledReason: 'The Craft Asset Transformer cannot be deleted.',
      },
      {id: 'public', name: 'Public', _deletable: false},
      {id: 'remote', name: 'Remote'},
    ],
  });
  const row = host.querySelector('tbody tr')!;
  const button =
    row.querySelector<HTMLElementTagNameMap['craft-button']>('craft-button')!;
  await button.updateComplete;

  await expect
    .element(page.getByRole('link', {name: 'Craft (Default)'}))
    .toHaveAttribute('href', '/settings/transformers/craft');
  await expect
    .element(page.elementLocator(button))
    .toHaveAccessibleDescription(
      'The Craft Asset Transformer cannot be deleted.'
    );
  expect(button.getAttribute('aria-disabled')).toBe('true');
  expect(
    host.querySelectorAll('tbody tr')[1]!.querySelector('craft-button')
  ).toBeNull();
  button.click();
  row.dispatchEvent(new KeyboardEvent('keydown', {key: ' ', bubbles: true}));
  await nextTick();
  expect(remove).not.toHaveBeenCalled();
  expect(host.querySelector('.bulk-actions-bar__count')).toBeNull();

  for (let step = 0; step < 10 && document.activeElement !== button; step++) {
    await userEvent.tab();
  }
  expect(document.activeElement).toBe(button);
  const tooltip =
    row.querySelector<HTMLElementTagNameMap['craft-tooltip']>('craft-tooltip')!;
  await vi.waitFor(() => expect(tooltip.opened).toBe(true));
  await userEvent.keyboard('{Escape}');
  await vi.waitFor(() => expect(tooltip.opened).toBe(false));
  expect(document.activeElement).toBe(button);
});

it.each([
  {
    error: {
      response: {data: {message: 'This workflow is assigned to a section.'}},
    },
    message: 'This workflow is assigned to a section.',
  },
  {
    error: new Error('Network unavailable'),
    message: 'A server error occurred.',
  },
])(
  'keeps a row after failed deletion, reports $message, and permits a retry',
  async ({error, message}) => {
    const displayError = vi.fn();
    vi.stubGlobal('Craft', {cp: {displayError}});
    vi.spyOn(window, 'confirm').mockReturnValue(true);
    let rejectDelete!: (error: unknown) => void;
    const request = vi
      .spyOn(actionClient, 'delete')
      .mockImplementationOnce(
        () =>
          new Promise((_resolve, reject) => {
            rejectDelete = reject;
          })
      )
      .mockResolvedValue({data: {}} as never);
    const host = mountNode({deleteUrl: '/settings/delete'});

    const button =
      host.querySelector<HTMLElementTagNameMap['craft-button']>(
        'tbody craft-button'
      )!;
    await button.updateComplete;
    button.focus();
    button.click();
    await nextTick();
    expect(button.getAttribute('aria-disabled')).toBe('true');
    expect(document.activeElement).toBe(button);
    button.click();
    expect(request).toHaveBeenCalledOnce();
    rejectDelete(error);
    await vi.waitFor(() => expect(displayError).toHaveBeenCalledWith(message));
    expect(rowNames(host)).toEqual(['Beta', 'Alpha']);
    expect(reload).not.toHaveBeenCalled();

    host.querySelector<HTMLElement>('tbody craft-button')!.click();
    await vi.waitFor(() => expect(rowNames(host)).toEqual(['Alpha']));
    expect(reload).toHaveBeenCalledOnce();
  }
);

it('deletes through a row-specific resource route without offering bulk deletion', async () => {
  const confirmDelete = vi
    .spyOn(window, 'confirm')
    .mockReturnValueOnce(false)
    .mockReturnValue(true);
  const post = vi
    .spyOn(actionClient, 'post')
    .mockResolvedValue({data: {}} as never);
  const remove = vi
    .spyOn(actionClient, 'delete')
    .mockResolvedValue({data: {}} as never);
  const host = mountNode({
    deletable: true,
    deleteConfirmMessage: 'Delete this row?',
    rows: [
      {
        id: 7,
        name: 'Group',
        _deleteUrl: '/settings/groups/7',
        _deleteConfirmMessage: 'Are you sure you want to delete "Group"?',
      },
    ],
  });

  expect(host.querySelector('tbody craft-checkbox')).toBeNull();
  host.querySelector<HTMLElement>('tbody craft-button')!.click();
  expect(confirmDelete).toHaveBeenCalledWith(
    'Are you sure you want to delete "Group"?'
  );
  expect(remove).not.toHaveBeenCalled();
  expect(rowNames(host)).toEqual(['Group']);

  host.querySelector<HTMLElement>('tbody craft-button')!.click();
  await vi.waitFor(() => expect(rowNames(host)).not.toContain('Group'));
  expect(remove).toHaveBeenCalledWith('/settings/groups/7', {data: {id: 7}});
  expect(post).not.toHaveBeenCalled();
});

it('rolls back a refused static reorder and saves array IDs after a retry', async () => {
  const displayError = vi.fn();
  vi.stubGlobal('Craft', {cp: {displayError}});
  let rejectReorder!: (error: unknown) => void;
  const post = vi
    .spyOn(actionClient, 'post')
    .mockImplementationOnce(
      () =>
        new Promise((_resolve, reject) => {
          rejectReorder = reject;
        })
    )
    .mockResolvedValue({data: {}} as never);
  const host = mountNode({reorderUrl: '/settings/reorder'});
  await moveFirstRowDown(host);

  await vi.waitFor(() =>
    expect(post).toHaveBeenCalledWith('/settings/reorder', {ids: [2, 1]})
  );
  expect(rowNames(host)).toEqual(['Alpha', 'Beta']);
  expect(reload).not.toHaveBeenCalled();

  rejectReorder(new Error('Failed to save'));
  await vi.waitFor(() => expect(rowNames(host)).toEqual(['Beta', 'Alpha']));
  await vi.waitFor(() =>
    expect(displayError).toHaveBeenCalledWith('Couldn’t reorder.')
  );
  expect(reload).not.toHaveBeenCalled();
  await moveFirstRowDown(host);

  await vi.waitFor(() => expect(reload).toHaveBeenCalledOnce());
  expect(rowNames(host)).toEqual(['Alpha', 'Beta']);
  expect(post).toHaveBeenCalledTimes(2);
  expect(post).toHaveBeenLastCalledWith('/settings/reorder', {ids: [2, 1]});
});

it('retains bulk selection when deletion is refused and removes the rows after a retry', async () => {
  const displayError = vi.fn();
  vi.stubGlobal('Craft', {cp: {displayError}});
  vi.spyOn(window, 'confirm').mockReturnValue(true);
  const request = vi
    .spyOn(actionClient, 'delete')
    .mockRejectedValueOnce({response: {data: {message: 'Deletion refused.'}}})
    .mockResolvedValue({data: {}} as never);
  const host = mountNode({bulkDeleteUrl: '/settings/delete-many'});

  expect(host.querySelector('tbody craft-button')).toBeNull();
  expect(host.querySelectorAll('tbody craft-checkbox')).toHaveLength(2);

  for (const row of host.querySelectorAll('tbody tr')) {
    row.dispatchEvent(new KeyboardEvent('keydown', {key: ' ', bubbles: true}));
  }
  await nextTick();

  const menu = host.querySelector('craft-action-menu')!;
  await menu.show();
  const remove = Array.from(
    host.querySelectorAll<HTMLElement>('craft-action-item')
  ).find((item) => item.textContent?.trim() === 'Delete')!;
  remove.click();
  await vi.waitFor(() =>
    expect(displayError).toHaveBeenCalledWith('Deletion refused.')
  );

  expect(rowNames(host)).toEqual(['Beta', 'Alpha']);
  expect(host.querySelector('.bulk-actions-bar__count')?.textContent).toContain(
    '2 selected'
  );
  expect(reload).not.toHaveBeenCalled();

  await menu.show();
  remove.click();
  await vi.waitFor(() =>
    expect(host.querySelector('tbody craft-checkbox')).toBeNull()
  );
  expect(request).toHaveBeenLastCalledWith('/settings/delete-many', {
    data: {ids: [1, 2]},
  });
  expect(host.querySelector('.bulk-actions-bar__count')).toBeNull();
});

it('uses independent endpoints and confirmations for row and bulk deletion', async () => {
  const confirmDelete = vi.spyOn(window, 'confirm').mockReturnValue(true);
  const request = vi
    .spyOn(actionClient, 'delete')
    .mockResolvedValue({data: {}} as never);
  const host = mountNode({
    deletable: true,
    deleteUrl: '/settings/delete-one',
    deleteConfirmMessage: 'Delete this record?',
    bulkDeleteUrl: '/settings/delete-many',
    bulkDeleteConfirmMessage: 'Delete these records?',
    rows: [...records, {id: 3, name: 'Gamma'}],
  });

  host.querySelector<HTMLElement>('tbody craft-button')!.click();
  await vi.waitFor(() => expect(rowNames(host)).toEqual(['Alpha', 'Gamma']));
  expect(request).toHaveBeenCalledWith('/settings/delete-one', {data: {id: 1}});
  expect(confirmDelete).toHaveBeenLastCalledWith('Delete this record?');
  expect(host.querySelectorAll('tbody craft-checkbox')).toHaveLength(2);

  for (const row of host.querySelectorAll('tbody tr')) {
    row.dispatchEvent(new KeyboardEvent('keydown', {key: ' ', bubbles: true}));
  }
  await nextTick();

  const menu = host.querySelector('craft-action-menu')!;
  await menu.show();
  Array.from(host.querySelectorAll<HTMLElement>('craft-action-item'))
    .find((item) => item.textContent?.trim() === 'Delete')!
    .click();
  await vi.waitFor(() =>
    expect(host.querySelector('craft-empty')).not.toBeNull()
  );

  expect(request).toHaveBeenLastCalledWith('/settings/delete-many', {
    data: {ids: [2, 3]},
  });
  expect(confirmDelete).toHaveBeenLastCalledWith('Delete these records?');
});

it('filters rows by status and search, clears selection, and restores manual ordering when filters are cleared', async () => {
  const {host, handle} = mountTable({reorderable: true});
  expect(host.querySelectorAll('tbody craft-reorder-button')).toHaveLength(2);
  host
    .querySelector('tbody tr')!
    .dispatchEvent(new KeyboardEvent('keydown', {key: ' ', bubbles: true}));
  await nextTick();
  expect(handle.value?.selectedIds).toEqual([1]);

  await setControl(host, 'craft-select-rich', 'enabled');
  await vi.waitFor(() => expect(rowNames(host)).toEqual(['Beta']));
  expect(handle.value?.selectedIds).toEqual([]);
  expect(host.querySelector('tbody craft-reorder-button')).toBeNull();

  await setControl(host, 'craft-input[name="search"]', 'alpha');
  await vi.waitFor(() =>
    expect(
      host.querySelector<HTMLElement & {label: string}>('craft-empty')?.label
    ).toBe('No results for “alpha”.')
  );

  await setControl(host, 'craft-select-rich', '');
  await vi.waitFor(() => expect(rowNames(host)).toEqual(['Alpha']));
  expect(host.querySelector('tbody craft-reorder-button')).toBeNull();

  await setControl(host, 'craft-input[name="search"]', '');
  expect(rowNames(host)).toEqual(['Beta', 'Alpha']);
  expect(host.querySelectorAll('tbody craft-reorder-button')).toHaveLength(2);
});

it('restores existing status, sorting, and column preferences while keeping the first column visible', async () => {
  const prefix = 'Craft-admin-table-test.settings';
  localStorage.setItem(`${prefix}.status`, 'enabled');
  localStorage.setItem(`${prefix}.sort`, '[{"id":"name","desc":true}]');
  localStorage.setItem(
    `${prefix}.columns`,
    '{"visible":[],"hidden":["count"]}'
  );
  const {host} = mountTable({storageKey: 'settings', reorderable: true});
  await nextTick();

  expect(rowNames(host)).toEqual(['Beta']);
  expect(host.querySelectorAll('thead th')).toHaveLength(2);
  await setControl(host, 'craft-select-rich', '');
  expect(rowNames(host)).toEqual(['Beta', 'Alpha']);
  expect(localStorage.getItem(`${prefix}.status`)).toBe('');
  expect(host.querySelector('tbody craft-reorder-button')).toBeNull();

  host.querySelector<HTMLButtonElement>('thead th button')!.click();
  await nextTick();
  expect(host.querySelectorAll('tbody craft-reorder-button')).toHaveLength(2);
});

it('loads the requested page and returns to page one when the page size changes', async () => {
  const loadRows = vi.fn(async (request: AdminTableRequest) =>
    result(`Page ${request.page}`, request.page)
  );
  const {host} = mountTable({loadRows, pageSize: 50});
  await vi.waitFor(() => expect(rowNames(host)).toEqual(['Page 1']));
  Array.from(host.querySelectorAll('craft-icon'))
    .find(
      (icon) => (icon as HTMLElement & {name: string}).name === 'chevron-right'
    )!
    .closest('craft-button')!
    .click();
  await vi.waitFor(() => expect(rowNames(host)).toEqual(['Page 2']));

  const pageSize = host.querySelector<HTMLSelectElement>(
    '.admin-table__footer select'
  )!;
  pageSize.value = '100';
  pageSize.dispatchEvent(new Event('change', {bubbles: true}));
  await vi.waitFor(() =>
    expect(loadRows.mock.lastCall?.[0]).toMatchObject({page: 1, perPage: 100})
  );
  await vi.waitFor(() => expect(rowNames(host)).toEqual(['Page 1']));
});

it('renders the initial server page and updates it through Inertia without echoing restored state', async () => {
  vi.stubGlobal('Craft', {systemUid: 'admin-table-test', pageTrigger: 'p'});
  const originalPageUrl =
    '/settings/entry-types?site=secondary&p=2&per_page=100&search=Article&sort[0][field]=name&sort[0][direction]=desc';
  inertiaPage.url = originalPageUrl;
  const originalPagination = {
    ...result('Loaded', 2).pagination,
    per_page: 100,
    total: 101,
    from: 101,
    to: 101,
  };
  const options = reactive<Partial<TableProps>>({
    rows: [{id: 2, name: 'Loaded'}],
    pagination: originalPagination,
    searchable: true,
  });
  const host = mountNode(options);
  await nextTick();

  expect(rowNames(host)).toEqual(['Loaded']);
  expect(get).not.toHaveBeenCalled();
  expect(
    host.querySelector<HTMLElement & {modelValue: string}>(
      'craft-input[name=search]'
    )?.modelValue
  ).toBe('Article');
  get.mockImplementationOnce((_url, _query, visit) => {
    options.rows = [{id: 1, name: 'Filtered'}];
    options.pagination = {
      ...result('Filtered').pagination,
      per_page: 100,
      total: 1,
      last_page: 1,
      to: 1,
    };
    inertiaPage.url =
      '/settings/entry-types?site=secondary&p=1&per_page=100&search=Filtered&sort[0][field]=name&sort[0][direction]=desc';
    void visit.onSuccess().then(() => visit.onFinish());
  });
  await setControl(host, 'craft-input[name=search]', 'Filtered');
  await vi.waitFor(() => expect(rowNames(host)).toEqual(['Filtered']));

  expect(get).toHaveBeenCalledExactlyOnceWith(
    '/settings/entry-types',
    {
      site: 'secondary',
      p: 1,
      per_page: 100,
      search: 'Filtered',
      sort: expect.anything(),
    },
    expect.objectContaining({
      only: ['ui'],
      preserveState: true,
      preserveScroll: true,
    })
  );

  const [serializedUrl] = mergeDataIntoQueryString(
    'get',
    '/settings/entry-types',
    get.mock.calls[0]![1],
    'brackets'
  );
  const params = new URL(serializedUrl, location.origin).searchParams;
  expect(params.get('sort[0][field]')).toBe('name');
  expect(params.get('sort[0][direction]')).toBe('desc');

  options.rows = [{id: 2, name: 'Loaded'}];
  options.pagination = originalPagination;
  inertiaPage.url = originalPageUrl;
  await nextTick();
  expect(rowNames(host)).toEqual(['Loaded']);
  expect(
    host.querySelector<HTMLElement & {modelValue: string}>(
      'craft-input[name=search]'
    )?.modelValue
  ).toBe('Article');
  await new Promise((resolve) => setTimeout(resolve, 350));
  expect(get).toHaveBeenCalledTimes(1);
});

it('ignores superseded responses and aborts an unfinished load on unmount', async () => {
  const pending: Array<{
    signal: AbortSignal;
    resolve: (value: AdminTablePage<(typeof records)[number]>) => void;
  }> = [];
  const loadRows = (_request: AdminTableRequest, signal: AbortSignal) =>
    new Promise<AdminTablePage<(typeof records)[number]>>((resolve) =>
      pending.push({signal, resolve})
    );
  const {host, handle} = mountTable({loadRows});
  const refresh = handle.value!.refresh();
  expect(pending[0]!.signal.aborted).toBe(true);
  pending[1]!.resolve(result('Latest'));
  await refresh;
  pending[0]!.resolve(result('Stale'));
  await nextTick();
  expect(rowNames(host)).toEqual(['Latest']);

  void handle.value!.refresh();
  teardown?.();
  teardown = undefined;
  expect(pending[2]!.signal.aborted).toBe(true);
  pending[2]!.resolve(result('Unmounted'));
});

it('refreshes through Inertia after deletion and returns from an emptied final page', async () => {
  inertiaPage.url = '/settings/entry-types?page=2';
  const remove = vi
    .spyOn(actionClient, 'delete')
    .mockResolvedValue({data: {}} as never);
  vi.spyOn(window, 'confirm').mockReturnValue(true);
  const options = reactive<Partial<TableProps>>({
    rows: [{id: 51, name: 'Last', _deleteUrl: '/settings/entry-types/51'}],
    pagination: {...result('Last', 2).pagination, total: 51, from: 51, to: 51},
    deletable: true,
  });
  const host = mountNode(options);
  get.mockImplementation((_url, query, visit) => {
    options.rows =
      query.page === 2
        ? []
        : [{id: 1, name: 'Replacement', _deleteUrl: '/settings/entry-types/1'}];
    options.pagination = {
      ...result('Unused', query.page).pagination,
      last_page: 1,
      total: 50,
      from: query.page === 2 ? null : 1,
      to: query.page === 2 ? null : 50,
    };
    inertiaPage.url = `/settings/entry-types?page=${query.page}`;
    void visit.onSuccess().then(() => visit.onFinish());
  });

  host.querySelector<HTMLElement>('tbody craft-button')!.click();
  await vi.waitFor(() => expect(rowNames(host)).toEqual(['Replacement']));

  expect(remove).toHaveBeenCalledExactlyOnceWith('/settings/entry-types/51', {
    data: {id: 51},
  });
  expect(get.mock.calls.map((call) => call[1].page)).toEqual([2, 1]);
  expect(reload).not.toHaveBeenCalled();
});

it('keeps a newer search while an earlier Inertia response updates the node', async () => {
  inertiaPage.url = '/settings/entry-types';
  const options = reactive({
    rows: [{id: 1, name: 'Initial'}],
    pagination: result('Initial').pagination,
    searchable: true,
  });
  const host = mountNode(options);
  await nextTick();
  const visits: Array<{onSuccess: () => Promise<void>; onFinish: () => void}> =
    [];
  get.mockImplementation((_url, _query, visit) => visits.push(visit));

  await setControl(host, 'craft-input[name=search]', 'First');
  await vi.waitFor(() => expect(get).toHaveBeenCalledTimes(1));
  await setControl(host, 'craft-input[name=search]', 'Latest');
  options.rows = [{id: 1, name: 'First response'}];
  inertiaPage.url = '/settings/entry-types?search=First';
  await visits[0]!.onSuccess();
  visits[0]!.onFinish();

  expect(
    host.querySelector<HTMLElement & {modelValue: string}>(
      'craft-input[name=search]'
    )?.modelValue
  ).toBe('Latest');
  await vi.waitFor(() => expect(get.mock.lastCall?.[1].search).toBe('Latest'));

  options.rows = [{id: 2, name: 'Latest response'}];
  inertiaPage.url = '/settings/entry-types?search=Latest';
  await visits[1]!.onSuccess();
  visits[1]!.onFinish();

  await vi.waitFor(() => expect(rowNames(host)).toEqual(['Latest response']));
  expect(
    host.querySelector<HTMLElement & {modelValue: string}>(
      'craft-input[name=search]'
    )?.modelValue
  ).toBe('Latest');
});

it('adapts node endpoint requests and renders serialized cells in the shared table', async () => {
  const post = vi.spyOn(actionClient, 'post').mockResolvedValue({
    data: {
      data: [
        {
          id: 1,
          name: {label: 'Settings link', url: '/settings/item'},
          count: {html: '<strong>42</strong>'},
          lastUsed: {date: new Date(2026, 2, 14, 12).toISOString()},
          expiryDate: null,
          _status: {value: 'disabled', fill: 'gray', label: 'Disabled'},
        },
      ],
      pagination: result('Unused').pagination,
    },
  } as never);
  const host = mountNode({
    columns: [
      {key: 'name', label: 'Name', sortable: true},
      {key: 'count', label: 'Count'},
      {key: 'lastUsed', label: 'Last Used'},
      {key: 'expiryDate', label: 'Expires'},
    ],
    rows: [],
    dataUrl: '/settings/table-data',
    bulkActions: [{label: 'Archive', url: '/archive'}],
    columnsToggleable: true,
    searchable: true,
  });
  await vi.waitFor(() =>
    expect(host.querySelector('tbody a')?.textContent).toBe('Settings link')
  );
  expect(host.querySelector('tbody a')?.getAttribute('href')).toBe(
    '/settings/item'
  );
  expect(host.querySelector('tbody strong')?.textContent).toBe('42');
  const status = page.getByRole('img', {name: 'Disabled', exact: true});
  await expect.element(status).toBeVisible();
  expect(getComputedStyle(status.element()).backgroundColor).toMatch(
    /^rgba\(.*?,\s*0\)$/
  );
  const headers = Array.from(host.querySelectorAll('thead th'));
  const cells = host.querySelector('tbody tr')!.querySelectorAll('td');
  function cellText(label: string) {
    const index = headers.findIndex(
      (header) => header.textContent?.trim() === label
    );
    return cells[index]?.textContent?.trim();
  }
  expect(cellText('Last Used')).toBe('March 14, 2026');
  expect(cellText('Expires')).toBe('');
  expect(host.querySelector('tbody craft-checkbox label')?.textContent).toBe(
    'Select Settings link'
  );
  await setControl(host, 'craft-input[name="search"]', 'Settings');
  await vi.waitFor(() =>
    expect(post).toHaveBeenLastCalledWith(
      '/settings/table-data',
      {
        page: 1,
        per_page: 50,
        search: 'Settings',
        status: undefined,
        sort: undefined,
      },
      {signal: expect.any(AbortSignal)}
    )
  );
});

it('moves the selected row to another page, reloads rows, and clears selection', async () => {
  let moved = false;
  const moveToPage = vi.fn(async () => {
    moved = true;
  });
  const {host, handle} = mountTable({
    loadRows: async () => result(moved ? 'Replacement' : 'Original'),
    moveToPage,
  });
  await vi.waitFor(() => expect(rowNames(host)).toEqual(['Original']));
  host
    .querySelector('tbody tr')!
    .dispatchEvent(new KeyboardEvent('keydown', {key: ' ', bubbles: true}));
  await nextTick();
  const invoker = await vi.waitFor(() => {
    const element = host.querySelector<HTMLButtonElement>(
      '.move-to-page-invoker'
    );
    expect(element).not.toBeNull();
    return element!;
  });
  await (
    host.querySelector(
      '.admin-table__footer craft-action-menu'
    ) as HTMLElement & {show: () => Promise<void>}
  ).show();
  await (
    invoker.closest('craft-popover') as HTMLElement & {
      show: () => Promise<void>;
    }
  ).show();
  const select = invoker
    .closest('craft-popover')!
    .querySelector<HTMLSelectElement>('select')!;
  select.value = '2';
  select.dispatchEvent(new Event('change', {bubbles: true}));
  await nextTick();
  Array.from(host.querySelectorAll<HTMLElement>('craft-popover craft-button'))
    .find((button) => button.textContent?.trim() === 'Move')!
    .click();
  await vi.waitFor(() => expect(rowNames(host)).toEqual(['Replacement']));
  expect(moveToPage).toHaveBeenCalledWith(1, 2, 50);
  expect(handle.value?.selectedIds).toEqual([]);
});

async function expectDebouncedDraftRefresh(
  onSaved: (result: {draft?: boolean}) => void,
  refresh: ReturnType<typeof vi.fn>
): Promise<void> {
  vi.useFakeTimers({toFake: ['setTimeout', 'clearTimeout']});
  try {
    onSaved({draft: true});
    expect(refresh).not.toHaveBeenCalled();
    await vi.advanceTimersByTimeAsync(400);
    onSaved({draft: true});
    await vi.advanceTimersByTimeAsync(400);
    expect(refresh).not.toHaveBeenCalled();
    await vi.advanceTimersByTimeAsync(200);
    expect(refresh).toHaveBeenCalledTimes(1);

    onSaved({draft: true});
    onSaved({});
    expect(refresh).toHaveBeenCalledTimes(2);
    await vi.advanceTimersByTimeAsync(600);
    expect(refresh).toHaveBeenCalledTimes(2);

    onSaved({draft: true});
    teardown?.();
    teardown = undefined;
    await vi.advanceTimersByTimeAsync(600);
    expect(refresh).toHaveBeenCalledTimes(2);
  } finally {
    vi.useRealTimers();
  }
}

it('opens slideout links at their width and refreshes through its own handler', async () => {
  const onRefresh = vi.fn();
  const navigate = vi
    .spyOn(inertiaRouter, 'visit')
    .mockImplementation(() => {});
  const host = mountNode(
    {
      rows: [
        {
          id: 1,
          name: {
            label: 'Summer sale',
            url: '/admin/rules/1',
            slideout: true,
            slideoutWidth: '85vw',
          },
        },
        {
          id: 2,
          name: {
            label: 'Winter sale',
            url: '/admin/rules/2',
            slideoutWidth: '85vw',
          },
        },
      ],
    },
    {onRefresh}
  );

  await userEvent.click(page.getByRole('link', {name: 'Winter sale'}));
  expect(navigate).toHaveBeenCalledWith('/admin/rules/2', expect.anything());
  expect(slideout).not.toHaveBeenCalled();
  navigate.mockClear();

  const link = await vi.waitFor(() => {
    const element = host.querySelector<HTMLAnchorElement>(
      'a[href="/admin/rules/1"]'
    );
    expect(element).not.toBeNull();
    return element!;
  });
  await userEvent.click(page.getByRole('link', {name: 'Summer sale'}));
  expect(navigate).not.toHaveBeenCalled();

  expect(slideout).toHaveBeenCalledWith(
    '/admin/rules/1',
    expect.objectContaining({opener: link, width: '85vw'})
  );
  await expectDebouncedDraftRefresh(
    slideout.mock.lastCall![1].onSaved,
    onRefresh
  );
  expect(reload).not.toHaveBeenCalled();
});

it('lets the owner refresh a paginated table after a slideout saves', async () => {
  const options = reactive<Partial<TableProps>>({
    rows: [
      {id: 1, name: {label: 'Original', url: '/admin/rules/1', slideout: true}},
    ],
    pagination: result('Unused').pagination,
  });
  const host = mountNode(options, {
    onRefresh: () => {
      options.rows = [{id: 1, name: 'Updated by owner'}];
    },
  });

  host.querySelector<HTMLAnchorElement>('a[href="/admin/rules/1"]')!.click();
  slideout.mock.lastCall![1].onSaved({});

  await vi.waitFor(() => expect(rowNames(host)).toEqual(['Updated by owner']));
  expect(get).not.toHaveBeenCalled();
  expect(reload).not.toHaveBeenCalled();
});

it('opens menu links in slideouts without losing native modified-click behavior', async () => {
  const onRefresh = vi.fn();
  const navigate = vi
    .spyOn(inertiaRouter, 'visit')
    .mockImplementation(() => {});
  const host = mountNode(
    {
      rows: [
        {
          id: 1,
          name: {
            label: 'Actions',
            items: [
              {
                label: 'Edit rule',
                url: '/admin/rules/1',
                slideout: true,
                slideoutWidth: '72vw',
              },
              {label: 'View rule', url: '/admin/rules/1/view'},
            ],
          },
        },
      ],
    },
    {onRefresh}
  );
  const menu = host.querySelector('craft-action-menu')!;
  await menu.show();
  const item = await vi.waitFor(() => {
    const element = menu.querySelector('craft-action-item');
    expect(element).toBeTruthy();
    return element!;
  });
  await item.updateComplete;
  const anchor = item.shadowRoot!.querySelector('a')!;
  expect(anchor.getAttribute('href')).toBe('/admin/rules/1');

  for (const modifier of ['altKey', 'ctrlKey', 'metaKey', 'shiftKey']) {
    const click = new MouseEvent('click', {
      bubbles: true,
      cancelable: true,
      [modifier]: true,
    });
    item.dispatchEvent(click);
    expect(click.defaultPrevented).toBe(false);
  }
  expect(slideout).not.toHaveBeenCalled();
  expect(navigate).not.toHaveBeenCalled();

  await menu.show();
  item.focus();
  await userEvent.keyboard('{Enter}');
  expect(slideout).toHaveBeenCalledWith(
    '/admin/rules/1',
    expect.objectContaining({
      opener: menu.querySelector('[slot="invoker"]'),
      width: '72vw',
    })
  );
  slideout.mock.lastCall![1].onSaved();
  expect(onRefresh).toHaveBeenCalledOnce();
  expect(reload).not.toHaveBeenCalled();

  await menu.show();
  await userEvent.click(page.getByRole('link', {name: 'View rule'}));
  expect(navigate).toHaveBeenCalledWith(
    '/admin/rules/1/view',
    expect.anything()
  );
});

it('opens the create action in a slideout when asked to', async () => {
  const host = mountNode({
    createLabel: 'New rule',
    createUrl: '/admin/rules/new',
    createSlideout: true,
    createSlideoutWidth: '85vw',
  });

  const button = await vi.waitFor(() => {
    const element = host.querySelector<HTMLElement>(
      'craft-button[href="/admin/rules/new"]'
    );
    expect(element).not.toBeNull();
    return element!;
  });
  const click = new MouseEvent('click', {bubbles: true, cancelable: true});
  button.dispatchEvent(click);

  expect(click.defaultPrevented).toBe(true);
  expect(slideout).toHaveBeenCalledWith(
    '/admin/rules/new',
    expect.objectContaining({opener: button, width: '85vw'})
  );
  await expectDebouncedDraftRefresh(slideout.mock.lastCall![1].onSaved, reload);
  expect(reload).toHaveBeenCalledWith({only: ['ui']});
});

async function doubleClickRow(
  host: HTMLElement,
  index: number
): Promise<Element> {
  const row = await vi.waitFor(() => {
    const element = host.querySelectorAll('tbody tr')[index];
    expect(element?.lastElementChild).toBeTruthy();
    return element!;
  });
  await userEvent.dblClick(row.lastElementChild!);
  return row;
}

it('opens a double-clicked row’s slideout link, returning focus to the link', async () => {
  const {openSlideout, closeAllSlideouts, setSlideoutPageLoader} =
    await import('@/common/slideouts/store');
  const {default: SlideoutHost} =
    await import('@/common/slideouts/SlideoutHost.vue');
  setSlideoutPageLoader(async () => ({
    component: defineComponent({
      render: () => h('input', {'aria-label': 'Editor'}),
    }),
    props: {},
    url: '/admin/rules/1',
  }));
  slideout.mockImplementationOnce(openSlideout);
  const host = mountNode({
    columns: [
      {key: 'name', label: 'Name'},
      {key: 'handle', label: 'Handle'},
    ],
    rows: [
      {
        id: 1,
        name: {label: 'Summer sale', url: '/admin/rules/1', slideout: true},
        handle: 'summerSale',
      },
    ],
  });
  const unmountTable = teardown;
  const root = document.createElement('div');
  document.body.append(root);
  const app = createApp(() => h(SlideoutHost, {assetVersion: ''}));
  app.mount(root);
  teardown = () => {
    closeAllSlideouts();
    app.unmount();
    unmountTable?.();
    setSlideoutPageLoader();
  };
  const row = await doubleClickRow(host, 0);
  const link = row.querySelector('a[href="/admin/rules/1"]');
  expect(link).toBeInstanceOf(HTMLAnchorElement);
  await expect
    .poll(() =>
      document
        .querySelector('.slideout-panel')
        ?.contains(document.activeElement)
    )
    .toBe(true);
  expect(document.querySelectorAll('.slideout-panel')).toHaveLength(1);
  closeAllSlideouts();
  await expect.poll(() => document.activeElement === link).toBe(true);
});

it('ignores double-clicks on rows without a slideout link', async () => {
  const host = mountNode({
    columns: [
      {key: 'name', label: 'Name'},
      {key: 'handle', label: 'Handle'},
    ],
    rows: [
      {
        id: 1,
        name: {label: 'Summer sale', url: '/admin/rules/1'},
        handle: 'summerSale',
      },
      {
        id: 2,
        name: {html: '<a href="/admin/rules/2">Winter sale</a>'},
        handle: 'winterSale',
      },
      {id: 3, name: 'Spring sale', handle: 'springSale'},
    ],
  });
  await doubleClickRow(host, 0);
  await doubleClickRow(host, 1);
  await doubleClickRow(host, 2);
  expect(slideout).not.toHaveBeenCalled();
  expect(visit).not.toHaveBeenCalled();
});

it.each([
  '<button type="button"><span>Own action</span></button>',
  '<a href="#own-action"><span>Own link</span></a>',
  '<input type="text" value="Editable" aria-label="Description">',
  '<label><input type="checkbox"><span>Enable rule</span></label>',
])('leaves double-clicks on a row’s controls to them: %s', async (html) => {
  const host = mountNode({
    columns: [
      {key: 'name', label: 'Name'},
      {key: 'detail', label: 'Detail'},
    ],
    rows: [
      {
        id: 1,
        name: {label: 'Editor', url: '/admin/rules/1', slideout: true},
        detail: {html},
      },
    ],
  });
  const target = host
    .querySelector('tbody td:last-child')!
    .querySelector('span, input')!;
  target.addEventListener('click', (event) => event.preventDefault());
  await userEvent.dblClick(target);
  expect(slideout).not.toHaveBeenCalled();
});

it('preserves width and owner refresh handling for a double-clicked editor', async () => {
  const onRefresh = vi.fn();
  const host = mountNode(
    {
      columns: [
        {key: 'name', label: 'Name'},
        {key: 'detail', label: 'Detail'},
      ],
      rows: [
        {
          id: 1,
          name: {
            label: 'Editor',
            url: '/admin/rules/1',
            slideout: true,
            slideoutWidth: '72vw',
          },
          detail: 'Text',
        },
      ],
    },
    {onRefresh}
  );
  await doubleClickRow(host, 0);
  expect(slideout).toHaveBeenCalledWith(
    '/admin/rules/1',
    expect.objectContaining({width: '72vw'})
  );
  await expectDebouncedDraftRefresh(
    slideout.mock.lastCall![1].onSaved,
    onRefresh
  );
  expect(reload).not.toHaveBeenCalled();
});
