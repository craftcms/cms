<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {useCraftTable} from '@/common/table/craftTable';
  import {computed, h} from 'vue';
  import {createCraftColumnHelper} from '@/common/table/createCraftColumnHelper';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import CpButtonLink from '@/common/components/CpButtonLink.vue';
  import {
    create,
    destroy,
    edit,
    reorder,
  } from '@actions/Settings/VolumesController';
  import type {SortItem} from '@/common/types';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useInertiaReorder} from '@/common/composables/useInertiaReorder';
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

  const onReorder = useInertiaReorder({url: reorder(), prop: 'volumes'});

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
        action: destroy({volumeId: row.original.id}),
      }),
    ]),
  ]);

  const table = useCraftTable<VolumeData>({
    get data() {
      return props.volumes;
    },
    get columns() {
      return columns.value;
    },
    state: {
      get columnVisibility() {
        return columnVisibility.value;
      },
    },
  });

  useAppLayout({title: props.title});
</script>

<template>
  <LayoutSlot name="content-actions">
    <CpButtonLink :href="create().url" variant="primary" icon="plus">
      {{ t('New volume') }}
    </CpButtonLink>
  </LayoutSlot>

  <div class="@container">
    <AdminTable
      padded
      :table="table"
      :reorderable="true"
      :read-only="readOnly"
      @reorder="onReorder"
    >
      <template #empty-row>
        <craft-empty
          :label="t('No volumes exist yet.')"
          icon="light/files"
        ></craft-empty>
      </template>
    </AdminTable>
  </div>
</template>
