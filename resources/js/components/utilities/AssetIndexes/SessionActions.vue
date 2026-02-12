<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import {useAssetIndexer} from '@/composables/useAssetIndexer';

  defineProps<{
    sessionId: number;
    actionRequired: boolean;
  }>();

  const emit = defineEmits<{
    stop: [sessionId: number];
    review: [sessionId: number];
  }>();

  const {isLoadingReview, isStopping} = useAssetIndexer();

  function handleStop(sessionId: number) {
    emit('stop', sessionId);
  }

  function handleReview(sessionId: number) {
    emit('review', sessionId);
  }
</script>

<template>
  <div class="flex gap-2 justify-end">
    <craft-button
      v-if="actionRequired"
      type="button"
      size="small"
      :loading="isLoadingReview"
      @click="handleReview(sessionId)"
    >
      {{ t('Review') }}
    </craft-button>
    <craft-button
      type="button"
      size="small"
      variant="danger"
      :loading="isStopping"
      @click="handleStop(sessionId)"
    >
      <craft-icon name="x" slot="prefix"></craft-icon>
      {{ t('Discard') }}
    </craft-button>
  </div>
</template>
