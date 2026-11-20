<script setup lang="ts">
  /** A run's or step run's status, with its indicator. */
  import RunIndicator from './RunIndicator.vue';
  import {statusLabels} from './run-format';
  import type {RunStatus} from './run-state';

  defineProps<{
    status: RunStatus;
    progress: number;
  }>();
</script>

<template>
  <span class="run-status-label">
    <RunIndicator :status="status" :progress="progress" />
    {{ statusLabels[status]() }}
  </span>
</template>

<style scoped>
  .run-status-label {
    display: inline-flex;
    align-items: center;
    gap: var(--c-spacing-sm);
  }
</style>
