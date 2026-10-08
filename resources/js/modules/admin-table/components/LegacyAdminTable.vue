<script setup lang="ts">
  /**
   * A `Craft.VueAdminTable` rendered with the current table.
   *
   * Craft 5 plugins build these from a settings object — columns as
   * `{name, title, callback}`, rows as plain serialized data — and the shim
   * translates that into the TanStack table the new `AdminTable` takes. Only
   * the static-data shape reaches here; see `DELEGATED_OPTIONS` for what goes
   * to the legacy implementation instead.
   *
   * @see resources/js/legacy-admin-table.ts
   */
  import {useCraftTable} from '@/modules/admin-table/craftTable';
  import {computed, h, ref, watch} from 'vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import type {
    LegacyAdminTableColumn,
    LegacyAdminTableRow,
  } from '@/modules/admin-table/types/legacy';

  const props = defineProps<{
    columns: Array<LegacyAdminTableColumn>;
    tableData: Array<LegacyAdminTableRow>;
    emptyMessage?: string;
    deleteAction?: string | null;
    deleteConfirmationMessage?: string | null;
    deleteSuccessMessage?: string | null;
    deleteFailMessage?: string | null;
  }>();

  /**
   * Rows are held locally so a delete can take one out without a round trip —
   * the legacy table did the same, and with static data there's nothing to
   * re-fetch from.
   */
  const rows = ref<Array<LegacyAdminTableRow>>([...props.tableData]);

  watch(
    () => props.tableData,
    (value) => {
      rows.value = [...value];
    }
  );

  /** Re-seeds from the settings the table was built with. */
  function reset(): void {
    rows.value = [...props.tableData];
  }

  defineExpose({reset});

  const columnHelper = createCraftColumnHelper<LegacyAdminTableRow>();

  /**
   * Craft 5 rendered cell values as HTML — a column's `callback` returned
   * markup, and plenty return it without one (a status pill, say). `innerHTML`
   * rather than the `html` column helper on purpose: that helper compiles the
   * value as a Vue template, which turns anything unexpected in a plugin's
   * markup into a render error that takes the surrounding tree with it.
   */
  function cellHtml(
    column: LegacyAdminTableColumn,
    row: LegacyAdminTableRow
  ): string {
    const value = row[column.name];

    if (column.callback) {
      return String(column.callback(value, row) ?? '');
    }

    return String(value ?? '');
  }

  async function remove(row: LegacyAdminTableRow): Promise<void> {
    if (!props.deleteAction) {
      return;
    }

    try {
      await window.Craft.sendActionRequest('POST', props.deleteAction, {
        data: {id: row.id},
      });

      rows.value = rows.value.filter((candidate) => candidate !== row);

      if (props.deleteSuccessMessage) {
        window.Craft.cp?.displayNotice?.(props.deleteSuccessMessage);
      }
    } catch {
      window.Craft.cp?.displayError?.(props.deleteFailMessage ?? undefined);
    }
  }

  const columns = computed(() => {
    const defined = props.columns.map((column) =>
      columnHelper.accessor((row) => row[column.name], {
        id: column.name,
        header: column.title ?? '',
        cell: (info) =>
          h('div', {innerHTML: cellHtml(column, info.row.original)}),
        meta: {wrap: true},
      })
    );

    if (!props.deleteAction) {
      return defined;
    }

    return [
      ...defined,
      // `_showDelete: false` is how the legacy table hid the button per row —
      // Shopify's sync utility uses it to protect an in-flight operation.
      columnHelper.actions(({row}) =>
        row.original._showDelete === false
          ? []
          : [
              h(DeleteButton, {
                confirm: props.deleteConfirmationMessage ?? undefined,
                onClick: () => remove(row.original),
              }),
            ]
      ),
    ];
  });

  const table = useCraftTable({
    get columns() {
      return columns.value;
    },

    get data() {
      return rows.value;
    },
  });
</script>

<template>
  <AdminTable
    layout="auto"
    :table="table"
    :from="rows.length ? 1 : 0"
    :to="rows.length"
    :total="rows.length"
    :reorderable="false"
  >
    <template #empty-row>
      <craft-empty :label="emptyMessage"></craft-empty>
    </template>
  </AdminTable>
</template>
