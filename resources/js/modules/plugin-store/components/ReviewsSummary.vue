<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import RatingStars from './RatingStars.vue';
  import type {PluginReviewStats} from '../types';

  const {stats} = defineProps<{stats: PluginReviewStats}>();

  const ratingsLabel = computed(() =>
    t('{totalReviews, plural, =1{# rating} other{# ratings}}', {
      totalReviews: stats.totalReviews,
    })
  );

  function share(rating: number): number {
    const count = stats.totalReviewsByRating[rating] ?? 0;
    return stats.totalReviews
      ? Math.round((count / stats.totalReviews) * 100)
      : 0;
  }
</script>

<template>
  <div class="reviews-summary">
    <div class="flex flex-wrap items-baseline gap-md">
      <span class="reviews-summary__average">{{ stats.ratingAvg }}</span>
      <span class="reviews-summary__quiet">/ 5</span>
      <RatingStars :rating="stats.ratingAvg" size="xl" />
      <span class="reviews-summary__quiet">
        {{ ratingsLabel }}
      </span>
    </div>
    <ul class="reviews-summary__bars">
      <li v-for="rating in [5, 4, 3, 2, 1]" :key="rating">
        <span>{{ rating }}</span>
        <span class="reviews-summary__bar" aria-hidden="true">
          <span :style="{inlineSize: `${share(rating)}%`}"></span>
        </span>
        <span>{{ share(rating) }}%</span>
      </li>
    </ul>
  </div>
</template>

<style scoped>
  .reviews-summary {
    display: grid;
    gap: var(--c-spacing-xl);
    align-items: end;
  }

  @media (width >= var(--breakpoint-xl)) {
    .reviews-summary {
      grid-template-columns: 1fr 1fr;
    }
  }

  .reviews-summary__average {
    font-size: 3.25rem;
    font-weight: 300;
    line-height: 1;
  }

  .reviews-summary__quiet {
    color: var(--c-text-quiet);
  }

  .reviews-summary__bars {
    display: grid;
    gap: var(--c-spacing-xs);
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .reviews-summary__bar {
    display: block;
    block-size: 0.625rem;
    overflow: hidden;
    border-radius: var(--c-radius-full);
    background: var(--c-color-neutral-fill-quiet);
  }

  .reviews-summary__bar span {
    display: block;
    block-size: 100%;
    background: var(--c-color-warning-fill-loud);
  }

  @media (forced-colors: active) {
    .reviews-summary__bar {
      border: 1px solid CanvasText;
    }

    .reviews-summary__bar span {
      background: CanvasText;
    }
  }

  .reviews-summary__bars li {
    display: grid;
    grid-template-columns: 1.5em 1fr 3em;
    gap: var(--c-spacing-sm);
    align-items: center;
    font-family: var(--c-font-mono, monospace);
    font-size: var(--c-text-xs);
  }
</style>
