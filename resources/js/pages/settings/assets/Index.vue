<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {useTable} from '@tanstack/vue-table';
  import {
    craftTableFeatures,
    type CraftTableFeatures,
  } from '@/modules/admin-table/tableFeatures';
  import {computed, h, nextTick, ref, watch} from 'vue';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import {router} from '@inertiajs/vue3';
  import CpButtonLink from '@/common/components/CpButtonLink.vue';
  import {
    create,
    destroy,
    edit,
    reorder,
  } from '@actions/Settings/VolumesController';
  import type {SortItem} from '@/common/types';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import CpContainer from '@/common/components/CpContainer.vue';

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
      h(DeleteButton, {
        confirm: t('Are you sure you want to delete “{name}?', {
          name: row.original.name,
        }),
        onClick: () => router.delete(destroy({volumeId: row.original.id})),
      }),
    ]),
  ]);

  const table = useTable<CraftTableFeatures, VolumeData>({
    features: craftTableFeatures,
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
  });

  useAppLayout({title: props.title});
</script>

<template>
  <LayoutSlot name="content-actions">
    <CpButtonLink :href="create().url" variant="primary" icon="plus">
      {{ t('New volume') }}
    </CpButtonLink>
  </LayoutSlot>

  <CpContainer class="@container">
    <AdminTable
      :table="table"
      :reorderable="true"
      :read-only="readOnly"
      @reorder="handleReorder"
    >
      <template #empty-row>
        <craft-empty
          :label="t('No volumes exist yet.')"
          icon="light/files"
        ></craft-empty>
      </template>
    </AdminTable>
  </CpContainer>
</template>
