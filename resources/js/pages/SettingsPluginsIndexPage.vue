<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {computed, h} from 'vue';
  import AppLayout from '@/layout/AppLayout.vue';
  import Empty from '@/components/Empty.vue';
  import Pane from '@/components/Pane.vue';
  import {FlexRender, getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {createCraftColumnHelper} from '@/components/AdminTable/createCraftColumnHelper';
  import type {PluginInfo} from '@/types/plugins';
  import PluginStatus from '@/components/Plugins/PluginStatus.vue';
  import PluginDetails from '@/components/Plugins/PluginDetails.vue';
  import AdminTable from '@/components/AdminTable/AdminTable.vue';
  import PluginActionMenu from '@/components/Plugins/PluginActionMenu.vue';
  import CpLink from '@/components/CpLink.vue';
  import {router} from '@inertiajs/vue3';
  import {index} from '@actions/PluginsController';

  const props = withDefaults(
    defineProps<{
      pluginInfo: Record<string, PluginInfo>;
      readOnly?: boolean;
    }>(),
    {pluginInfo: () => ({}), readOnly: false}
  );

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
   * Maybe a little heavy handed, this will reload the inertia pluginInfo whenever
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
  <AppLayout>
    <Pane appearance="raised" :padding="0">
      <AdminTable :table="table" @action:change-state="handleStateChange">
        <template #empty-row>
          <Empty icon="plugin" :label="t('There are no available plugins.')">
            <CpLink appearance="button" :inertia="false" :href="index().url">{{
              t('Browse the store')
            }}</CpLink>
          </Empty>
        </template>
      </AdminTable>
    </Pane>
  </AppLayout>
</template>

<style scoped lang="scss"></style>
