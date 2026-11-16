<script setup lang="ts">
  /**
   * Where a run, or one of its steps, has got: progress while it runs, then whether
   * it finished cleanly, with its status in a tooltip.
   *
   * It's drawn the size of a small control with a 2px ring, so it can take the place
   * of a run button.
   */
  import '@craftcms/ui/components/icon/icon';
  import '@craftcms/ui/components/progress/progress';
  import '@craftcms/ui/components/spinner/spinner';
  import '@craftcms/ui/components/tooltip/tooltip';
  import {computed, useId} from 'vue';
  import {t} from '@craftcms/ui';
  import type {RunStatus} from './run-state';

  const {status, progress, name} = defineProps<{
    status: RunStatus;
    /** From 0 to 100, or below 0 while it isn't known yet. */
    progress: number;
    /** What's running, to name it to screen readers. */
    name?: string;
  }>();

  const spinning = computed(() => status === 'running' && progress < 0);

  const id = useId();

  const label = computed(() => {
    switch (status) {
      case 'pending':
        return t('Waiting to run');
      case 'running':
        return progress < 0
          ? t('Running')
          : t('Running: {percent, number}% done', {percent: progress});
      case 'successful':
        return t('Successful');
      case 'failed':
        return t('Finished with errors');
    }

    return '';
  });

  const accessibleLabel = computed(() =>
    name ? `${name}: ${label.value}` : label.value
  );

  const icon = computed(() =>
    status === 'failed' ? 'triangle-exclamation' : 'circle-check'
  );

  const color = computed(() => {
    switch (status) {
      case 'running':
        return 'warning';
      case 'successful':
        return 'success';
      case 'failed':
        return 'danger';
    }

    return undefined;
  });
</script>

<template>
  <span
    class="run-indicator"
    :class="`run-indicator--${status}`"
    :data-color="color"
  >
    <span :id="id" class="run-indicator__graphic">
      <span
        v-if="status === 'pending'"
        class="run-indicator__outline"
        role="img"
        :aria-label="accessibleLabel"
      ></span>
      <craft-spinner
        v-else-if="spinning"
        :label="accessibleLabel"
      ></craft-spinner>
      <craft-progress
        v-else-if="status === 'running'"
        :progress="progress"
        :label="accessibleLabel"
      >
      </craft-progress>
      <craft-icon v-else :name="icon" :label="accessibleLabel"></craft-icon>
    </span>
    <div v-if="status === 'running'" class="run-indicator__pip"></div>
  </span>
  <craft-tooltip :for="id">{{ label }}</craft-tooltip>
</template>

<style scoped>
  .run-indicator {
    --_size: var(--c-size-control-sm);
    --_stroke-width: var(--c-run-indicator-stroke-width, 2px);
    --_track-color: var(--color-slate-200);
    display: inline-grid;
    place-items: center;
    gap: var(--c-spacing-sm);
    color: var(--c-color-fill-loud);
    font-size: var(--c-text-sm);
  }

  .run-indicator__outline {
    box-sizing: border-box;
    inline-size: var(--_size);
    block-size: var(--_size);
    border: var(--_stroke-width) solid var(--_track-color);
    border-radius: var(--c-radius-full);
  }

  .run-indicator__graphic,
  .run-indicator__pip {
    grid-row: 1 / -1;
    grid-column: 1 / -1;
  }

  .run-indicator__graphic {
    display: inline-grid;
    flex: none;
    place-items: center;
    inline-size: var(--_size);
    block-size: var(--_size);
    /* as a run button's outline, which the ring and spinner take the place of */
    --c-progress-size: var(--_size);
    --c-progress-stroke-width: var(--_stroke-width);
    --c-progress-track-color: var(--_track-color);
    --c-spinner-stroke-width: var(--_stroke-width);
    --size: var(--_size);
  }

  .run-indicator__graphic craft-icon {
    /* a little smaller, as a filled shape looks heavier than a button's outline */
    --c-icon-size: calc(var(--_size) - 4px);
    inline-size: var(--_size);
  }

  .run-indicator__pip {
    width: max(calc(var(--_size) * 0.5), 4px);
    height: max(calc(var(--_size) * 0.5), 4px);
    border-radius: var(--c-radius-full);
    background-color: currentColor;
  }
</style>
