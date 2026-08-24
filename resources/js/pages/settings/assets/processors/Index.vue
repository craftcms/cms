<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {router} from '@inertiajs/vue3';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {computed, h, ref} from 'vue';
  import CpLink from '@/common/components/CpLink.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import {
    create,
    destroy,
    edit,
  } from '@actions/Settings/AssetProcessorsController';

  interface AssetProcessorData {
    uid: string;
    name: string;
    handle: string;
    driver: string;
    isDefault: boolean;
    canDelete: boolean;
  }

  const props = defineProps<{
    processors: Array<AssetProcessorData>;
    readOnly: boolean;
  }>();

  function deleteProcessor(processor: AssetProcessorData): void {
    if (
      confirm(
        t('Are you sure you want to delete the “{name}” Asset Processor?', {
          name: processor.name,
        })
      )
    ) {
      router.delete(destroy({handle: processor.handle}));
    }
  }

  const columnHelper = createCraftColumnHelper<AssetProcessorData>();
  const columnVisibility = computed(() => ({
    name: true,
    handle: true,
    driver: true,
    actions: !props.readOnly,
  }));
  const columns = ref([
    columnHelper.link('name', {
      header: t('Name'),
      cell: ({row, getValue}) =>
        row.original.isDefault
          ? `${String(getValue())} (${t('Default')})`
          : getValue(),
      props: ({row}) => ({
        href: edit({handle: row.original.handle}).url,
      }),
    }),
    columnHelper.handle('handle'),
    columnHelper.accessor('driver', {
      header: t('Driver'),
    }),
    columnHelper.actions(({row}) =>
      row.original.canDelete
        ? [
            h(DeleteButton, {
              onClick: () => deleteProcessor(row.original),
            }),
          ]
        : []
    ),
  ]);
  const table = useVueTable<AssetProcessorData>({
    get data() {
      return props.processors;
    },
    get columns() {
      return columns.value;
    },
    state: {
      get columnVisibility() {
        return columnVisibility.value;
      },
    },
    enableSorting: false,
    getCoreRowModel: getCoreRowModel<AssetProcessorData>(),
  });
</script>

<template>
  <LayoutSlot v-if="!readOnly" name="actions">
    <CpLink variant="accent" appearance="button" :href="create().url">{{
      t('New Asset Processor')
    }}</CpLink>
  </LayoutSlot>

  <craft-pane padding="0" appearance="raised">
    <AdminTable :table="table" :reorderable="false" />
  </craft-pane>
</template>
