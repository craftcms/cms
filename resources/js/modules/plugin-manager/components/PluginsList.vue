<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, h} from 'vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import Empty from '@/common/components/Empty.vue';
  import type {PluginInfo} from '@/modules/plugin-manager/types/plugins';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import PluginDetails from '@/modules/plugin-manager/components/PluginDetails.vue';
  import PluginStatus from '@/modules/plugin-manager/components/PluginStatus.vue';
  import PluginActionMenu from '@/modules/plugin-manager/components/PluginActionMenu.vue';
  import {router} from '@inertiajs/vue3';
  import {index} from '@actions/PluginsController';

  const props = defineProps<{
    pluginInfo: Record<string, PluginInfo>;
    readOnly?: boolean;
  }>();

  const plugins = computed(() => {
    return Object.entries(props.pluginInfo).map(([handle, value]) => {
      return {
        ...value,
        handle,
      };
    });
  });

  const columnHelper = createCraftColumnHelper<PluginInfo>();
  const table = useVueTable({
    state: {
      get columnVisibility() {
        return {
          details: true,
          status: true,
          actions: !props.readOnly,
        };
      },
    },
    get columns() {
      return [
        columnHelper.display({
          id: 'details',
          header: t('Plugin'),
          cell: ({row}) => h(PluginDetails, {plugin: row.original}),
        }),
        columnHelper.display({
          id: 'status',
          header: t('Status'),
          meta: {
            trackSize: 'minmax(280px, 20%)',
          },
          cell: ({row}) =>
            h(PluginStatus, {
              plugin: row.original,
            }),
        }),
        columnHelper.actions(
          ({row}) => [h(PluginActionMenu, {plugin: row.original})],
          {
            meta: {
              trackSize: '60px',
            },
          }
        ),
      ];
    },
    get data() {
      return plugins.value;
    },
    getCoreRowModel: getCoreRowModel<PluginInfo>(),
  });

  /**
   * Maybe a little heavy-handed, this will reload the inertia pluginInfo whenever
   * an http action is successful
   */
  function handleStateChange(event: CustomEvent) {
    if (
      event.detail?.state === 'success' &&
      event.detail?.actionType === 'http'
    ) {
      router.visit(index(), {
        only: ['pluginInfo'],
      });
    }
  }
</script>

<template>
  <craft-pane appearance="raised" padding="0">
    <AdminTable :table="table" @craft-state-change="handleStateChange">
      <template #empty-row>
        <Empty
          icon="plugin"
          :label="t('There are no available plugins.')"
        ></Empty>
      </template>
    </AdminTable>
  </craft-pane>
</template>

<style scoped lang="scss"></style>
