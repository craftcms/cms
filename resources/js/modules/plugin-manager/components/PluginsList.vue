<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, h, shallowReactive} from 'vue';
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
  import {useAnnouncer} from '@/common/composables/useAnnouncer';

  const props = defineProps<{
    pluginInfo: Record<string, PluginInfo>;
    readOnly?: boolean;
  }>();

  const {announce} = useAnnouncer();
  const errors = shallowReactive<string[]>([]);
  const errorSummary = t('An error occurred.');

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
    if (event.detail?.state === 'loading') {
      errors.length = 0;
    }

    if (event.detail?.state === 'error') {
      errors.push(event.detail.message ?? t('Request failed'));
      announce(errorSummary);
    }

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
    <div v-if="errors.length" class="action-errors">
      <craft-callout
        v-for="(error, index) in errors"
        :key="index"
        variant="danger"
      >
        <strong>{{ errorSummary }}</strong>
        <div
          class="action-error-message"
          role="region"
          tabindex="0"
          :aria-label="errorSummary"
        >
          {{ error }}
        </div>
      </craft-callout>
    </div>
    <AdminTable :table="table" @action:change-state="handleStateChange">
      <template #empty-row>
        <Empty
          icon="plugin"
          :label="t('There are no available plugins.')"
        ></Empty>
      </template>
    </AdminTable>
  </craft-pane>
</template>

<style scoped lang="scss">
  craft-pane,
  .action-errors,
  .action-error-message {
    min-inline-size: 0;
  }

  .action-errors {
    display: grid;
    gap: var(--c-spacing-md);
    padding: var(--c-spacing-md);
    overflow-wrap: anywhere;
  }

  .action-error-message {
    margin-block-start: var(--c-spacing-sm);
    max-block-size: min(200px, 40dvh);
    overflow: auto;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    user-select: text;
  }
</style>
