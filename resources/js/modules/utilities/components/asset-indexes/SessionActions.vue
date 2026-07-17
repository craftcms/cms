<script setup lang="ts">
  import {t} from '@craftcms/ui/utilities/translate';
  import {ref} from 'vue';

  defineProps<{
    sessionId: number;
    actionRequired: boolean;
  }>();

  const emit = defineEmits<{
    stop: [sessionId: number];
    review: [sessionId: number];
  }>();

  const isLoadingReview = ref(false);
  const isStopping = ref(false);

  function handleStop(sessionId: number) {
    isStopping.value = true;
    emit('stop', sessionId);
  }

  function handleReview(sessionId: number) {
    isLoadingReview.value = true;
    emit('review', sessionId);
  }
</script>

<template>
  <div class="flex gap-1">
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
