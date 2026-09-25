<script setup lang="ts">
  import {capitalize, t} from '@craftcms/ui';
  import {useTable} from '@tanstack/vue-table';
  import {craftTableFeatures} from '@/modules/admin-table/tableFeatures';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import {h, ref} from 'vue';
  import CpButtonLink from '@/common/components/CpButtonLink.vue';
  import {
    create,
    destroy,
    edit,
  } from '@actions/Settings/ImageTransformsController';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import {router} from '@inertiajs/vue3';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import CpContainer from '@/common/components/CpContainer.vue';

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
  const columns = ref(
    columnHelper.columns([
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
          confirm: t(
            'Are you sure you want to delete the “{name}” transform?',
            {
              name: row.original.name,
            }
          ),
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
    ])
  );
  const table = useTable({
    features: craftTableFeatures,
    get data() {
      return props.transforms;
    },
    get columns() {
      return columns.value;
    },
    enableSorting: false,
    state: {
      get columnVisibility() {
        return columnVisibility.value;
      },
    },
  });
</script>

<template>
  <LayoutSlot name="content-actions">
    <CpButtonLink :href="create().url" variant="primary" icon="plus">{{
      t('New image transform')
    }}</CpButtonLink>
  </LayoutSlot>

  <CpContainer class="@container">
    <AdminTable :table="table">
      <template #empty-row>
        <craft-empty :label="t('No image transforms exist yet.')" icon="image">
          <CpButtonLink :href="create().url" icon="plus">{{
            t('New image transform')
          }}</CpButtonLink>
        </craft-empty>
      </template>
    </AdminTable>
  </CpContainer>
</template>
