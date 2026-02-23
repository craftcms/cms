<script setup lang="ts">
  import AdminTable from '@/components/common/AdminTable/AdminTable.vue';
  import {
    createColumnHelper,
    getCoreRowModel,
    useVueTable,
  } from '@tanstack/vue-table';
  import {h, ref} from 'vue';
  import {t} from '@craftcms/cp';
  import Badge from '@/components/common/Badge/Badge.vue';
  import SessionProgress from '@/components/utilities/AssetIndexes/SessionProgress.vue';
  import SessionActions from '@/components/utilities/AssetIndexes/SessionActions.vue';
  import {useAssetIndexer} from '@/composables/useAssetIndexer';
  import {type IndexingSession} from '@craftcms/cp/src/services/AssetIndexer.js';
  import ReviewSessionModal from '@/components/utilities/AssetIndexes/ReviewSessionModal.vue';
  import SessionVolumes from '@/components/utilities/AssetIndexes/SessionVolumes.vue';
  import Date from '@/components/common/Date/Date.vue';
  import Pane from '@/components/common/Pane/Pane.vue';

  const {
    sessionsArray,
    currentSessionId,
    stopSession,
    reviewSessionOverview,
    reviewSession,
  } = useAssetIndexer();

  // Table columns
  const columnHelper = createColumnHelper<IndexingSession>();

  const columns = ref([
    columnHelper.accessor('indexedVolumes', {
      header: () => t('Volumes being indexed'),
      cell: ({getValue}) => h(SessionVolumes, {value: getValue()}),
    }),
    columnHelper.accessor('dateUpdated', {
      header: () => t('Last update'),
      cell: ({getValue}) => h(Date, {value: getValue().date}),
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
  <div class="sessions">
    <template v-for="session in sessionsArray">
      <div class="session">
        <div class="session__label">
          <div class="mb-1">
            <strong>{{ t('Volumes being indexed') }}</strong>
          </div>
          <SessionVolumes :value="session.indexedVolumes" />
        </div>

        <div class="session__status">
          <div class="mb-1">
            <strong>{{ t('Status') }}</strong>
          </div>
          <template v-if="session.actionRequired">
            <Badge variant="warning">{{ t('Waiting for review') }}</Badge>
          </template>
          <template v-else-if="session.id === currentSessionId">
            <Badge variant="success">{{ t('Active') }}</Badge>
          </template>
          <template v-else>
            <Badge>{{ t('Waiting') }}</Badge>
          </template>
        </div>

        <div class="session__last-update">
          <div class="mb-1">
            <strong>{{ t('Last update') }}</strong>
          </div>
          <Date :value="session.dateUpdated.date" />
        </div>

        <div class="session__progress">
          <div class="mb-1">
            <strong>{{ t('Progress') }}</strong>
          </div>
          <SessionProgress
            :processed-entries="session.processedEntries"
            :total-entries="session.totalEntries"
            :pending="
              !session.actionRequired && session.id !== currentSessionId
            "
          />
        </div>

        <div class="session__actions">
          <SessionActions
            :session-id="session.id"
            :action-required="session.actionRequired"
            @stop="(id: number) => stopSession(id)"
            @review="(id: number) => reviewSessionOverview(id)"
          />
        </div>
      </div>
    </template>
  </div>

  <template v-if="reviewSession">
    <ReviewSessionModal />
  </template>
</template>

<style scoped lang="scss">
  .sessions {
    display: grid;
    border: 1px solid var(--c-color-neutral-border-subtle);
    border-radius: var(--c-radius-md);
  }

  .session {
    display: grid;
    gap: var(--c-spacing-lg);
    padding: var(--c-spacing-md);
    align-items: center;
    grid-template-areas: 'label' 'status' 'last-update' 'progress' 'actions';

    @container (width >= 420px) {
      gap: var(--c-spacing-md);
      padding: var(--c-spacing-lg);
      grid-template-areas: 'label actions' 'status last-update' 'progress progress';
    }

    @container (width >= 620px) {
      grid-template-areas: 'label label label actions' 'status last-update progress progress';
    }

    @container (width >= 800px) {
      padding: var(--c-spacing-sm) var(--c-spacing-md);
      align-items: start;
      grid-template-areas: 'label last-update status progress actions';
    }
  }

  .session + .session {
    border-block-start: 1px solid var(--c-color-neutral-border-subtle);
  }

  .session__label {
    grid-area: label;
  }

  .session__last-update {
    grid-area: last-update;
    white-space: nowrap;
  }

  .session__status {
    grid-area: status;
    white-space: nowrap;
  }

  .session__progress {
    grid-area: progress;
  }

  .session__actions {
    grid-area: actions;
    margin-block-start: var(--c-spacing-lg);

    @container (width >= 420px) {
      margin-block-start: 0;
      align-self: start;
      justify-self: end;
    }

    @container (width >= 800px) {
      align-self: center;
    }
  }
</style>
