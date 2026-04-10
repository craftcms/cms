<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import IndexLayout from '@/layout/IndexLayout.vue';
  import AdminTable from '@/components/AdminTable/AdminTable.vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import VarDump from '@/components/VarDump.vue';
  import {computed, h} from 'vue';
  import {createCraftColumnHelper} from '@/components/AdminTable/createCraftColumnHelper';
  import DeleteButton from '@/components/AdminTable/DeleteButton.vue';
  import {router} from '@inertiajs/vue3';
  import Empty from '@/components/Empty.vue';
  import CpLink from '@/components/CpLink.vue';
  import {
    create,
    destroy,
    edit,
    index,
  } from '@actions/Settings/VolumesController';
  import {index as imageTransformsIndex} from '@actions/Settings/ImageTransformsController';
  import {useServerSort} from '@/composables/useServerSort';
  import type {SortItem} from '@/types';

  interface VolumeData {
    id: number;
    name: string;
    handle: string;
    titleTranslationMethod: {
      name: string;
      value: string;
    };
    titleTranslationKeyFormat: null;
    altTranslationMethod: {
      name: string;
      value: string;
    };
    altTranslationKeyFormat: null;
    sortOrder: number;
    fieldLayoutId: number;
    uid: string;
    fsHandle: string;
    transformFsHandle: null;
    subpath: string;
    transformSubpath: string;
    idAttribute: string | null;
  }

  const props = defineProps<{
    title: string;
    volumes: Array<VolumeData>;
    sort: Array<SortItem>;
  }>();

  function deleteVolume(volume: VolumeData) {
    if (
      confirm(
        t('Are you sure you want to delete “{name}?', {
          name: volume.name,
        })
      )
    ) {
      router.delete(destroy(volume.id));
    }
  }

  const columnHelper = createCraftColumnHelper<VolumeData>();
  const columns = computed(() => [
    columnHelper.link('name', {
      header: t('Name'),
      props: ({row}) => ({
        href: edit(row.original.id).url,
        inertia: false,
      }),
    }),
    columnHelper.handle('handle'),
    columnHelper.actions(({row}) => [
      h(DeleteButton, {onClick: () => deleteVolume(row.original)}),
    ]),
  ]);

  const {sortingState, sortingConfig} = useServerSort({
    initialState: props.sort,
    onChange: ({query}) => {
      router.visit(
        index({
          query,
        }),
        {
          only: ['data', 'sort'],
          preserveScroll: true,
        }
      );
    },
  });

  const table = useVueTable<VolumeData>({
    get data() {
      return props.volumes;
    },
    get columns() {
      return columns.value;
    },
    state: {
      get sorting() {
        return sortingState.value;
      },
    },
    ...sortingConfig,
    getCoreRowModel: getCoreRowModel<VolumeData>(),
  });

  const navItems = computed(() => {
    return {
      volumes: {label: t('Volumes'), url: index().url},
      transforms: {
        label: t('Image Transforms'),
        url: imageTransformsIndex().url,
        inertia: false,
      },
    };
  });
</script>

<template>
  <IndexLayout :title="title">
    <template #actions>
      <CpLink
        appearance="button"
        :href="create().url"
        variant="primary"
        :inertia="false"
        icon="plus"
      >
        {{ t('New volume') }}
      </CpLink>
    </template>

    <template #interior-nav>
      <craft-nav-list>
        <template v-for="(item, id) in navItems" :key="id">
          <CpLink
            as="craft-nav-item"
            :active="true"
            :href="item.url"
            block
            flush
            :inertia="item.inertia ?? true"
          >
            {{ item.label }}
          </CpLink>
        </template>
      </craft-nav-list>
    </template>
    <AdminTable :table="table" :reorderable="false">
      <template #empty-row>
        <Empty :label="t('No volumes exist yet.')" icon="light/files" />
      </template>
    </AdminTable>
  </IndexLayout>
</template>

<style scoped lang="scss"></style>
