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

  type AssetProcessorIndexData =
    CraftCms.Cms.Asset.Data.AssetProcessorIndexData;

  const props = defineProps<{
    processors: Array<AssetProcessorIndexData>;
    readOnly: boolean;
  }>();

  const columnHelper = createCraftColumnHelper<AssetProcessorIndexData>();
  const columnVisibility = computed(() => ({
    name: true,
    handle: true,
    driver: true,
    actions: !props.readOnly,
  }));
  const columns = ref([
    columnHelper.link(
      (processor) =>
        processor.isDefault
          ? `${processor.name} (${t('Default')})`
          : processor.name,
      {
        id: 'name',
        header: t('Name'),
        props: ({row}) => ({
          href: edit({handle: row.original.handle}).url,
        }),
      }
    ),
    columnHelper.handle('handle'),
    columnHelper.accessor('driver', {
      header: t('Driver'),
    }),
    columnHelper.actions(({row}) => {
      const processor = row.original;
      const deleteButton = h(DeleteButton, {
        confirm: t(
          'Are you sure you want to delete the “{name}” Asset Processor?',
          {
            name: processor.name,
          }
        ),
        disabled: processor.deleteDisabledReason !== null,
        onClick: () =>
          router
            .optimistic<{processors: Array<AssetProcessorIndexData>}>(
              ({processors}) => ({
                processors: processors.filter(
                  ({handle}) => handle !== processor.handle
                ),
              })
            )
            .delete(destroy({handle: processor.handle})),
      });

      if (processor.deleteDisabledReason === null) {
        return [deleteButton];
      }

      const tooltipId = `delete-asset-processor-${processor.uid}`;

      return [
        h(
          'span',
          {
            id: tooltipId,
            class: 'inline-flex',
            tabindex: 0,
          },
          deleteButton
        ),
        h('craft-tooltip', {for: tooltipId}, processor.deleteDisabledReason),
      ];
    }),
  ]);
  const table = useVueTable<AssetProcessorIndexData>({
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
    getCoreRowModel: getCoreRowModel<AssetProcessorIndexData>(),
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
