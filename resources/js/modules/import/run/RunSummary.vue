<script setup lang="ts">
  /**
   * How a run went, or how it's going, as a row of facts formatted as the list of runs
   * shows them: its status, when it started and who started it, and how long it took.
   */
  import {t} from '@craftcms/ui';
  import {useIntervalFn, useNow} from '@vueuse/core';
  import {computed, watch} from 'vue';
  import MetadataItem from '@/common/components/MetadataItem.vue';
  import MetadataList from '@/common/components/MetadataList.vue';
  import RunStatusLabel from './RunStatusLabel.vue';
  import {runDuration, runStartedBy} from './run-format';
  import type {RunState} from './run-state';

  const props = defineProps<{
    run: RunState;
  }>();

  // ticks only while the run is going, so its duration counts up between reloads
  const {now, pause, resume} = useNow({
    controls: true,
    scheduler: (tick) => useIntervalFn(tick, 1000),
  });
  watch(
    () => Boolean(props.run.dateStarted && !props.run.dateFinished),
    (going) => (going ? resume() : pause()),
    {immediate: true}
  );

  const duration = computed(() => runDuration(props.run, now.value.getTime()));
</script>

<template>
  <MetadataList layout="row" class="run-summary">
    <MetadataItem :label="t('Status')">
      <RunStatusLabel :status="run.status" :progress="run.progress" />
    </MetadataItem>
    <MetadataItem :label="t('Started')">
      <span :title="run.started.title">{{ run.started.label }}</span>
    </MetadataItem>
    <MetadataItem :label="t('Started by')">
      {{ runStartedBy(run) }}
    </MetadataItem>
    <MetadataItem :label="t('Duration')">
      {{ duration ?? '—' }}
    </MetadataItem>
  </MetadataList>
</template>

<style scoped>
  /* right under the title, its rule across the whole content area */
  .run-summary {
    margin-inline: calc(var(--cp-container-padding) * -1);
    padding-block: var(--c-spacing-md) var(--c-spacing-lg);
    padding-inline: var(--cp-container-padding);
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }
</style>
