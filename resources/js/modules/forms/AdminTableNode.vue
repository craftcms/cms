<script setup lang="ts">
  import {actionClient, t} from '@craftcms/ui';
  import {router} from '@inertiajs/vue3';
  import {
    type ColumnDef,
    getCoreRowModel,
    type RowSelectionState,
    useVueTable,
  } from '@tanstack/vue-table';
  import {watchDebounced} from '@vueuse/core';
  import {computed, defineComponent, h, onMounted, ref, watch} from 'vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import CraftSelectRich, {
    type SelectRichOption,
  } from '@craftcms/ui/vue/CraftSelectRich.vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import CpLink from '@/common/components/CpLink.vue';
  import type {ActionItemLink, PaginationData} from '@/common/types';
  import type {
    BulkAction,
    BulkActionItem,
  } from '@/modules/elements/types/actions';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import {useLocalStorage} from '@/common/composables/useStorage';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import ElementStatus from '@/modules/elements/ElementStatus.vue';
  import AdminTableToolbar from '@/modules/admin-table/components/AdminTableToolbar.vue';
  import CreateActionButton from '@/modules/admin-table/components/CreateActionButton.vue';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import MoveToPageButton from '@/modules/admin-table/components/MoveToPageButton.vue';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import type {FormNodePayload, FormValues} from './types';

  interface TableColumn {
    key: string;
    label: string;
  }

  interface TableLink {
    label: string;
    url: string | null;
  }

  interface TableMenu {
    label: string;
    items: TableLink[];
  }

  interface TableIcon {
    icon: string;
    label?: string;
  }

  interface TableHtml {
    html: string;
  }

  interface BulkActionSingle {
    label: string;
    url: string;
    params?: Record<string, unknown>;
    allowMultiple?: boolean;
    fill?: string;
  }

  interface BulkActionMenu {
    label?: string;
    icon?: string;
    items: BulkActionSingle[];
  }

  type BulkActionDescriptor = BulkActionSingle | BulkActionMenu;

  type TableCellValue =
    | string
    | number
    | boolean
    | null
    | TableLink
    | TableLink[]
    | TableMenu
    | TableIcon
    | TableHtml;

  interface TableStatus {
    value?: string;
    fill: string;
    label: string | null;
  }

  interface StatusFilterOption {
    value: string;
    label: string;
    fill: string | null;
  }

  type TableRow = Record<string, TableCellValue> & {
    id?: string | number;
    _deletable?: boolean;
    _status?: TableStatus | null;
    _search?: string;
  };

  const props = defineProps<{
    node: FormNodePayload<{
      columns: TableColumn[];
      rows: TableRow[];
      dataUrl: string | null;
      perPage: number;
      perPageOptions: number[];
      moveToPageUrl: string | null;
      emptyMessage: string | null;
      createLabel: string | null;
      createUrl: string | null;
      createMenuItems: Array<{label: string; url: string}> | null;
      createActionInPageHeader: boolean;
      reorderUrl: string | null;
      reorderSuccessMessage: string | null;
      reorderFailMessage: string | null;
      deleteUrl: string | null;
      deleteConfirmMessage: string | null;
      bulkDeletable: boolean;
      bulkActions: BulkActionDescriptor[];
      statusActions: BulkActionSingle[];
      statusFilterOptions: StatusFilterOption[];
      searchable: boolean;
      searchPlaceholder: string | null;
      bordered: boolean;
    }>;
  }>();

  const rows = ref<TableRow[]>([...props.node.props.rows]);
  watch(
    () => props.node.props.rows,
    (newRows) => {
      rows.value = [...newRows];
    }
  );

  const isEndpointMode = computed(() => !!props.node.props.dataUrl);

  const pageRows = ref<TableRow[]>([]);

  // Endpoint tables are keyed by endpoint, since a node uid isn't unique across screens or plugins.
  const storageKey = props.node.props.dataUrl
    ? `adminTable.${new URL(props.node.props.dataUrl, window.location.origin).pathname}`
    : `adminTable.${window.location.pathname}.${props.node.uid}`;

  const storedPerPage = props.node.props.dataUrl
    ? useLocalStorage(`${storageKey}.perPage`, props.node.props.perPage, {
        writeDefaults: false,
      })
    : null;

  const perPage = ref(
    storedPerPage &&
      props.node.props.perPageOptions.includes(storedPerPage.value)
      ? storedPerPage.value
      : props.node.props.perPage
  );
  const pagination = ref<PaginationData | null>(null);
  const loading = ref(false);

  async function fetchPage(page: number): Promise<void> {
    loading.value = true;
    try {
      const {data} = await actionClient.post<{
        data: TableRow[];
        pagination: PaginationData;
      }>(props.node.props.dataUrl!, {
        page,
        per_page: perPage.value,
        search: search.value || undefined,
        status: status.value || undefined,
      });
      pageRows.value = data.data;
      pagination.value = data.pagination;
      // The endpoint may ignore or clamp `per_page`, so defer to the size it actually used.
      perPage.value = data.pagination.per_page;
    } finally {
      loading.value = false;
    }
  }

  onMounted(() => {
    if (isEndpointMode.value) {
      fetchPage(1);
    }
  });

  const search = ref('');

  const hasStatusFilter = computed(
    () => props.node.props.statusFilterOptions.length > 0
  );

  const storedStatus = useLocalStorage(`${storageKey}.status`, '', {
    writeDefaults: false,
  });

  const status = ref(
    hasStatusFilter.value &&
      props.node.props.statusFilterOptions.some(
        (option) => option.value === storedStatus.value
      )
      ? storedStatus.value
      : ''
  );

  watch(status, (value) => {
    storedStatus.value = value;
    table.resetRowSelection();

    if (isEndpointMode.value) {
      fetchPage(1);
    }
  });

  const isFiltered = computed(() => !!search.value || !!status.value);

  watchDebounced(
    search,
    () => {
      if (isEndpointMode.value) {
        fetchPage(1);
      }
    },
    {debounce: 300}
  );

  function cellText(value: TableCellValue): string {
    if (value === null || value === undefined) return '';
    if (typeof value !== 'object') return String(value);
    if (Array.isArray(value)) return value.map((link) => link.label).join(' ');
    // `html` cells could hold anything (a working custom element, not just markup) — there's no
    // safe, generic way to reduce that to plain text, so a row relying on one for its primary
    // content needs its own explicit `_search` to stay searchable.
    if ('html' in value) return '';
    return value.label ?? '';
  }

  function rowSearchText(row: TableRow): string {
    const text =
      row._search ??
      props.node.props.columns
        .map((column) => cellText(row[column.key] ?? null))
        .join(' ');

    return text.toLowerCase();
  }

  const filteredRows = computed(() => {
    const query = search.value.trim().toLowerCase();

    return rows.value.filter(
      (row) =>
        (!status.value || row._status?.value === status.value) &&
        (!query || rowSearchText(row).includes(query))
    );
  });

  const displayedRows = computed(() =>
    isEndpointMode.value ? pageRows.value : filteredRows.value
  );

  const footerFrom = computed(() =>
    isEndpointMode.value ? (pagination.value?.from ?? 0) : 1
  );
  const footerTo = computed(() =>
    isEndpointMode.value
      ? (pagination.value?.to ?? 0)
      : displayedRows.value.length
  );
  const footerTotal = computed(() =>
    isEndpointMode.value
      ? (pagination.value?.total ?? 0)
      : displayedRows.value.length
  );

  const columnHelper = createCraftColumnHelper<TableRow>();

  const hasCreateAction = computed(
    () =>
      (!!props.node.props.createUrl && !!props.node.props.createLabel) ||
      !!props.node.props.createMenuItems?.length
  );

  const createActionInToolbar = computed(
    () => hasCreateAction.value && !props.node.props.createActionInPageHeader
  );

  function isMenu(
    value: TableLink | TableLink[] | TableMenu | TableIcon | TableHtml
  ): value is TableMenu {
    return !Array.isArray(value) && 'items' in value;
  }

  function isIcon(
    value: TableLink | TableLink[] | TableMenu | TableIcon | TableHtml
  ): value is TableIcon {
    return !Array.isArray(value) && 'icon' in value;
  }

  function isHtml(
    value: TableLink | TableLink[] | TableMenu | TableIcon | TableHtml
  ): value is TableHtml {
    return !Array.isArray(value) && 'html' in value;
  }

  function isBulkActionMenu(
    action: BulkActionDescriptor
  ): action is BulkActionMenu {
    return 'items' in action;
  }

  function renderLink(link: TableLink) {
    return link.url
      ? h(CpLink, {href: link.url, inertia: false}, () => link.label)
      : link.label;
  }

  function renderIcon(icon: TableIcon) {
    return h('craft-icon', {name: icon.icon, label: icon.label});
  }

  function renderHtmlCell(cell: TableHtml) {
    return h('div', {innerHTML: cell.html});
  }

  function renderMenu(menu: TableMenu) {
    return h(
      ActionMenu,
      {
        label: menu.label,
        actions: menu.items.map(
          (item): ActionItemLink => ({
            type: 'link',
            href: item.url ?? '#',
            label: item.label,
          })
        ),
      },
      {
        invoker: () =>
          h(
            'craft-button',
            {type: 'button', size: 'small', variant: 'outline'},
            menu.label
          ),
      }
    );
  }

  const columns = computed(() => {
    const cols: ColumnDef<TableRow, any>[] = props.node.props.columns.map(
      (column, columnIndex) =>
        columnHelper.accessor(column.key, {
          header: column.label,
          cell: ({getValue, row}) => {
            const value = getValue();
            let rendered;

            if (Array.isArray(value)) {
              rendered = value.flatMap((link, index) => [
                index > 0 ? ', ' : '',
                renderLink(link),
              ]);
            } else if (value !== null && typeof value === 'object') {
              if (isMenu(value)) rendered = renderMenu(value);
              else if (isIcon(value)) rendered = renderIcon(value);
              else if (isHtml(value)) rendered = renderHtmlCell(value);
              else rendered = renderLink(value);
            } else {
              rendered = value ?? '';
            }

            const status = row.original._status;
            if (columnIndex === 0 && status) {
              return h('div', {class: 'flex flex-nowrap gap-1 items-center'}, [
                h('craft-indicator', {
                  fill: status.fill,
                  label: status.label ?? undefined,
                }),
                rendered,
              ]);
            }

            return rendered;
          },
        })
    );

    if (props.node.props.deleteUrl) {
      cols.push(
        columnHelper.actions(({row}) => {
          const actions = [];

          if (row.original._deletable !== false) {
            actions.push(
              h(DeleteButton, {onClick: () => deleteRow(row.original)})
            );
          }

          return actions;
        })
      );
    }

    return cols;
  });

  const rowSelection = ref<RowSelectionState>({});

  const hasBulkFooter = computed(
    () =>
      (!!props.node.props.deleteUrl && props.node.props.bulkDeletable) ||
      props.node.props.bulkActions.length > 0 ||
      props.node.props.statusActions.length > 0
  );

  const showMoveToPage = computed(
    () =>
      isEndpointMode.value &&
      !isFiltered.value &&
      !!props.node.props.moveToPageUrl &&
      (pagination.value?.last_page ?? 0) > 1
  );

  const paginationState = computed(() => ({
    pageIndex: (pagination.value?.current_page ?? 1) - 1,
    pageSize: perPage.value,
  }));

  function onPaginationChange(
    updater:
      | {pageIndex: number; pageSize: number}
      | ((old: {pageIndex: number; pageSize: number}) => {
          pageIndex: number;
          pageSize: number;
        })
  ): void {
    if (!isEndpointMode.value) return;

    const next =
      updater instanceof Function ? updater(paginationState.value) : updater;

    if (next.pageSize !== perPage.value) {
      const previous = perPage.value;
      perPage.value = next.pageSize;
      table.resetRowSelection();
      fetchPage(1)
        .then(() => {
          if (storedPerPage) {
            storedPerPage.value = perPage.value;
          }
        })
        .catch(() => {
          perPage.value = previous;
        });
      return;
    }

    fetchPage(next.pageIndex + 1);
  }

  const table = useVueTable({
    get data() {
      return displayedRows.value;
    },
    get columns() {
      return columns.value;
    },
    state: {
      get rowSelection() {
        return rowSelection.value;
      },
      get pagination() {
        return paginationState.value;
      },
    },
    getRowId: (row) => String(row.id),
    enableRowSelection: (row) =>
      hasBulkFooter.value && row.original._deletable !== false,
    onRowSelectionChange: (updater) => {
      rowSelection.value =
        updater instanceof Function ? updater(rowSelection.value) : updater;
    },
    enableSorting: false,
    getCoreRowModel: getCoreRowModel(),
    get manualPagination() {
      return isEndpointMode.value;
    },
    // TanStack uses -1 for an unknown page count outside endpoint mode.
    get pageCount() {
      return isEndpointMode.value ? (pagination.value?.last_page ?? -1) : -1;
    },
    onPaginationChange,
  });

  const selectedIds = computed(() =>
    table.getSelectedRowModel().rows.map((row) => row.original.id!)
  );

  function onReorder(startIndex: number, finishIndex: number): void {
    if (isEndpointMode.value) {
      onReorderWithinPage(startIndex, finishIndex);
      return;
    }

    const previous = rows.value;
    const reordered = [...previous];
    const [moved] = reordered.splice(startIndex, 1);

    if (!moved) {
      return;
    }

    reordered.splice(finishIndex, 0, moved);
    rows.value = reordered;

    // The backend actions this posts to are shared with the legacy `Craft.VueAdminTable`
    // widget, which JSON-encodes `ids` as a string rather than posting a raw array.
    actionClient
      .post(props.node.props.reorderUrl!, {
        ids: JSON.stringify(reordered.map((row) => row.id)),
      })
      .then(() => {
        Craft.cp?.displayNotice?.(
          props.node.props.reorderSuccessMessage ?? t('Order updated.')
        );
        refreshForm();
      })
      .catch(() => {
        rows.value = previous;
        Craft.cp?.displayError?.(
          props.node.props.reorderFailMessage ?? t('Couldn’t reorder.')
        );
      });
  }

  function onReorderWithinPage(startIndex: number, finishIndex: number): void {
    const previous = pageRows.value;
    const reordered = [...previous];
    const [moved] = reordered.splice(startIndex, 1);

    if (!moved || !pagination.value) {
      return;
    }

    reordered.splice(finishIndex, 0, moved);
    pageRows.value = reordered;

    const toPosition =
      (pagination.value.current_page - 1) * pagination.value.per_page +
      finishIndex;

    actionClient
      .post(props.node.props.reorderUrl!, {id: moved.id, toPosition})
      .then(() => {
        Craft.cp?.displayNotice?.(
          props.node.props.reorderSuccessMessage ?? t('Order updated.')
        );
        // Reordering can shift rows across page boundaries.
        fetchPage(pagination.value!.current_page);
      })
      .catch(() => {
        pageRows.value = previous;
        Craft.cp?.displayError?.(
          props.node.props.reorderFailMessage ?? t('Couldn’t reorder.')
        );
      });
  }

  function moveToPage(id: TableRow['id'], page: number): void {
    actionClient
      .post(props.node.props.moveToPageUrl!, {
        id,
        page,
        per_page: pagination.value!.per_page,
      })
      .then(() => {
        Craft.cp?.displayNotice?.(
          props.node.props.reorderSuccessMessage ?? t('Order updated.')
        );
        table.resetRowSelection();
        fetchPage(pagination.value!.current_page);
      })
      .catch(() => {
        Craft.cp?.displayError?.(
          props.node.props.reorderFailMessage ?? t('Couldn’t reorder.')
        );
      });
  }

  async function deleteRow(row: TableRow): Promise<void> {
    const message = props.node.props.deleteConfirmMessage ?? t('Are you sure?');

    if (!confirm(message)) {
      return;
    }

    await actionClient.post(props.node.props.deleteUrl!, {id: row.id});
    rows.value = rows.value.filter((r) => r.id !== row.id);
    refreshForm();
  }

  async function deleteSelected(): Promise<void> {
    const ids = selectedIds.value;

    if (!ids.length) return;

    const message = props.node.props.deleteConfirmMessage ?? t('Are you sure?');

    if (!confirm(message)) {
      return;
    }

    await actionClient.post(props.node.props.deleteUrl!, {ids});
    rows.value = rows.value.filter((r) => !ids.includes(r.id!));
    table.resetRowSelection();
    refreshForm();
  }

  // `BulkActionsBar` handles the request, per-row-vs-bulk disabling, and
  // refresh itself once this is declarative — see its own `resolveItem()`.
  function bulkActionToItem(action: BulkActionSingle): BulkActionItem {
    return {
      key: action.url,
      label: action.label,
      bulk: action.allowMultiple === false ? false : undefined,
      fill: action.fill,
      action: {
        type: 'http',
        url: action.url,
        // Server JSON, trusted to be FormValue-shaped at runtime.
        body: action.params as FormValues | undefined,
      },
    };
  }

  // ActionMenu's display items receive no props, so this component closes over the current selection.
  const MoveToPageDisplay = defineComponent({
    name: 'MoveToPageDisplay',
    setup() {
      return () =>
        h(MoveToPageButton, {
          currentPage: pagination.value?.current_page ?? 1,
          lastPage: pagination.value?.last_page ?? 1,
          loading: loading.value,
          onMove: (page: number) => {
            const id = selectedIds.value[0];
            if (id !== undefined) {
              moveToPage(id, page);
            }
          },
        });
    },
  });

  const footerActionItems = computed((): Array<BulkAction> => {
    const items: Array<BulkAction> = props.node.props.bulkActions.map(
      (action) =>
        isBulkActionMenu(action)
          ? {
              type: 'group',
              heading: action.label,
              items: action.items.map(bulkActionToItem),
            }
          : bulkActionToItem(action)
    );

    if (showMoveToPage.value && selectedIds.value.length === 1) {
      items.push({type: 'display', is: MoveToPageDisplay});
    }

    if (props.node.props.deleteUrl && props.node.props.bulkDeletable) {
      items.push({
        key: 'delete',
        label: t('Delete'),
        variant: 'danger',
        onClick: deleteSelected,
      });
    }

    return items;
  });

  const statusActionItems = computed(
    (): Array<BulkAction> =>
      props.node.props.statusActions.map(bulkActionToItem)
  );

  // The select's option slot only types the base option shape.
  function statusOptionFill(option: SelectRichOption): string | undefined {
    return (option as StatusFilterOption).fill ?? undefined;
  }

  function refreshForm(): void {
    router.reload({only: ['form']});
  }
