<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {computed, h, nextTick, ref, watch} from 'vue';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import {router} from '@inertiajs/vue3';
  import Empty from '@/common/components/Empty.vue';
  import CpLink from '@/common/components/CpLink.vue';
  import {
    create,
    destroy,
    edit,
    reorder,
  } from '@actions/Settings/VolumesController';
  import type {SortItem} from '@/common/types';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';

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
    readOnly: boolean;
  }>();

  function deleteVolume(volume: VolumeData) {
    if (
      confirm(
        t('Are you sure you want to delete “{name}?', {
          name: volume.name,
        })
      )
    ) {
      router.delete(destroy({volumeId: volume.id}));
    }
  }

  const volumeIds = ref(props.volumes.map((volume) => volume.id));
  const volumes = computed((): VolumeData[] => {
    return (volumeIds.value ?? [])
      .map((id) => props.volumes.find((volume) => volume.id === id))
      .filter((v): v is VolumeData => v !== undefined);
  });

  function handleReorder(startIndex: number, finishIndex: number) {
    const newIds = [...volumeIds.value];
    const [id] = newIds.splice(startIndex, 1);
    if (id === undefined) return;
    newIds.splice(finishIndex, 0, id);
    volumeIds.value = newIds;
  }

  watch(volumeIds, (newValue, oldValue) => {
    // Defer to next tick to avoid issues during drag-and-drop event handling
    nextTick(() => {
      router.post(
        reorder(),
        {
          ids: [...newValue], // Copy to ensure we have the current value
        },
        {
          preserveScroll: true,
          preserveState: true,
          onError: () => {
            volumeIds.value = oldValue;
          },
        }
      );
    });
  });

  const columnHelper = createCraftColumnHelper<VolumeData>();
  const columnVisibility = computed(() => {
    return {
      name: true,
      handle: true,
      actions: !props.readOnly,
    };
  });
  const columns = computed(() => [
    columnHelper.link('name', {
      header: t('Name'),
      props: ({row}) => ({
        href: edit({volumeId: row.original.id}).url,
        inertia: true,
      }),
    }),
    columnHelper.handle('handle'),
    columnHelper.actions(({row}) => [
      h(DeleteButton, {onClick: () => deleteVolume(row.original)}),
    ]),
  ]);

  const table = useVueTable<VolumeData>({
    get data() {
      return volumes.value;
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
    getCoreRowModel: getCoreRowModel<VolumeData>(),
  });

  useAppLayout({title: props.title});
</script>

<template>
  <LayoutSlot name="actions">
    <CpLink
      appearance="button"
      :href="create().url"
      variant="accent"
      icon="plus"
    >
      {{ t('New volume') }}
    </CpLink>
  </LayoutSlot>

  <craft-pane appearance="raised" padding="0" class="cp:@container">
    <AdminTable
      :table="table"
      :reorderable="true"
      :read-only="readOnly"
      @reorder="handleReorder"
    >
      <template #empty-row>
        <Empty :label="t('No volumes exist yet.')" icon="light/files" />
      </template>
    </AdminTable>
  </craft-pane>
</template>
