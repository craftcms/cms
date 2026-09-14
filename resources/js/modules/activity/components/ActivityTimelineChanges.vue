<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import '@craftcms/ui/components/disclosure/disclosure';
  import {computed, shallowRef} from 'vue';
  import Modal from '@/common/components/Modal.vue';
  import ActivityTimelineChangeList from './ActivityTimelineChangeList.vue';
  import type {ActivityChange} from '@/modules/activity/composables/useActivityTimeline';

  const props = defineProps<{
    changes: ActivityChange[];
  }>();

  const modalActive = shallowRef(false);
  const showInline = computed(
    () =>
      props.changes.length <= 3 && JSON.stringify(props.changes).length <= 400
  );

  function changeCountLabel(count: number): string {
    return t('{count, plural, =1{1 change} other{# changes}}', {count});
  }
</script>

<template>
  <craft-card class="activity-timeline__changes">
    <craft-disclosure
      v-if="showInline"
      :label="changeCountLabel(changes.length)"
      opened
    >
      <ActivityTimelineChangeList
        slot="content"
        class="activity-timeline__change-list"
        :changes="changes"
      />
    </craft-disclosure>

    <div v-else class="activity-timeline__changes-summary">
      <span>{{ changeCountLabel(changes.length) }}</span>
      <craft-button
        type="button"
        size="small"
        variant="outline"
        @click="modalActive = true"
      >
        {{ t('Expand') }}
      </craft-button>
    </div>
  </craft-card>

  <Teleport to="body">
    <Modal :is-active="modalActive" width="2xl" @close="modalActive = false">
      <craft-pane :label="changeCountLabel(changes.length)">
        <craft-button
          slot="header-actions"
          type="button"
          icon="x"
          :aria-label="t('Close')"
          variant="plain"
          size="small"
          @click="modalActive = false"
        />
        <ActivityTimelineChangeList :changes="changes" />
      </craft-pane>
    </Modal>
  </Teleport>
</template>

<style scoped>
  .activity-timeline__changes {
    margin-block-start: var(--c-spacing-sm);
  }

  .activity-timeline__change-list {
    margin-block-start: var(--c-spacing-sm);
  }

  .activity-timeline__changes-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--c-spacing-sm);
  }
</style>
