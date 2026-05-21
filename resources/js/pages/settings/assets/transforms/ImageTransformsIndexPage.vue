<script setup lang="ts">
  import {capitalize, t} from '@craftcms/cp';
  import IndexLayout from '@/layout/IndexLayout.vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {createCraftColumnHelper} from '@/components/AdminTable/createCraftColumnHelper';
  import {computed, h, ref} from 'vue';
  import CpLink from '@/components/CpLink.vue';
  import {
    create,
    destroy,
    edit,
    index as imageTransformsIndex,
  } from '@actions/Settings/ImageTransformsController';
  import AdminTable from '@/components/AdminTable/AdminTable.vue';
  import DeleteButton from '@/components/AdminTable/DeleteButton.vue';
  import Empty from '@/components/Empty.vue';
  import {router} from '@inertiajs/vue3';
  import {index} from '@actions/Settings/VolumesController';
  import type {ExistingImageTransform} from '@/pages/settings/assets/transforms/types';

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
        .delete(destroy(transform.id), {
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
        href: edit(row.original.handle).url,
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

  const navItems = computed(() => {
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
        variant="primary"
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
            variant="default"
            icon="plus"
            >{{ t('New image transform') }}</CpLink
          >
        </Empty>
      </template>
    </AdminTable>
  </IndexLayout>
</template>

<style scoped lang="scss"></style>
