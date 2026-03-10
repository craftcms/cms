<script setup lang="ts">
  import {computed} from 'vue';

  const props = defineProps<{
    processedEntries: number;
    totalEntries: number;
    pending?: boolean;
  }>();

  const progressPercent = computed(() => {
    if (props.totalEntries === 0) return 0;
    return Math.round((props.processedEntries / props.totalEntries) * 100);
  });
</script>

<template>
  <div class="progress-cell">
    <craft-progress-bar
      :total="totalEntries"
      :processed="processedEntries"
      :pending="pending"
      show-status
    ></craft-progress-bar>
  </div>
</template>

<style scoped lang="scss">
  .progress-cell {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .progress-bar-container {
    width: 100%;
    height: 0.5rem;
    background-color: var(--c-color-neutral-fill-quiet);
    border-radius: var(--c-radius-sm);
    overflow: hidden;
  }

  .progress-bar {
    height: 100%;
    background-color: var(--c-color-accent-fill-loud);
    transition: width 0.2s ease;
  }

  .progress-info {
    font-size: var(--c-text-sm);
    color: var(--c-color-neutral-on-quiet);
  }
</style>
