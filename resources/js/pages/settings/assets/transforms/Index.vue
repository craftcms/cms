<script setup lang="ts">
  import {capitalize, t} from '@craftcms/ui';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import {h, ref} from 'vue';
  import CpLink from '@/common/components/CpLink.vue';
  import {
    create,
    destroy,
    edit,
  } from '@actions/Settings/ImageTransformsController';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import Empty from '@/common/components/Empty.vue';
  import {router} from '@inertiajs/vue3';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';

  type ExistingImageTransform = Omit<
    CraftCms.Cms.Image.Data.ImageTransform,
    'id'
  > & {
    id: number;
    uid: string;
    handle: string;
    name: string;
  };

  const props = defineProps<{
    transforms: Array<ExistingImageTransform>;
  }>();

  const columnVisibility = ref({
    name: true,
    handle: true,
  });
  const columnHelper = createCraftColumnHelper<ExistingImageTransform>();
  const columns = ref([
    columnHelper.link('name', {
      header: t('Name'),
      props: ({row}) => ({
        href: edit({transformHandle: row.original.handle}).url,
        inertia: true,
      }),
    }),
    columnHelper.handle('handle'),
    columnHelper.accessor('mode', {
      header: t('Mode'),
    }),
    columnHelper.display({
      id: 'dimensions',
      header: t('Dimensions'),
      cell: ({row}) =>
        `${row.original.width ?? 'Auto'} x ${row.original.height ?? 'Auto'}`,
    }),

    columnHelper.accessor('interlace', {
      header: t('Interlace'),
      cell: ({row}) =>
        row.original.interlace ? capitalize(row.original.interlace) : 'None',
    }),

    columnHelper.accessor('format', {
      header: t('Format'),
      cell: ({row}) =>
        row.original.format ? capitalize(row.original.format) : 'Auto',
    }),
    columnHelper.actions(({row}) => [
      h(DeleteButton, {
        confirm: t('Are you sure you want to delete the “{name}” transform?', {
          name: row.original.name,
        }),
        onClick: () =>
          router
            .optimistic<{transforms: Array<ExistingImageTransform>}>(
              (props) => ({
                transforms: props.transforms.filter(
                  ({id}) => id !== row.original.id
                ),
              })
            )
            .delete(destroy({transformId: row.original.id}), {
              preserveScroll: true,
            }),
      }),
    ]),
  ]);
  const table = useVueTable({
    get data() {
      return props.transforms;
    },
    get columns() {
      return columns.value;
    },
    enableSorting: false,
    getCoreRowModel: getCoreRowModel<ExistingImageTransform>(),
    state: {
      get columnVisibility() {
        return columnVisibility.value;
      },
    },
  });
</script>

<template>
  <LayoutSlot name="actions">
    <CpLink
      appearance="button"
      :href="create().url"
      variant="accent"
      icon="plus"
      >{{ t('New image transform') }}</CpLink
    >
  </LayoutSlot>

  <craft-pane appearance="raised" padding="0" class="@container">
    <AdminTable :table="table">
      <template #empty-row>
        <Empty :label="t('No image transforms exist yet.')" icon="image">
          <CpLink
            appearance="button"
            :href="create().url"
            variant="neutral"
            icon="plus"
            >{{ t('New image transform') }}</CpLink
          >
        </Empty>
      </template>
    </AdminTable>
  </craft-pane>
</template>
