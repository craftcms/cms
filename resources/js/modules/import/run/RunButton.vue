<script setup lang="ts">
  /**
   * Runs an import plan, then gives way to the run's progress until it finishes, its
   * outline turning into the progress ring.
   */
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/tooltip/tooltip';
  import {useId} from 'vue';
  import {t} from '@craftcms/ui';
  import RunIndicator from './RunIndicator.vue';
  import type {RunStatus} from './run-state';

  const {
    name,
    running,
    status = 'pending',
    progress = -1,
  } = defineProps<{
    /** The plan's name. */
    name: string;
    /** Whether the plan is running, or about to. */
    running: boolean;
    status?: RunStatus;
    progress?: number;
  }>();

  const emit = defineEmits<{run: []}>();

  const id = useId();
</script>

<template>
  <RunIndicator
    v-if="running"
    :status="status"
    :progress="progress"
    :name="name"
  />
  <template v-else>
    <craft-button
      :id="id"
      class="run-button"
      type="button"
      icon="play"
      variant="outline"
      size="small"
      inherit
      data-color="success"
      :aria-label="t('Run {name}', {name})"
      @click="emit('run')"
    ></craft-button>
    <craft-tooltip :for="id">{{ t('Run') }}</craft-tooltip>
  </template>
</template>

<style scoped>
  .run-button {
    --c-button-radius: var(--c-radius-full);
    /* as thick as the progress ring that takes its place */
    --c-button-border-width: 2px;
    border-color: var(--c-color-fill-loud);
    color: var(--c-color-fill-loud);
  }

  /* a triangle's weight sits left of its box's middle, so it's nudged right to look centered */
  .run-button::part(prefix) {
    translate: 1px 0;
  }
</style>
