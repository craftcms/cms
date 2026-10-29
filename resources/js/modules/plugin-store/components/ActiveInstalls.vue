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
  const hoveredIndex = shallowRef<number | null>(null);
  const hovered = computed<PluginInstallHistoryPoint | null>(() =>
    hoveredIndex.value === null
      ? null
      : (history.value[hoveredIndex.value] ?? null)
  );

  // Scaled between the lowest and highest counts, so day-to-day change shows.
  const points = computed(() => {
    const values = history.value.map((point) => point.activeInstalls);
    const min = Math.min(...values);
    const range = Math.max(...values) - min || 1;
    const step = values.length > 1 ? width / (values.length - 1) : width;

    return values.map((value, i) => [
      i * step,
      height - 2 - ((value - min) / range) * (height - 4),
    ]);
  });

  const linePath = computed(() =>
    points.value.map(([x, y], i) => `${i ? 'L' : 'M'}${x},${y}`).join(' ')
  );

  const areaPath = computed(() =>
    linePath.value ? `${linePath.value} L${width},${height} L0,${height} Z` : ''
  );

  // Placed as a share of the chart, which stretches to fill its box. The chart
  // draws left to right in every language, so the dot does too.
  const marker = computed(() => {
    const point =
      hoveredIndex.value === null ? null : points.value[hoveredIndex.value];

    if (!point) {
      return null;
    }

    const [x = 0, y = 0] = point;

    return {
      x,
      style: {left: `${(x / width) * 100}%`, top: `${(y / height) * 100}%`},
    };
  });

  function onPointerMove(event: PointerEvent): void {
    const rect = (event.currentTarget as SVGElement).getBoundingClientRect();
    const ratio = (event.clientX - rect.left) / rect.width;
    const last = history.value.length - 1;
    hoveredIndex.value = Math.min(last, Math.max(0, Math.round(ratio * last)));
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
      <span v-if="history.length > 1" class="active-installs__chart">
        <svg
          :viewBox="`0 0 ${width} ${height}`"
          preserveAspectRatio="none"
          aria-hidden="true"
          @pointermove="onPointerMove"
          @pointerleave="hoveredIndex = null"
        >
          <path class="active-installs__area" :d="areaPath" />
          <path class="active-installs__line" :d="linePath" />
          <line
            v-if="marker"
            class="active-installs__rule"
            :x1="marker.x"
            :x2="marker.x"
            y1="0"
            :y2="height"
          />
        </svg>
        <span
          v-if="marker"
          class="active-installs__dot"
          :style="marker.style"
          aria-hidden="true"
        ></span>
      </span>
    </div>
  </MetaStat>
</template>

<style scoped>
  .active-installs__count {
    min-inline-size: 4rem;
  }

  .active-installs__chart {
    position: relative;
    flex: 1;
    block-size: 2rem;
  }

  .active-installs__chart svg {
    display: block;
    inline-size: 100%;
    block-size: 100%;
  }

  .active-installs__rule {
    stroke: var(--c-color-accent-fill-loud);
    stroke-width: 1;
    stroke-dasharray: 2 2;
    vector-effect: non-scaling-stroke;
  }

  .active-installs__dot {
    position: absolute;
    inline-size: 0.5rem;
    block-size: 0.5rem;
    border: 2px solid var(--c-surface-default);
    border-radius: var(--c-radius-full);
    background: var(--c-color-accent-fill-loud);
    pointer-events: none;
    transform: translate(-50%, -50%);
  }

  .active-installs__area {
    fill: var(--c-color-accent-fill-quiet);
  }

  .active-installs__line {
    fill: none;
    stroke: var(--c-color-accent-fill-loud);
    stroke-width: 1.5;
    vector-effect: non-scaling-stroke;
  }
</style>
