<script setup lang="ts">
  /**
   * Runs an import plan. While it runs, its outline turns into the progress ring,
   * with the play icon left in the middle, though it can't be run again until then.
   */
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/tooltip/tooltip';
  import {computed, useId} from 'vue';
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

  /** A run that's finished leaves the plan to be run again, whatever it came to. */
  const inProgress = computed(
    () => running && (status === 'pending' || status === 'running')
  );
</script>

<template>
  <span class="run-button__frame">
    <span v-if="inProgress" class="run-button__progress">
      <RunIndicator :status="status" :progress="progress" :name="name" />
    </span>
    <craft-button
      :id="id"
      class="run-button"
      :class="{'run-button--running': inProgress}"
      type="button"
      icon="play"
      variant="outline"
      size="small"
      inherit
      data-color="success"
      :disabled="inProgress || undefined"
      :aria-label="t('Run {name}', {name})"
      @click="emit('run')"
    ></craft-button>
    <craft-tooltip v-if="!inProgress" :for="id">{{ t('Run') }}</craft-tooltip>
  </span>
</template>

<style scoped>
  .run-button__frame {
    display: inline-grid;
    place-items: center;
  }

  .run-button__frame > * {
    grid-area: 1 / 1;
  }

  /* centred on the button, without a line box's baseline space or the tooltip taking room beside it */
  .run-button__progress {
    display: inline-grid;
    place-items: center;
    line-height: 0;
  }

  .run-button__progress > :deep(*) {
    grid-area: 1 / 1;
  }

  /* the ring stands in for the outline, and the icon for its dot */
  .run-button__progress :deep(.run-indicator__pip) {
    display: none;
  }

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

  /* hovering reaches the ring beneath, which says how far the run has got */
  .run-button--running {
    pointer-events: none;
    border-color: transparent;
  }
</style>
