import {createApp, effectScope, h, nextTick, ref} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import AdminTable from './AdminTable.vue';
import AdminTableNode from '@/modules/ui/AdminTableNode.vue';
import {actionClient} from '@craftcms/ui';
import type {TableProps} from '@/modules/ui/table-types';
import {useAdminTable} from '../useAdminTable';
import type {
  AdminTableHandle,
  AdminTablePage,
  AdminTableRequest,
} from '../types';

const reload = vi.hoisted(() => vi.fn());

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => ({props: {readOnly: false}}),
  router: {reload},
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

function mountNode(options: Partial<TableProps> = {}) {
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
          bordered: false,
          ...options,
        },
      },
    })
  );
  app.mount(host);
  teardown = () => app.unmount();
  return host;
}

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

it('insets padded table controls, rows, and footer by the container gutter', async () => {
  const {host} = mountTable({class: 'admin-table--padded'});
  host.style.setProperty('--cp-container-padding', '24px');
  await nextTick();

  for (const region of ['header', 'body', 'footer']) {
    const element = host.querySelector(`.admin-table__${region}`)!;
    expect(getComputedStyle(element).paddingInlineStart).toBe('24px');
    expect(getComputedStyle(element).paddingInlineEnd).toBe('24px');
  }
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

it('saves static ordering as an array of row IDs', async () => {
  const post = vi
    .spyOn(actionClient, 'post')
    .mockResolvedValue({data: {}} as never);
  const host = mountNode({reorderUrl: '/settings/reorder'});
  const handle = host.querySelector('craft-reorder-button')!;
  await handle.updateComplete;
  handle
    .shadowRoot!.querySelector<HTMLElement>('[data-action="moveDown"]')!
    .click();

  await vi.waitFor(() =>
    expect(post).toHaveBeenCalledWith('/settings/reorder', {ids: [2, 1]})
  );
  expect(rowNames(host)).toEqual(['Alpha', 'Beta']);
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

it('filters rows by status and search and clears selection when the view changes', async () => {
  const {host, handle} = mountTable();
  host
    .querySelector('tbody tr')!
    .dispatchEvent(new KeyboardEvent('keydown', {key: ' ', bubbles: true}));
  await nextTick();
  expect(handle.value?.selectedIds).toEqual([1]);

  await setControl(host, 'craft-select-rich', 'enabled');
  await vi.waitFor(() => expect(rowNames(host)).toEqual(['Beta']));
  expect(handle.value?.selectedIds).toEqual([]);

  await setControl(host, 'craft-input[name="search"]', 'alpha');
  await vi.waitFor(() =>
    expect(
      host.querySelector<HTMLElement & {label: string}>('craft-empty')?.label
    ).toBe('No results for “alpha”.')
  );

  await setControl(host, 'craft-select-rich', '');
  await vi.waitFor(() => expect(rowNames(host)).toEqual(['Alpha']));
});

it('restores existing status, sorting, and column preferences while keeping the first column visible', async () => {
  const prefix = 'Craft-admin-table-test.settings';
  localStorage.setItem(`${prefix}.status`, 'enabled');
  localStorage.setItem(`${prefix}.sort`, '[{"id":"name","desc":true}]');
  localStorage.setItem(
    `${prefix}.columns`,
    '{"visible":[],"hidden":["count"]}'
  );
  const {host} = mountTable({storageKey: 'settings'});
  await nextTick();

  expect(rowNames(host)).toEqual(['Beta']);
  expect(host.querySelectorAll('thead th')).toHaveLength(2);
  await setControl(host, 'craft-select-rich', '');
  expect(rowNames(host)).toEqual(['Beta', 'Alpha']);
  expect(localStorage.getItem(`${prefix}.status`)).toBe('');
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

it('rolls back a failed reorder and prevents reorder while sorting or filtering', async () => {
  const scope = effectScope();
  teardown = () => scope.stop();
  const state = scope.run(() =>
    useAdminTable(
      {
        rows: records,
        columns,
        pageSize: 100,
        pageSizeOptions: [100],
        statusFilterOptions: [],
        columnsToggleable: false,
        hiddenColumnsByDefault: [],
        reorderable: true,
        selectable: true,
      },
      () => {}
    )
  )!;
  let rejectMove!: (reason: Error) => void;
  const perform = vi.fn(
    () =>
      new Promise<void>((_resolve, reject) => {
        rejectMove = reject;
      })
  );
  const moving = state.reorder(0, 1, perform);
  const failed = expect(moving).rejects.toThrow('Failed to save');
  await nextTick();
  expect(
    state.table.getRowModel().rows.map((row) => row.original.name)
  ).toEqual(['Alpha', 'Beta']);
  rejectMove(new Error('Failed to save'));
  await failed;
  expect(
    state.table.getRowModel().rows.map((row) => row.original.name)
  ).toEqual(['Beta', 'Alpha']);

  state.viewSortField.value = 'name';
  await state.reorder(0, 1, perform);
  state.viewSortField.value = '';
  state.search.value = 'Beta';
  await state.reorder(0, 1, perform);
  expect(perform).toHaveBeenCalledOnce();
});

it('adapts node endpoint requests and renders serialized cells in the shared table', async () => {
  const post = vi.spyOn(actionClient, 'post').mockResolvedValue({
    data: {
      data: [
        {
          id: 1,
          name: {label: 'Settings link', url: '/settings/item'},
          count: {html: '<strong>42</strong>'},
        },
      ],
      pagination: result('Unused').pagination,
    },
  } as never);
  const host = mountNode({
    columns: [
      {key: 'name', label: 'Name', sortable: true},
      {key: 'count', label: 'Count'},
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
