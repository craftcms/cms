<script setup lang="ts">
  import {actionClient, t} from '@craftcms/ui';
  import {router} from '@inertiajs/vue3';
  import {
    type ColumnDef,
    getCoreRowModel,
    useVueTable,
  } from '@tanstack/vue-table';
  import {computed, h, ref, watch} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import CpLink from '@/common/components/CpLink.vue';
  import type {ActionItemLink} from '@/common/types';
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

  type TableRow = Record<string, TableCellValue> & {
    /** Required when the table is reorderable and/or deletable. */
    id?: string | number;
    /** Opts a single row out of an otherwise-deletable table. */
    _deletable?: boolean;
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
      deleteUrl: string | null;
      deleteConfirmMessage: string | null;
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
      (column) =>
        columnHelper.accessor(column.key, {
          header: column.label,
          cell: ({getValue}) => {
            const value = getValue();

            if (Array.isArray(value)) {
              return value.flatMap((link, index) => [
                index > 0 ? ', ' : '',
                renderLink(link),
              ]);
            }

            if (value !== null && typeof value === 'object') {
              if (isMenu(value)) return renderMenu(value);
              if (isIcon(value)) return renderIcon(value);
              if (isHtml(value)) return renderHtmlCell(value);
              return renderLink(value);
            }

            return value ?? '';
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

  const table = useVueTable({
    get data() {
      return rows.value;
    },
    get columns() {
      return columns.value;
    },
    enableSorting: false,
    getCoreRowModel: getCoreRowModel(),
  });

  function onReorder(startIndex: number, finishIndex: number): void {
    const reordered = [...rows.value];
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
      .then(() => refreshForm());
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
      <AdminTable
        :table="table"
        :reorderable="!!node.props.reorderUrl"
        @reorder="onReorder"
      >
        <template #empty-row>
          <Empty :label="node.props.emptyMessage ?? t('Nothing to show.')" />
        </template>
      </AdminTable>
    </craft-pane>
  </div>
</template>