</script>

<template>
  <div :data-form-node="node.uid">
    <LayoutSlot
      v-if="hasCreateAction && node.props.createActionInPageHeader"
      name="content-actions"
    >
      <CreateActionButton
        :label="node.props.createLabel"
        :url="node.props.createUrl"
        :menu-items="node.props.createMenuItems"
      />
    </LayoutSlot>

    <component
      :is="node.props.bordered ? 'craft-pane' : 'div'"
      v-bind="node.props.bordered ? {padding: '0', appearance: 'raised'} : {}"
    >
      <AdminTable
        :table="table"
        :reorderable="!!node.props.reorderUrl && !isFiltered"
        :selectable="hasBulkFooter"
        :loading="loading"
        :from="footerFrom"
        :to="footerTo"
        :total="footerTotal"
        :enable-adjust-page-size="isEndpointMode"
        :page-size-options="node.props.perPageOptions"
        :actions="footerActionItems"
        :statuses="statusActionItems"
        ids-field="ids"
        @reorder="onReorder"
        @action-performed="refreshForm"
      >
        <template
          v-if="
            hasStatusFilter || node.props.searchable || createActionInToolbar
          "
          #table-header
        >
          <AdminTableToolbar>
            <template v-if="hasStatusFilter" #status>
              <CraftSelectRich
                v-model="status"
                :options="node.props.statusFilterOptions"
                :label="t('Status')"
                label-sr-only
              >
                <template #option="{option}">
                  <span
                    v-if="statusOptionFill(option)"
                    class="inline-flex items-center gap-2"
                  >
                    <craft-indicator
                      :fill="statusOptionFill(option)"
                    ></craft-indicator>
                    {{ option.label }}
                  </span>
                  <ElementStatus v-else :label="option.label" value="" />
                </template>
              </CraftSelectRich>
            </template>
            <template v-if="node.props.searchable" #search>
              <CraftInput
                name="search"
                :label="t('Search')"
                :placeholder="node.props.searchPlaceholder ?? t('Search')"
                label-sr-only
                v-model="search"
              >
                <div slot="suffix" class="flex">
                  <craft-button
                    v-if="search"
                    type="button"
                    icon
                    size="small"
                    variant="plain"
                    @click="search = ''"
                  >
                    <craft-icon
                      name="x"
                      :label="t('Clear search')"
                    ></craft-icon>
                  </craft-button>
                </div>
              </CraftInput>
            </template>
            <template v-if="createActionInToolbar" #actions>
              <CreateActionButton
                :label="node.props.createLabel"
                :url="node.props.createUrl"
                :menu-items="node.props.createMenuItems"
              />
            </template>
          </AdminTableToolbar>
        </template>
        <template #empty-row>
          <craft-empty
            v-if="!loading"
            :label="
              search
                ? t('No results for “{search}”.', {search})
                : status
                  ? t('No results.')
                  : (node.props.emptyMessage ?? t('Nothing to show.'))
            "
          ></craft-empty>
        </template>
      </AdminTable>
    </component>
  </div>
</template>
