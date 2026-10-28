<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {formatNumber} from '@craftcms/ui/utilities/format';
  import {computed, shallowRef} from 'vue';
  import MetaStat from './MetaStat.vue';
  import {formatDate} from '../dates';
  import type {Plugin, PluginInstallHistoryPoint} from '../types';

  const {plugin} = defineProps<{plugin: Plugin}>();

  const width = 160;
  const height = 32;

  const history = computed(() => plugin.installHistory ?? []);
  const hovered = shallowRef<PluginInstallHistoryPoint | null>(null);

  const points = computed(() => {
    const values = history.value.map((point) => point.activeInstalls);
    const max = Math.max(...values, 1);
    const step = values.length > 1 ? width / (values.length - 1) : width;

    return values.map((value, i) => [
      i * step,
      height - (value / max) * height,
    ]);
  });

  const areaPath = computed(() => {
    if (points.value.length === 0) {
      return '';
    }

    const line = points.value.map(([x, y]) => `L${x},${y}`).join(' ');
    return `M0,${height} ${line} L${width},${height} Z`;
  });

  function onPointerMove(event: PointerEvent): void {
    const rect = (event.currentTarget as SVGElement).getBoundingClientRect();
    const ratio = (event.clientX - rect.left) / rect.width;
    const index = Math.round(ratio * (history.value.length - 1));
    hovered.value = history.value[index] ?? null;
  }
</script>

<template>
  <MetaStat>
    <template #label>
      {{ hovered ? formatDate(hovered.date) : t('Active Installs') }}
    </template>
    <div class="flex items-center gap-md">
      <span class="active-installs__count">
        {{ formatNumber(hovered?.activeInstalls ?? plugin.activeInstalls) }}
      </span>
      <svg
        v-if="history.length > 1"
        class="active-installs__chart"
        :viewBox="`0 0 ${width} ${height}`"
        preserveAspectRatio="none"
        aria-hidden="true"
        @pointermove="onPointerMove"
        @pointerleave="hovered = null"
      >
        <path :d="areaPath" />
      </svg>
    </div>
  </MetaStat>
</template>

<style scoped>
  .active-installs__count {
    min-inline-size: 4rem;
  }

  .active-installs__chart {
    flex: 1;
    block-size: 2rem;
    fill: var(--c-color-accent-fill-quiet);
  }
</style>
