<script setup lang="ts">
  import {capitalize, t} from '@craftcms/cp';
  import IndexLayout from '@/common/layouts/IndexLayout.vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import {computed, h, ref} from 'vue';
  import CpLink from '@/common/components/CpLink.vue';
  import {
    create,
    destroy,
    edit,
    index as imageTransformsIndex,
  } from '@actions/Settings/ImageTransformsController';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import Empty from '@/common/components/Empty.vue';
  import {router} from '@inertiajs/vue3';
  import {index} from '@actions/Settings/VolumesController';

  type ExistingImageTransform = Omit<
    CraftCms.Cms.Image.Data.ImageTransform,
    'id'
  > & {
    id: number;
    uid: string;
    handle: string;
    name: string;
  };

  function deleteTransform(transform: ExistingImageTransform) {
    if (
      confirm(
        t('Are you sure you want to delete the “{name}” transform?', {
          name: transform.name,
        })
      )
    ) {
      router
        .optimistic<{transforms: Array<ExistingImageTransform>}>((props) => ({
          transforms: props.transforms.filter(({id}) => id !== transform.id),
        }))
        .delete(destroy({transformId: transform.id}), {
          preserveScroll: true,
        });
    }
  }

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
        onClick: () => deleteTransform(row.original),
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

  type NavItem = {
    label: string;
    url: string;
    active?: boolean;
    inertia?: boolean;
  };
  const navItems = computed((): Record<string, NavItem> => {
    return {
      volumes: {label: t('Volumes'), url: index().url, active: false},
      transforms: {
        label: t('Image Transforms'),
        url: imageTransformsIndex().url,
        active: true,
      },
    };
  });
</script>

<template>
  <IndexLayout>
    <template #actions>
      <CpLink
        appearance="button"
        :href="create().url"
        variant="accent"
        icon="plus"
        >{{ t('New image transform') }}</CpLink
      >
    </template>

    <template #interior-nav>
      <craft-nav-list>
        <template v-for="(item, id) in navItems" :key="id">
          <CpLink
            as="craft-nav-item"
            :active="item.active ?? false"
            :href="item.url"
            block
            flush
            :inertia="true"
          >
            {{ item.label }}
          </CpLink>
        </template>
      </craft-nav-list>
    </template>
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
  </IndexLayout>
</template>

<style scoped lang="scss"></style>
