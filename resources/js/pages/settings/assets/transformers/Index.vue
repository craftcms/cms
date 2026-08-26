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
  } from '@actions/Settings/AssetTransformersController';

  type AssetTransformerIndexData =
    CraftCms.Cms.Asset.Data.AssetTransformerIndexData;

  const props = defineProps<{
    transformers: Array<AssetTransformerIndexData>;
    readOnly: boolean;
  }>();

  const columnHelper = createCraftColumnHelper<AssetTransformerIndexData>();
  const columnVisibility = computed(() => ({
    name: true,
    handle: true,
    driver: true,
    actions: !props.readOnly,
  }));
  const columns = ref([
    columnHelper.link(
      (transformer) =>
        transformer.isDefault
          ? `${transformer.name} (${t('Default')})`
          : transformer.name,
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
      const transformer = row.original;
      const deleteButton = h(DeleteButton, {
        confirm: t(
          'Are you sure you want to delete the “{name}” Asset Transformer?',
          {
            name: transformer.name,
          }
        ),
        disabled: transformer.deleteDisabledReason !== null,
        onClick: () =>
          router
            .optimistic<{transformers: Array<AssetTransformerIndexData>}>(
              ({transformers}) => ({
                transformers: transformers.filter(
                  ({handle}) => handle !== transformer.handle
                ),
              })
            )
            .delete(destroy({handle: transformer.handle})),
      });

      if (transformer.deleteDisabledReason === null) {
        return [deleteButton];
      }

      const tooltipId = `delete-asset-transformer-${transformer.uid}`;

      return [
        h(
          'span',
          {
            id: tooltipId,
            class: 'inline-flex',
          },
          deleteButton
        ),
        h('craft-tooltip', {for: tooltipId}, transformer.deleteDisabledReason),
      ];
    }),
  ]);
  const table = useVueTable<AssetTransformerIndexData>({
    get data() {
      return props.transformers;
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
    getCoreRowModel: getCoreRowModel<AssetTransformerIndexData>(),
  });
</script>

<template>
  <LayoutSlot v-if="!readOnly" name="actions">
    <CpLink variant="accent" appearance="button" :href="create().url">{{
      t('New Asset Transformer')
    }}</CpLink>
  </LayoutSlot>

  <craft-pane padding="0" appearance="raised">
    <AdminTable :table="table" :reorderable="false" />
  </craft-pane>
</template>
