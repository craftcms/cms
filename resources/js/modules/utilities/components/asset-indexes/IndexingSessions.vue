<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import Badge from '@/common/components/Badge.vue';
  import SessionProgress from '@/modules/utilities/components/asset-indexes/SessionProgress.vue';
  import SessionActions from '@/modules/utilities/components/asset-indexes/SessionActions.vue';
  import {useAssetIndexer} from '@/modules/utilities/composables/useAssetIndexer';
  import ReviewSessionModal from '@/modules/utilities/components/asset-indexes/ReviewSessionModal.vue';
  import SessionVolumes from '@/modules/utilities/components/asset-indexes/SessionVolumes.vue';
  import Date from '@/common/components/Date.vue';

  const {
    sessionsArray,
    currentSessionId,
    stopSession,
    reviewSessionOverview,
    reviewSession,
  } = useAssetIndexer();
</script>

<template>
  <div class="sessions">
    <template v-for="session in sessionsArray" :key="session.id">
      <div class="session">
        <div class="session__label">
          <div class="cp:mb-1">
            <strong>{{ t('Volumes being indexed') }}</strong>
          </div>
          <SessionVolumes :value="session.indexedVolumes" />
        </div>

        <div class="session__status">
          <div class="cp:mb-1">
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
          <div class="cp:mb-1">
            <strong>{{ t('Last update') }}</strong>
          </div>
          <Date :value="session.dateUpdated.date" />
        </div>

        <div class="session__progress">
          <div class="cp:mb-1">
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
    border: 1px solid var(--c-color-neutral-border-quiet);
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
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
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
