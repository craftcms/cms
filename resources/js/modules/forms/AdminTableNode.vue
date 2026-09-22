<script setup lang="ts">
  import {actionClient, t} from '@craftcms/ui';
  import {router} from '@inertiajs/vue3';
  import {
    type ColumnDef,
    getCoreRowModel,
    type RowSelectionState,
    useVueTable,
  } from '@tanstack/vue-table';
  import {computed, h, ref, watch} from 'vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import CpLink from '@/common/components/CpLink.vue';
  import Text from '@/common/components/Text.vue';
  import type {ActionItemButton, ActionItemLink} from '@/common/types';
  import Empty from '@/common/components/Empty.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import type {FormNodePayload} from './types';

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

  /**
   * A single bulk-footer action — posts `{ids: <selected row ids>, ...params}` to `url`.
   * `allowMultiple: false` (default `true`) disables it — as a standalone button, or as a
   * menu item — whenever more than one row is selected, for an action that only makes sense
   * against one row at a time (its endpoint has no obligation to reflect that itself; nothing
   * stops a request with several ids reaching it some other way, so this is a UI nicety, not
   * a substitute for that endpoint enforcing the same rule server-side).
   */
  interface BulkActionSingle {
    label: string;
    url: string;
    params?: Record<string, unknown>;
    allowMultiple?: boolean;
  }

  /**
   * A dropdown of {@link BulkActionSingle}s, shown as one button in the bulk footer. `label`
   * is optional — omit it (alongside an `icon`) for an icon-only invoker, matching legacy's
   * own unlabeled gear-icon settings menu for a single, infrequently-needed item.
   */
  interface BulkActionMenu {
    label?: string;
    icon?: string;
    items: BulkActionSingle[];
  }

  type BulkAction = BulkActionSingle | BulkActionMenu;

  /**
   * A scalar renders as plain text; `{label, url}` renders as a link (or plain
   * text when `url` is null); a list of those renders several links in one
   * cell; `{label, items}` renders a dropdown menu of links; `{icon, label?}`
   * renders a single `<craft-icon>` (`label` becomes its accessible name);
   * `{html}` renders pre-built markup via `v-html`, for the rare cell no
   * structured shape above can express — the same convention the PHP `Table`
   * Node's non-Vue `renderHtml()` fallback uses (menus render as inline links
   * there, an icon renders as its label text, `html` prints as-is), so both
   * renderers agree on one row shape without the columns themselves declaring
   * a type. Only delete (and, when reorderable, the drag handle `DataTable`
   * renders on its own) live in the trailing actions column — a menu is
   * ordinary column data, not merged into it.
   *
   * `html` is trusted completely, not sanitized here or on the PHP side: the
   * PHP `Table::rows()` that produced it renders it unsanitized on purpose,
   * so a cell can host a real working custom element (a copy-to-clipboard
   * control, say) rather than only static display markup — unlike
   * `TemplateContentNode.vue`'s `v-html`, which still relies on
   * `TemplateContent`'s own sanitizer. That leaves whichever server-side
   * column builds one of these entirely responsible for its safety —
   * `Html::encode()`-ing any user-entered value before it goes into the
   * string, exactly as if writing directly to the page. Prefer a structured
   * shape above when it fits; reach for `html` only when it doesn't.
   */
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
    fill: string;
    label: string | null;
  }

  type TableRow = Record<string, TableCellValue> & {
    /** Required when the table is reorderable and/or deletable. */
    id?: string | number;
    /** Opts a single row out of an otherwise-deletable table. */
    _deletable?: boolean;
    /**
     * A colored status indicator dot, already resolved server-side (see PHP `Table::rows()`) —
     * this component just draws it, prefixed onto the *first* column's own cell content.
     */
    _status?: TableStatus | null;
    /**
     * The plain text this row's own search box (see {@link searchable}) matches against, when
     * anything worth searching isn't itself a visible column. Falls back to the row's own
     * column text when absent.
     */
    _search?: string;
  };

  const props = defineProps<{
    node: FormNodePayload<{
      columns: TableColumn[];
      rows: TableRow[];
      emptyMessage: string | null;
      createLabel: string | null;
      createUrl: string | null;
      createMenuItems: Array<{label: string; url: string}> | null;
      reorderUrl: string | null;
      reorderSuccessMessage: string | null;
      reorderFailMessage: string | null;
      deleteUrl: string | null;
      deleteConfirmMessage: string | null;
      bulkDeletable: boolean;
      bulkActions: BulkAction[];
      searchable: boolean;
      searchPlaceholder: string | null;
    }>;
  }>();

  // Reorder/delete mutate this local copy directly (optimistic UI, matching the
  // legacy Craft.VueAdminTable's behavior) rather than round-tripping through a
  // form refresh for what's otherwise a read-only listing.
  const rows = ref<TableRow[]>([...props.node.props.rows]);
  watch(
    () => props.node.props.rows,
    (newRows) => {
      rows.value = [...newRows];
    }
  );

  const search = ref('');

  /** Plain text a cell's own rendered content reduces to, for {@link rowSearchText}. */
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

    return query
      ? rows.value.filter((row) => rowSearchText(row).includes(query))
      : rows.value;
  });

  const columnHelper = createCraftColumnHelper<TableRow>();

  const createMenuActions = computed<ActionItemLink[]>(
    () =>
      props.node.props.createMenuItems?.map((item) => ({
        type: 'link',
        href: item.url,
        label: item.label,
      })) ?? []
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

  function isBulkActionMenu(action: BulkAction): action is BulkActionMenu {
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
        columnHelper.actions(({row}) =>
          row.original._deletable === false
            ? []
            : [h(DeleteButton, {onClick: () => deleteRow(row.original)})]
        )
      );
    }

    return cols;
  });

  // Keyed by row id (see `getRowId`) rather than row index, so a selection
  // survives the optimistic row-removal a delete does. Gated on `bulkDeletable`
  // (see `Table::deletable()`) or a non-empty `bulkActions` — either turns
  // selection on; a plain `deleteUrl` alone (no `bulk: true`) wouldn't know
  // what to do with the `ids` array a bulk request posts.
  const rowSelection = ref<RowSelectionState>({});

  const hasBulkFooter = computed(
    () =>
      (!!props.node.props.deleteUrl && props.node.props.bulkDeletable) ||
      props.node.props.bulkActions.length > 0
  );

  const table = useVueTable({
    get data() {
      return filteredRows.value;
    },
    get columns() {
      return columns.value;
    },
    state: {
      get rowSelection() {
        return rowSelection.value;
      },
    },
    getRowId: (row) => String(row.id),
    // A row opted out of the single-row delete action (`_deletable: false`) is
    // just as ineligible for any bulk one — there's no separate "excluded from
    // bulk actions but not delete" flag, so this gate serves both.
    enableRowSelection: (row) =>
      hasBulkFooter.value && row.original._deletable !== false,
    onRowSelectionChange: (updater) => {
      rowSelection.value =
        updater instanceof Function ? updater(rowSelection.value) : updater;
    },
    enableSorting: false,
    getCoreRowModel: getCoreRowModel(),
  });

  const selectedIds = computed(() =>
    table.getSelectedRowModel().rows.map((row) => row.original.id!)
  );

  function onReorder(startIndex: number, finishIndex: number): void {
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
        // The optimistic reorder above never actually took server-side — put the rows
        // back the way they were rather than leaving the UI showing an order that
        // silently failed to save.
        rows.value = previous;
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

  /**
   * Deletes every currently-selected row in one request. Posts a real `ids`
   * array (not the JSON-encoded string `onReorder` posts) — this shares the
   * same backend action as a single-row {@link deleteRow}, and the legacy
   * `Craft.VueAdminTable` widget's own bulk delete posted `ids` as a plain
   * array the same way.
   */
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

  /**
   * Runs one bulk action (a {@link BulkActionSingle}, whether it stands alone
   * or was picked from a {@link BulkActionMenu}) against every selected row.
   * Unlike delete, there's no confirmation step here — none of these actions
   * are inherently destructive the way delete is, so `Table::bulkActions()`
   * doesn't carry a per-action confirm message.
   */
  async function performBulkAction(action: BulkActionSingle): Promise<void> {
    const ids = selectedIds.value;

    if (!ids.length) return;
    // Belt-and-braces alongside the disabled button/menu item below — the
    // control shouldn't be reachable in this state, but this is what actually
    // stops the request if it somehow is.
    if (action.allowMultiple === false && ids.length > 1) return;

    await actionClient.post(action.url, {ids, ...action.params});
    table.resetRowSelection();
    refreshForm();
  }

  function bulkActionDisabled(action: BulkActionSingle): boolean {
    return action.allowMultiple === false && selectedIds.value.length > 1;
  }

  /** Adapts a menu action's items to `ActionMenu`'s item shape. */
  function bulkActionMenuItems(menu: BulkActionMenu): ActionItemButton[] {
    return menu.items.map((item) => ({
      type: 'button',
      label: item.label,
      disabled: bulkActionDisabled(item),
      onClick: () => performBulkAction(item),
    }));
  }

  // This Node's data is set once, from the page's own initial render — not read from a
  // live-fetching endpoint the way an element index table is — so a mutation only updates
  // this component's own local `rows`. Other props derived from the same server-side state
  // (e.g. whether a "New store"-style create button should show at all) don't know to
  // recompute on their own; a partial Inertia reload re-fetches this page's `form` prop so
  // they catch up too. The local mutation above still runs first, for instant feedback while
  // this is in flight.
  function refreshForm(): void {
    router.reload({only: ['form']});
  }
</script>

<template>
  <div :data-form-node="node.uid">
    <LayoutSlot v-if="node.props.createUrl" name="actions">
      <CpLink
        variant="accent"
        appearance="button"
        :href="node.props.createUrl"
        :inertia="false"
        >{{ node.props.createLabel }}</CpLink
      >
    </LayoutSlot>
    <LayoutSlot v-else-if="createMenuActions.length" name="actions">
      <ActionMenu :actions="createMenuActions">
        <template #invoker="{attributes}">
          <craft-button
            type="button"
            variant="primary"
            icon="plus"
            v-bind="attributes"
          >
            {{ node.props.createLabel }}
            <craft-icon name="chevron-down" slot="suffix"></craft-icon>
          </craft-button>
        </template>
      </ActionMenu>
    </LayoutSlot>

    <craft-pane padding="0" appearance="raised">
      <div v-if="node.props.searchable" slot="header-actions">
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
              <craft-icon name="x" :label="t('Clear search')"></craft-icon>
            </craft-button>
          </div>
        </CraftInput>
      </div>

      <AdminTable
        :table="table"
        :reorderable="!!node.props.reorderUrl && !search"
        :selectable="hasBulkFooter"
        @reorder="onReorder"
      >
        <template #empty-row>
          <Empty
            :label="
              search
                ? t('No results for “{search}”.', {search})
                : (node.props.emptyMessage ?? t('Nothing to show.'))
            "
          />
        </template>
      </AdminTable>

      <!--
        The element index's own footer (`BaseElementIndex.vue`, reached via
        `AdminTable`) never renders here: it needs `from`/`to`/`total` props
        this component doesn't pass (nothing paginates a Table Node's data —
        every row is always on the one page), and its selection/bulk-action
        row (`BulkActionsBar`) is wired to real Craft elements end to end
        (`PerformElementActionController` resolves an `ElementQuery` and a
        registered `ElementAction` — there's no fitting either into it for
        these plain PHP-object rows). This replicates just its appearance —
        same background, border, and copy — driven by this component's own
        `rows`/`selectedIds` instead.
      -->
      <div v-if="filteredRows.length" class="admin-table-footer">
        <template v-if="selectedIds.length">
          <Text
            as="span"
            class="admin-table-footer__count"
            template="{count, plural, =1{# selected} other{# selected}}"
            :params="{count: selectedIds.length}"
          />
          <craft-button
            type="button"
            variant="plain"
            size="small"
            @click="table.resetRowSelection()"
            >{{ t('Clear selection') }}</craft-button
          >
          <div class="admin-table-footer__actions">
            <template
              v-for="(action, index) in node.props.bulkActions"
              :key="index"
            >
              <ActionMenu
                v-if="isBulkActionMenu(action)"
                :actions="bulkActionMenuItems(action)"
                :icon="action.icon"
                :label="action.label || undefined"
              >
                <!--
                  Omitted (rather than `v-if="action.label"` around the button
                  alone) when there's no label, so ActionMenu's own default
                  invoker — an icon-only button, already accessible via its own
                  `label` prop above — renders instead of an empty one here.
                -->
                <template v-if="action.label" #invoker="{attributes}">
                  <craft-button type="button" size="small" v-bind="attributes">
                    {{ action.label }}
                    <craft-icon name="chevron-down" slot="suffix"></craft-icon>
                  </craft-button>
                </template>
              </ActionMenu>
              <craft-button
                v-else
                type="button"
                size="small"
                :disabled="bulkActionDisabled(action)"
                @click="performBulkAction(action)"
                >{{ action.label }}</craft-button
              >
            </template>
            <craft-button
              v-if="node.props.deleteUrl && node.props.bulkDeletable"
              type="button"
              variant="danger"
              size="small"
              @click="deleteSelected"
              >{{ t('Delete') }}</craft-button
            >
          </div>
        </template>
        <Text
          v-else
          as="span"
          template="{from} – {to} of {total, plural, =1{# item} other{# items}}"
          :params="{
            from: 1,
            to: filteredRows.length,
            total: filteredRows.length,
          }"
        />
      </div>
    </craft-pane>
  </div>
</template>

<style scoped lang="scss">
  .admin-table-footer {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
    background-color: var(--c-color-neutral-fill-quiet);
    padding: var(--c-spacing-md);
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
  }

  .admin-table-footer__count {
    font-weight: 600;
  }

  .admin-table-footer__actions {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
    margin-inline-start: auto;
  }
</style>
