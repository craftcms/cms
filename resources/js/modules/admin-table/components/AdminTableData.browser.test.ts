import {createApp, effectScope, h, nextTick, ref} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import AdminTable from './AdminTable.vue';
import AdminTableNode from '@/modules/ui/AdminTableNode.vue';
import {actionClient} from '@craftcms/ui';
import {useAdminTable} from '../useAdminTable';
import type {
  AdminTableHandle,
  AdminTablePage,
  AdminTableRequest,
} from '../types';

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => ({props: {readOnly: false}}),
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
    row.querySelector('td:not(.cp-table-cell--select)')?.textContent?.trim()
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
  const host = document.createElement('div');
  document.body.append(host);
  const app = createApp({
    render: () =>
      h(AdminTableNode, {
        node: {
          uid: 'settings-table',
          type: 'adminTable',
          children: [],
          props: {
            columns: [
              {key: 'name', label: 'Name', sortable: true},
              {key: 'count', label: 'Count'},
            ],
            rows: [],
            dataUrl: '/settings/table-data',
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
            bulkDeletable: false,
            deleteModalUrl: null,
            bulkActions: [{label: 'Archive', url: '/archive'}],
            statusActions: [],
            statusFilterOptions: [],
            columnsToggleable: true,
            hiddenColumnsByDefault: [],
            searchable: true,
            searchPlaceholder: null,
            bordered: false,
          },
        },
      } as never),
  });
  app.mount(host);
  teardown = () => app.unmount();
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
