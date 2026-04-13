<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import AdminTable from '@/components/AdminTable/AdminTable.vue';
  import {
    createColumnHelper,
    getCoreRowModel,
    useVueTable,
  } from '@tanstack/vue-table';
  import {computed, h, ref} from 'vue';
  import Empty from '@/components/Empty.vue';
  import CpLink from '@/components/CpLink.vue';
  import {create, destroy, edit} from '@actions/Settings/FilesystemsController';
  import IndexLayout from '@/layout/IndexLayout.vue';
  import VarDump from '@/components/VarDump.vue';
  import {createCraftColumnHelper} from '@/components/AdminTable/createCraftColumnHelper';
  import DeleteButton from '@/components/AdminTable/DeleteButton.vue';
  import {router} from '@inertiajs/vue3';
  import AppLayout from '@/layout/AppLayout.vue';
  import Pane from '@/components/Pane.vue';

  interface FileSystemData {
    name: string;
    handle: string;
    oldHandle: any;
    hasUrls: boolean;
    url: string;
    uid?: string;
    missing: boolean;
    rootUrl: string;
    type: string;
    id: number;
    dateCreated: string;
    dateUpdated: string;
    settingsHtml: string;
    rootPath: string;
    path: string;
  }

  const props = defineProps<{
    filesystems: Array<FileSystemData>;
    readOnly: boolean;
  }>();

  function deleteFs(fs: FileSystemData) {
    console.log({fs});
    if (
      confirm(
        t('Are you sure you want to delete “{name}”', {
          name: fs.name,
        })
      )
    ) {
      router.delete(destroy(fs.handle));
    }
  }

  const columnHelper = createCraftColumnHelper<FileSystemData>();

  const columnVisibility = computed(() => {
    return {
      name: true,
      handle: true,
      type: true,
      actions: !props.readOnly,
    };
  });
  const columns = ref([
    columnHelper.link('name', {
      header: t('Name'),
      props: ({row}) => ({
        href: edit['/admin/settings/filesystems/{handle}/edit']({
          handle: row.original.handle,
        }).url,
        inertia: false,
      }),
    }),
    columnHelper.handle('handle'),
    columnHelper.accessor('type', {
      header: t('Type'),
      cell: ({row, getValue}) => {
        if (row.original.missing) {
          return h('span', {class: 'c-color-error'}, getValue());
        }
        return getValue();
      },
    }),
    columnHelper.actions(({row}) => [
      h(DeleteButton, {
        onClick: () => deleteFs(row.original),
      }),
    ]),
  ]);
  const table = useVueTable<FileSystemData>({
    get data() {
      return props.filesystems;
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
    getCoreRowModel: getCoreRowModel<FileSystemData>(),
  });
</script>

<template>
  <AppLayout>
    <template #actions>
      <CpLink
        variant="primary"
        appearance="button"
        :href="create().url"
        :inertia="false"
        >{{ t('New filesystem') }}</CpLink
      >
    </template>

    <Pane :padding="0" appearance="raised">
      <AdminTable :table="table" :reorderable="false">
        <template #empty-row>
          <Empty
            :label="t('No filesystems exist yet.')"
            icon="light/folder-open"
          >
            <CpLink appearance="button" :href="create().url" :inertia="false">{{
              t('New filesystem')
            }}</CpLink>
          </Empty>
        </template>
      </AdminTable>
    </Pane>
  </AppLayout>
</template>

<style scoped lang="scss"></style>
