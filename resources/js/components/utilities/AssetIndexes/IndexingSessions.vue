<script setup lang="ts">
  import AdminTable from '@/components/AdminTable/AdminTable.vue';
  import {
    createColumnHelper,
    getCoreRowModel,
    useVueTable,
  } from '@tanstack/vue-table';
  import {h, ref} from 'vue';
  import {t} from '@craftcms/cp';
  import Badge from '@/components/Badge.vue';
  import SessionProgress from '@/components/utilities/AssetIndexes/SessionProgress.vue';
  import SessionActions from '@/components/utilities/AssetIndexes/SessionActions.vue';
  import {useAssetIndexer} from '@/composables/useAssetIndexer';
  import {type IndexingSession} from '@craftcms/cp/src/services/AssetIndexer.js';
  import ReviewSessionModal from '@/components/utilities/AssetIndexes/ReviewSessionModal.vue';

  const {
    sessionsArray,
    currentSessionId,
    stopSession,
    reviewSessionOverview,
    isReviewOpen,
    closeReview,
    reviewSession,
  } = useAssetIndexer();

  // Table columns
  const columnHelper = createColumnHelper<IndexingSession>();

  const columns = ref([
    columnHelper.accessor('indexedVolumes', {
      header: () => t('Volumes being indexed'),
      cell: ({getValue}) => {
        const raw = getValue();
        const parsed = typeof raw === 'string' ? JSON.parse(raw) : raw;
        const names: Array<string> = Object.values(parsed);
        return h(
          'ul',
          names.map((name) => h('li', name))
        );
      },
    }),
    columnHelper.accessor('dateUpdated', {
      header: () => t('Last update'),
      cell: (info) => new Date(info.getValue().date).toLocaleString(),
    }),
    columnHelper.display({
      id: 'progress',
      header: () => t('Progress'),
      cell: ({row}) =>
        h(SessionProgress, {
          pending:
            !row.original.actionRequired &&
            row.original.id !== currentSessionId.value,
          processedEntries: row.original.processedEntries,
          totalEntries: row.original.totalEntries,
        }),
    }),
    columnHelper.display({
      id: 'status',
      header: () => t('Status'),
      cell: ({row}) => {
        const session = row.original;
        if (session.actionRequired) {
          return h(Badge, {variant: 'warning'}, () => t('Waiting for review'));
        }

        if (session.id === currentSessionId.value) {
          return h(Badge, {variant: 'success'}, () => t('Active'));
        }

        return h(Badge, {variant: 'default'}, () => t('Waiting'));
      },
    }),
    columnHelper.display({
      id: 'actions',
      cell: ({row}) =>
        h(SessionActions, {
          sessionId: row.original.id,
          actionRequired: row.original.actionRequired,
          onStop: (id: number) => stopSession(id),
          onReview: (id: number) => reviewSessionOverview(id),
        }),
    }),
  ]);

  const sessionsTable = useVueTable({
    get data() {
      return sessionsArray.value;
    },
    get columns() {
      return columns.value;
    },
    getRowId: (row) => String(row.id),
    getCoreRowModel: getCoreRowModel<IndexingSession>(),
  });
</script>

<template>
  <AdminTable
    :table="sessionsTable"
    :reorderable="false"
    spacing="relaxed"
    layout="fixed"
  />

  <template v-if="reviewSession">
    <ReviewSessionModal />
  </template>
</template>

<style scoped lang="scss"></style>
