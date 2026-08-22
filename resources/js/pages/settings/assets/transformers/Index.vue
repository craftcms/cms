<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {router} from '@inertiajs/vue3';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {computed, h, ref} from 'vue';
  import CpLink from '@/common/components/CpLink.vue';
  import Empty from '@/common/components/Empty.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import {
    create,
    destroy,
    edit,
  } from '@actions/Settings/AssetTransformersController';

  interface AssetTransformerData {
    uid: string;
    name: string;
    handle: string;
    driver: string;
    isDefault: boolean;
    canDelete: boolean;
  }

  const props = defineProps<{
    transformers: Array<AssetTransformerData>;
    readOnly: boolean;
  }>();

  function deleteTransformer(transformer: AssetTransformerData): void {
    if (
      confirm(
        t('Are you sure you want to delete the “{name}” Asset Transformer?', {
          name: transformer.name,
        })
      )
    ) {
      router.delete(destroy({handle: transformer.handle}));
    }
  }

  const columnHelper = createCraftColumnHelper<AssetTransformerData>();
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
        inertia: false,
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
              onClick: () => deleteTransformer(row.original),
            }),
          ]
        : []
    ),
  ]);
  const table = useVueTable<AssetTransformerData>({
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
    getCoreRowModel: getCoreRowModel<AssetTransformerData>(),
  });
</script>

<template>
  <LayoutSlot v-if="!readOnly" name="actions">
    <CpLink
      variant="accent"
      appearance="button"
      :href="create().url"
      :inertia="false"
      >{{ t('New Asset Transformer') }}</CpLink
    >
  </LayoutSlot>

  <craft-pane padding="0" appearance="raised">
    <AdminTable :table="table" :reorderable="false">
      <template #empty-row>
        <Empty :label="t('No Asset Transformers exist yet.')" icon="image">
          <CpLink
            v-if="!readOnly"
            appearance="button"
            :href="create().url"
            :inertia="false"
            >{{ t('New Asset Transformer') }}</CpLink
          >
        </Empty>
      </template>
    </AdminTable>
  </craft-pane>
</template>
