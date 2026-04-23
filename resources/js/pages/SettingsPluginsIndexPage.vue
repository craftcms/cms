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
</script>

<template>
  <AppLayout>
    <Pane appearance="raised" :padding="0">
      <AdminTable :table="table">
        <template #empty-row>
          <Empty
            icon="plugin"
            :label="t('There are no available plugins.')"
          ></Empty>
        </template>
      </AdminTable>
    </Pane>
  </AppLayout>
</template>

<style scoped lang="scss"></style>
