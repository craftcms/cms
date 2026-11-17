<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import MetaStat from './MetaStat.vue';
  import type {Plugin} from '../types';

  const {plugin} = defineProps<{plugin: Plugin}>();

  const stats = computed(
    () => plugin.issueStats?.find((s) => s.period === 30) ?? null
  );

  const items = computed(() =>
    stats.value
      ? [
          {
            label: t('Closed Issues'),
            value: stats.value.closedIssues,
            icon: 'circle-check',
          },
          {
            label: t('New Issues'),
            value: stats.value.openIssues,
            icon: 'circle-dot',
          },
          {
            label: t('Merged PRs'),
            value: stats.value.mergedPulls,
            icon: 'code-merge',
          },
          {
            label: t('Open PRs'),
            value: stats.value.openPulls,
            icon: 'code-pull-request',
          },
        ]
      : []
  );
</script>

<template>
  <MetaStat v-if="stats">
    <template #label>
      {{ t('Activity') }}
      <small>{{ t('({period} days)', {period: stats.period}) }}</small>
    </template>
    <ul class="github-activity">
      <li v-for="item in items" :key="item.label">
        <span class="flex items-center gap-xs">
          <craft-icon :name="item.icon"></craft-icon>
          {{ item.value }}
        </span>
        <span class="github-activity__label">{{ item.label }}</span>
      </li>
    </ul>
  </MetaStat>
</template>

<style scoped>
  .github-activity {
    display: grid;
    grid-template-columns: 1fr 1fr;
    margin-block: var(--c-spacing-sm) 0;
    margin-inline: 0;
    padding: 0;
    border: 1px solid var(--c-color-border-quiet);
    border-radius: var(--c-radius-md);
    list-style: none;
  }

  .github-activity li {
    padding: var(--c-spacing-md);
  }

  .github-activity li:nth-child(odd) {
    border-inline-end: 1px solid var(--c-color-border-quiet);
  }

  .github-activity li:nth-child(n + 3) {
    border-block-start: 1px solid var(--c-color-border-quiet);
  }

  .github-activity__label {
    display: block;
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
    font-weight: normal;
  }
</style>
