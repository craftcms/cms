<script setup lang="ts">
  import {actionClient, t} from '@craftcms/ui';
  import {router} from '@inertiajs/vue3';
  import type {ColumnDef} from '@tanstack/vue-table';
  import {computed, h, ref, shallowRef} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import CpLink from '@/common/components/CpLink.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import type {
    ActionItemButton,
    ActionItemLink,
    PaginationData,
  } from '@/common/types';
  import {openSlideout} from '@/common/slideouts';
  import {createCraftColumnHelper} from '@/common/table/createCraftColumnHelper';
  import type {CraftTableFeatures} from '@/common/table/craftTable';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import CreateActionButton from '@/modules/admin-table/components/CreateActionButton.vue';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import type {
    AdminTableHandle,
    AdminTablePage,
    AdminTableRequest,
    AdminTableReorder,
  } from '@/modules/admin-table/types';
  import type {
    BulkAction,
    BulkActionItem,
  } from '@/modules/elements/types/actions';
  import AdminTableDeleteModal from './AdminTableDeleteModal.vue';
  import UiModal from './UiModal.vue';
  import type {UiValues} from './types';

  import type {
    BulkActionDescriptor,
    BulkActionMenu,
    BulkActionSingle,
    TableCellValue,
    TableHtml,
    TableIcon,
    TableLink,
    TableMenu,
    TableModalAction,
    TableNodePayload,
    TableRow,
  } from './table-types';

  const props = defineProps<{node: TableNodePayload}>();

  const adminTable = ref<AdminTableHandle>();
  const deletePending = shallowRef(false);
  const deletable = computed(
    () => props.node.props.deletable ?? !!props.node.props.deleteUrl
  );

  // Endpoint preferences are shared across screens that use the same endpoint.
  const storageKey = props.node.props.dataUrl
    ? `adminTable.${new URL(props.node.props.dataUrl, window.location.origin).pathname}`
    : `adminTable.${window.location.pathname}.${props.node.uid}`;

  async function loadRows(
    request: AdminTableRequest,
    signal: AbortSignal
  ): Promise<AdminTablePage<TableRow>> {
    const {data} = await actionClient.post<{
      data: TableRow[];
      pagination: PaginationData;
    }>(
      props.node.props.dataUrl!,
      {
        page: request.page,
        per_page: request.perPage,
        search: request.search,
        status: request.status,
        sort: request.sort,
      },
      {signal}
    );
    return {rows: data.data, pagination: data.pagination};
  }

  function sortValue(row: TableRow, key: string): string | number {
    const value =
      row._sort && key in row._sort
        ? row._sort[key]
        : cellText(row[key] ?? null);
    return typeof value === 'number' ? value : String(value ?? '');
  }

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

  const columnHelper = createCraftColumnHelper<TableRow>();

  const hasCreateAction = computed(
    () =>
      (!!props.node.props.createUrl && !!props.node.props.createLabel) ||
      !!props.node.props.createMenuItems?.length
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
    if (!link.url) {
      return link.label;
    }

    const url = link.url;

    return h(
      CpLink,
      {
        href: url,
        // A modified click still follows the link, to open the screen in a tab.
        onClick: link.slideout
          ? (event: MouseEvent) => {
              if (
                event.altKey ||
                event.ctrlKey ||
                event.metaKey ||
                event.shiftKey ||
                event.button !== 0
              ) {
                return;
              }

              event.preventDefault();
              void openSlideout(url, {onSaved: refreshTable});
            }
          : undefined,
      },
      () => link.label
    );
  }

  function renderIcon(icon: TableIcon) {
    return h('craft-icon', {name: icon.icon, label: icon.label});
  }

  function renderHtmlCell(cell: TableHtml) {
    return h('div', {innerHTML: cell.html});
  }

  function isModalAction(
    item: TableLink | TableModalAction
  ): item is TableModalAction {
    return 'modalUrl' in item && 'actionUrl' in item;
  }

  const menuModal = shallowRef<TableModalAction | null>(null);

  function onMenuModalSubmitted(): void {
    menuModal.value = null;
    refreshTable();
  }

  function renderMenu(menu: TableMenu) {
    return h(
      ActionMenu,
      {
        label: menu.label,
        actions: menu.items.map((item): ActionItemButton | ActionItemLink =>
          isModalAction(item)
            ? {
                type: 'button',
                label: item.label,
                onClick: () => {
                  menuModal.value = item;
                },
              }
            : {
                type: 'link',
                href: item.url ?? '#',
                label: item.label,
              }
        ),
      },
      {
        // Without the slot `craft-action-menu` has no invoker, and files the button
        // away with its content.
        //
        // The label reads as plain cell text until its row (`DataTable`'s
        // `cp-table-row`) is hovered or focused, or its menu is open, so a column of
        // these doesn't turn into a column of buttons. Where there's no hover to
        // reveal it, the chevron stays put.
        invoker: ({attributes}: {attributes: Record<string, string>}) =>
          h(
            'craft-button',
            {
              ...attributes,
              type: 'button',
              size: 'small',
              variant: 'plain',
              flush: '',
              class: 'group',
            },
            [
              menu.label,
              h('craft-icon', {
                name: 'chevron-down',
                slot: 'suffix',
                class:
                  'transition-opacity [@media(hover:hover)]:opacity-0 [.cp-table-row:hover_&]:opacity-100 [.cp-table-row:focus-within_&]:opacity-100 group-aria-expanded:opacity-100',
              }),
            ]
          ),
      }
    );
  }

  const columns = computed(() => {
    const cols: ColumnDef<CraftTableFeatures, TableRow, any>[] =
      props.node.props.columns.map((column, columnIndex) =>
        columnHelper.accessor(column.key, {
          header: column.label,
          enableSorting: !!column.sortable,
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

    if (deletable.value) {
      cols.push(
        columnHelper.actions(({row}) => {
          const actions = [];

          if (row.original._deletable !== false && deleteUrl(row.original)) {
            actions.push(
              h(DeleteButton, {
                disabled: deletePending.value,
                onClick: () => deleteRow(row.original),
              })
            );
          }

          return actions;
        })
      );
    }

    return cols;
  });

  const hasBulkFooter = computed(
    () =>
      !!props.node.props.bulkDeleteUrl ||
      props.node.props.bulkActions.length > 0 ||
      props.node.props.statusActions.length > 0 ||
      !!props.node.props.moveToPageUrl
  );

  async function reorderRows(move: AdminTableReorder<TableRow>): Promise<void> {
    const ids = move.rows.map((row) => row.id);
    await actionClient.post(
      props.node.props.reorderUrl!,
      move.paginated
        ? {
            id: move.row.id,
            toPosition: (move.page - 1) * move.pageSize + move.finishIndex,
          }
        : {ids}
    );
    notifyOrderUpdated();
    if (!move.paginated) refreshUi();
  }

  async function moveToPage(
    id: string | number,
    page: number,
    pageSize: number
  ): Promise<void> {
    await actionClient.post(props.node.props.moveToPageUrl!, {
      id,
      page,
      per_page: pageSize,
    });
    notifyOrderUpdated();
  }

  function notifyOrderUpdated(): void {
    Craft.cp?.displayNotice?.(
      props.node.props.reorderSuccessMessage ?? t('Order updated.')
    );
  }

  function onOrderFailed(): void {
    Craft.cp?.displayError?.(
      props.node.props.reorderFailMessage ?? t('Couldn’t reorder.')
    );
  }

  const deletingRow = ref<TableRow | null>(null);

  function deleteUrl(row: TableRow): string | null {
    return row._deleteUrl ?? props.node.props.deleteUrl;
  }

  async function submitDelete(
    url: string,
    data: {id?: string | number; ids?: Array<string | number>}
  ): Promise<boolean> {
    if (deletePending.value) return false;

    deletePending.value = true;

    try {
      await actionClient.delete(url, {data});

      return true;
    } catch (error: any) {
      Craft.cp?.displayError?.(
        error?.response?.data?.message ?? t('A server error occurred.')
      );
      return false;
    } finally {
      deletePending.value = false;
    }
  }

  function onModalDeleted(): void {
    const row = deletingRow.value;
    deletingRow.value = null;
    if (row?.id !== undefined) adminTable.value?.removeRows([row.id]);
    refreshTable();
  }

  async function deleteRow(row: TableRow): Promise<void> {
    const url = deleteUrl(row);
    if (!url || deletePending.value) return;

    if (props.node.props.deleteModalUrl) {
      deletingRow.value = row;
      return;
    }

    const message =
      row._deleteConfirmMessage ??
      props.node.props.deleteConfirmMessage ??
      t('Are you sure?');

    if (!confirm(message)) {
      return;
    }

    if (!(await submitDelete(url, {id: row.id}))) return;

    if (row.id !== undefined) adminTable.value?.removeRows([row.id]);
    refreshTable();
  }

  async function deleteSelected(): Promise<void> {
    const ids = adminTable.value?.selectedIds ?? [];
    const url = props.node.props.bulkDeleteUrl;

    if (!ids.length || !url || deletePending.value) return;

    const message =
      props.node.props.bulkDeleteConfirmMessage ?? t('Are you sure?');

    if (!confirm(message)) {
      return;
    }

    if (!(await submitDelete(url, {ids}))) return;

    adminTable.value?.removeRows(ids);
    refreshTable();
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
        // Server JSON, trusted to be UiValue-shaped at runtime.
        body: action.params as UiValues | undefined,
      },
    };
  }

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

    if (props.node.props.bulkDeleteUrl) {
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

  function onLoadFailed(): void {
    Craft.cp?.displayError?.(t('An error occurred.'));
  }

  function refreshUi(): void {
    router.reload({only: ['ui']});
  }

  function refreshTable(): void {
    refreshUi();

    void adminTable.value?.refresh();
  }
</script>

<template>
  <div :data-ui-node="node.uid">
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
        ref="adminTable"
        :rows="node.props.rows"
        :columns="columns"
        :load-rows="node.props.dataUrl ? loadRows : undefined"
        :storage-key="storageKey"
        :page-size="node.props.perPage"
        :page-size-options="node.props.perPageOptions"
        :get-row-label="
          (row) =>
            cellText(row[node.props.columns[0]?.key ?? ''] ?? null) ||
            String(row.id)
        "
        :get-search-text="rowSearchText"
        :get-row-status="(row) => row._status?.value"
        :get-sort-value="sortValue"
        :can-select-row="(row) => row._deletable !== false"
        :reorderable="!!node.props.reorderUrl"
        :reorder-rows="node.props.reorderUrl ? reorderRows : undefined"
        :move-to-page="node.props.moveToPageUrl ? moveToPage : undefined"
        :selectable="hasBulkFooter"
        :interactions-disabled="deletePending"
        :show-footer="node.props.showFooter"
        :actions="footerActionItems"
        :statuses="statusActionItems"
        :searchable="node.props.searchable"
        :search-placeholder="node.props.searchPlaceholder"
        :status-filter-options="node.props.statusFilterOptions"
        :columns-toggleable="node.props.columnsToggleable"
        :hidden-columns-by-default="node.props.hiddenColumnsByDefault"
        :empty-message="node.props.emptyMessage"
        :create-label="node.props.createLabel"
        :create-url="
          node.props.createActionInPageHeader ? null : node.props.createUrl
        "
        :create-menu-items="
          node.props.createActionInPageHeader
            ? null
            : node.props.createMenuItems
        "
        ids-field="ids"
        @action-error="onOrderFailed"
        @load-error="onLoadFailed"
        @action-performed="refreshUi"
      >
        <template
          v-if="hasCreateAction && node.props.createActionInPageHeader"
          #empty-actions
        >
          <CreateActionButton
            :label="node.props.createLabel"
            :url="node.props.createUrl"
            :menu-items="node.props.createMenuItems"
          />
        </template>
      </AdminTable>
    </component>
    <AdminTableDeleteModal
      v-if="deletingRow && node.props.deleteModalUrl"
      :modal-url="node.props.deleteModalUrl"
      :delete-url="deleteUrl(deletingRow)!"
      :row-id="deletingRow.id!"
      @close="deletingRow = null"
      @deleted="onModalDeleted"
    />
    <UiModal
      v-if="menuModal"
      :modal-url="menuModal.modalUrl"
      :action-url="menuModal.actionUrl"
      :params="menuModal.params"
      @close="menuModal = null"
      @submitted="onMenuModalSubmitted"
    />
  </div>
</template>
