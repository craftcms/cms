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
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import CpLink from '@/common/components/CpLink.vue';
  import Text from '@/common/components/Text.vue';
  import type {
    ActionItemButton,
    ActionItemLink,
    ActionItems,
    PaginationData,
  } from '@/common/types';
  import Empty from '@/common/components/Empty.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import MoveToPageButton from '@/modules/admin-table/components/MoveToPageButton.vue';
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
   * `allowMultiple: false` (default `true`) disables it — whether it stands alone or sits
   * inside a {@link BulkActionMenu} group — whenever more than one row is selected, for an
   * action that only makes sense against one row at a time (its endpoint has no obligation
   * to reflect that itself; nothing stops a request with several ids reaching it some other
   * way, so this is a UI nicety, not a substitute for that endpoint enforcing the same rule
   * server-side).
   */
  interface BulkActionSingle {
    label: string;
    url: string;
    params?: Record<string, unknown>;
    allowMultiple?: boolean;
  }

  /**
   * A labeled group of {@link BulkActionSingle}s within the footer's single "Actions" menu —
   * a heading followed by its items. `label` is optional — omit it to fold the items in
   * unheaded instead, blended into the flat list (matches legacy's own unlabeled gear-icon
   * settings menu for a single, infrequently-needed item; `icon` is accepted for the same PHP
   * config shape but unused here — there's only one shared "Actions" invoker now, not a
   * distinct one per menu).
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
      dataUrl: string | null;
      perPage: number;
      moveToPageUrl: string | null;
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
      statusActions: BulkActionSingle[];
      searchable: boolean;
      searchPlaceholder: string | null;
    }>;
  }>();

  // Reorder/delete mutate this local copy directly (optimistic UI, matching the
  // legacy Craft.VueAdminTable's behavior) rather than round-tripping through a
  // form refresh for what's otherwise a read-only listing. Upfront mode only —
  // endpoint mode's `pageRows` (below) is a fetch result, not this Node's own
  // initial props, so there's nothing to keep in sync here.
  const rows = ref<TableRow[]>([...props.node.props.rows]);
  watch(
    () => props.node.props.rows,
    (newRows) => {
      rows.value = [...newRows];
    }
  );

  const isEndpointMode = computed(() => !!props.node.props.dataUrl);

  // Endpoint mode (`Table::dataUrl()`): the current page's rows plus the server's own
  // pagination metadata, replacing `rows`/`filteredRows` as this component's data source
  // entirely — search becomes a server param `fetchPage()` sends (see the debounced watcher
  // below), not a client-side filter over an already-loaded set.
  const pageRows = ref<TableRow[]>([]);
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
        per_page: props.node.props.perPage,
        search: search.value || undefined,
      });
      pageRows.value = data.data;
      pagination.value = data.pagination;
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

  // Endpoint mode only — upfront mode's `filteredRows` computed (below) already reacts to
  // `search` instantly, with no request to debounce.
  watchDebounced(
    search,
    () => {
      if (isEndpointMode.value) {
        fetchPage(1);
      }
    },
    {debounce: 300}
  );

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

  /** Whichever of the two modes' own row sets is actually on screen right now. */
  const displayedRows = computed(() =>
    isEndpointMode.value ? pageRows.value : filteredRows.value
  );

  // Endpoint mode reports the server's own real `from`/`to`/`total` (driving `AdminTable`'s
  // — really `BaseElementIndex`'s — built-in pagination footer, see the `useVueTable` config
  // below); upfront mode keeps reporting "every loaded/filtered row is on the one page",
  // exactly as before.
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

  // Keyed by row id (see `getRowId`) rather than row index, so a selection
  // survives the optimistic row-removal a delete does. Gated on `bulkDeletable`
  // (see `Table::deletable()`) or a non-empty `bulkActions`/`statusActions` — any
  // of the three turns selection on; a plain `deleteUrl` alone (no `bulk: true`)
  // wouldn't know what to do with the `ids` array a bulk request posts.
  const rowSelection = ref<RowSelectionState>({});

  const hasBulkFooter = computed(
    () =>
      (!!props.node.props.deleteUrl && props.node.props.bulkDeletable) ||
      props.node.props.bulkActions.length > 0 ||
      props.node.props.statusActions.length > 0
  );

  // A "Move to page…" control only makes sense once there's more than one page to move
  // *to* — mirrors legacy's own `AdminTableMoveToPageHud`, which the within-page
  // drag-and-drop `onReorder()` already offers can't reach on its own. Shown in the
  // selection footer (see the template) only while exactly one row is selected — moving
  // more than one row to the same absolute position isn't a well-defined operation, and
  // matches this codebase's own convention of surfacing uncommon per-selection actions
  // in the footer rather than a disclosure control on every row (see the real Entries
  // index's "Move to…" in its own selection-footer Actions menu).
  const showMoveToPage = computed(
    () =>
      isEndpointMode.value &&
      !!props.node.props.moveToPageUrl &&
      (pagination.value?.last_page ?? 0) > 1
  );

  // Endpoint mode's own pagination state, kept in the shape TanStack's `state.pagination`
  // expects — derived from the last `fetchPage()` response rather than owned locally, since
  // the server is the source of truth for which page is "current" here.
  const paginationState = computed(() => ({
    pageIndex: (pagination.value?.current_page ?? 1) - 1,
    pageSize: props.node.props.perPage,
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
    get manualPagination() {
      return isEndpointMode.value;
    },
    // -1 (TanStack's own "unknown" sentinel) outside endpoint mode — harmless, since nothing
    // reads it there (`AdminTable`/`BaseElementIndex` only show pagination controls once
    // `getPageCount() > 1`, and upfront mode never sets `total`/`from`/`to` to make that
    // branch relevant either).
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
        // The optimistic reorder above never actually took server-side — put the rows
        // back the way they were rather than leaving the UI showing an order that
        // silently failed to save.
        rows.value = previous;
        Craft.cp?.displayError?.(
          props.node.props.reorderFailMessage ?? t('Couldn’t reorder.')
        );
      });
  }

  /**
   * Endpoint mode's own reorder: `startIndex`/`finishIndex` are only positions *within the
   * currently loaded page* (there's no full `ids` list to post — every other page's rows
   * were never fetched), so this posts the moved row's new *absolute* position across the
   * whole dataset instead — `Table::reorderable()`'s same URL, a different payload shape,
   * mirroring the established `id`/`ids` dual-payload precedent `deletable()` already uses.
   */
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
      (pagination.value.current_page - 1) * props.node.props.perPage +
      finishIndex;

    actionClient
      .post(props.node.props.reorderUrl!, {id: moved.id, toPosition})
      .then(() => {
        Craft.cp?.displayNotice?.(
          props.node.props.reorderSuccessMessage ?? t('Order updated.')
        );
        // Re-fetches rather than trusting the optimistic splice above: a cross-page shift
        // can change which rows belong on *this* page at all (the last row bumped off the
        // end into the next one, say), which a same-page splice alone can't reflect.
        fetchPage(pagination.value!.current_page);
      })
      .catch(() => {
        pageRows.value = previous;
        Craft.cp?.displayError?.(
          props.node.props.reorderFailMessage ?? t('Couldn’t reorder.')
        );
      });
  }

  /** "Move to page…" (see `MoveToPageButton.vue`) — always moves the selected row to the
   *  chosen page's first slot, matching legacy's own `AdminTableMoveToPageHud`/
   *  `AdminTable::moveToPage()`. Posts the plain `page` number, not a computed
   *  `toPosition` — the endpoint owns that arithmetic (`(page - 1) * perPage`), the same
   *  `Table::moveToPageUrl()` documents. Only ever called with exactly one id selected
   *  (see the footer template), so clears the selection on success the same way
   *  {@link deleteSelected} does. */
  function moveToPage(id: TableRow['id'], page: number): void {
    actionClient
      .post(props.node.props.moveToPageUrl!, {id, page})
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

  /** Adapts a `BulkActionSingle` to `ActionMenu`'s item shape. */
  function bulkActionToItem(action: BulkActionSingle): ActionItemButton {
    return {
      type: 'button',
      label: action.label,
      disabled: bulkActionDisabled(action),
      onClick: () => performBulkAction(action),
    };
  }

  /**
   * `MoveToPageButton` embedded as a single "Move to page…" item inside the
   * consolidated Actions menu (below) via `ActionMenu`'s `{type: 'display'}`
   * escape hatch — matching the real Entries index's own selection-footer
   * convention (an "Actions" menu holding uncommon per-selection actions,
   * "Move to…" among them) instead of a standalone footer button or, worse, a
   * disclosure control on every row. `display` items render through an
   * isolated `vueRender()` tree of their own (see `ActionMenu.vue`'s
   * `displayToNode()`) rather than Vue's normal patch path — the same
   * mechanism that makes `craft-popover` safe to nest inside an already-open
   * `craft-action-menu` at all (a full unmount/remount on every reveal, never
   * a patch of DOM the overlay has relocated). `is` must be a plain
   * `Component` with no prop channel of its own (`displayToNode()` calls
   * `createVNode(component)` with none), so this closure-based wrapper reads
   * `pagination`/`loading`/`selectedIds`/`moveToPage` directly from the
   * surrounding scope instead of `MoveToPageButton`'s normal props — a
   * completely ordinary Vue closure-component, not a new reusable one.
   */
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

  /**
   * Everything the selection footer's single "Actions" menu offers: each
   * `Table::bulkActions()` entry (a standalone button, or a labeled group for
   * a `BulkActionMenu` — `ActionItemGroup` is one level deep, which is exactly
   * what a `BulkActionMenu`'s own flat `items` list needs), then "Move to
   * page…", then "Delete" last and destructive-styled — the same ordering the
   * real Entries index uses for its own equivalent items.
   */
  const footerActionItems = computed((): ActionItems => {
    const items: ActionItems = props.node.props.bulkActions.map((action) =>
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
        type: 'button',
        label: t('Delete'),
        variant: 'danger',
        onClick: deleteSelected,
      });
    }

    return items;
  });

  /**
   * `Table::statusActions()`'s own items, adapted the same way a `bulkActions()`
   * entry is — but rendered as their own dedicated "Set status" button (see the
   * template), never folded into `footerActionItems`'s "Actions" menu. Mirrors
   * `BulkActionsBar.vue`'s real `SET_STATUS_KEY` pull-out exactly: Set Status
   * is the most-reached-for action, so it always gets its own button alongside
   * "Actions" rather than living inside it.
   */
  const statusActionItems = computed((): ActionItemButton[] =>
    props.node.props.statusActions.map(bulkActionToItem)
  );

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
    <LayoutSlot v-if="node.props.createUrl" name="content-actions">
      <CpLink
        variant="accent"
        appearance="button"
        :href="node.props.createUrl"
        :inertia="false"
        >{{ node.props.createLabel }}</CpLink
      >
    </LayoutSlot>
    <LayoutSlot v-else-if="createMenuActions.length" name="content-actions">
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
        :loading="loading"
        :from="footerFrom"
        :to="footerTo"
        :total="footerTotal"
        @reorder="onReorder"
      >
        <template #empty-row>
          <Empty
            v-if="!loading"
            :label="
              search
                ? t('No results for “{search}”.', {search})
                : (node.props.emptyMessage ?? t('Nothing to show.'))
            "
          />
        </template>
      </AdminTable>

      <!--
        `from`/`to`/`total` (above) are what turn on `BaseElementIndex`'s own built-in footer
        — real "X – Y of Z items" text always, plus real prev/next pagination controls once
        `pageCount > 1` (endpoint mode only; `pageCount` is -1 outside it, see the
        `useVueTable` config above) — no new pagination UI needed here, in either mode. Its
        *other* footer concern, the selection/bulk-action bar (`BulkActionsBar`), stays inert
        regardless (it only renders once `actions.length > 0`, and `AdminTable`/
        `BaseElementIndex` are never given any here): `PerformElementActionController`, what it
        posts to, resolves a real `ElementQuery` and a registered `ElementAction`, and there's
        no fitting these plain PHP-object rows into that pipeline without building a parallel
        Element-like system, wildly out of proportion to the need. So this component still
        carries its own small, self-contained selection/bulk-actions footer below (same
        background/border/copy as the real one) — shown only once something's selected,
        stacking beneath the real footer above it rather than replacing any part of it.
      -->
      <div v-if="selectedIds.length" class="admin-table-footer">
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
        <div
          v-if="statusActionItems.length || footerActionItems.length"
          class="admin-table-footer__actions"
        >
          <!--
            Its own dedicated button, matching Entries' own `BulkActionsBar.vue` —
            Set Status always stays separate from "Actions" below, since it's the
            most-reached-for action, not because it's architecturally different.
          -->
          <ActionMenu
            v-if="statusActionItems.length"
            :actions="statusActionItems"
            :label="t('Set status')"
          >
            <template #invoker="{attributes}">
              <craft-button type="button" size="small" v-bind="attributes">
                {{ t('Set status') }}
                <craft-icon name="chevron-down" slot="suffix"></craft-icon>
              </craft-button>
            </template>
          </ActionMenu>
          <ActionMenu
            v-if="footerActionItems.length"
            :actions="footerActionItems"
            :label="t('Actions')"
          >
            <template #invoker="{attributes}">
              <craft-button type="button" size="small" v-bind="attributes">
                {{ t('Actions') }}
                <craft-icon name="chevron-down" slot="suffix"></craft-icon>
              </craft-button>
            </template>
          </ActionMenu>
        </div>
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
