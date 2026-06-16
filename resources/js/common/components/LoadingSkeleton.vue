<script setup lang="ts">
  import {t} from '@craftcms/cp';

  // A shared placeholder shown while an element index refetches its list. The
  // `cards` variant mirrors the stacked card grid; the `table` variant mirrors
  // table rows. `count`/`columns` are passed by each view so the placeholder
  // roughly matches the current page size and visible columns and the layout
  // doesn't jump when the real content swaps back in.
  withDefaults(
    defineProps<{
      variant: 'cards' | 'table';
      count?: number;
      columns?: number;
    }>(),
    {
      count: 6,
      columns: 4,
    }
  );
</script>

<template>
  <div class="loading-skeleton" role="status" aria-busy="true">
    <span class="sr-only">{{ t('Loading…') }}</span>

    <div v-if="variant === 'cards'" class="skeleton-cards" aria-hidden="true">
      <div v-for="n in count" :key="n" class="skeleton-card">
        <div class="skeleton-card__header">
          <span class="skeleton-bar skeleton-bar--title"></span>
        </div>
        <div class="skeleton-card__body">
          <div class="skeleton-card__lines">
            <span class="skeleton-bar"></span>
            <span class="skeleton-bar skeleton-bar--short"></span>
          </div>
          <span class="skeleton-bar skeleton-card__thumb"></span>
        </div>
      </div>
    </div>

    <div v-else class="skeleton-table" aria-hidden="true">
      <div v-for="n in count" :key="n" class="skeleton-row">
        <span
          v-for="c in columns"
          :key="c"
          class="skeleton-bar skeleton-cell"
          :class="{'skeleton-cell--first': c === 1}"
        ></span>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
  .loading-skeleton {
    padding: var(--c-spacing-md);
  }

  .skeleton-cards {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
  }

  .skeleton-card {
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-md);
    overflow: hidden;
  }

  .skeleton-card__header {
    padding: var(--c-spacing-sm) var(--c-spacing-md);
    background-color: var(--c-color-neutral-fill-quiet);
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }

  .skeleton-card__body {
    display: flex;
    justify-content: space-between;
    gap: var(--c-spacing-md);
    padding: var(--c-spacing-md);
  }

  .skeleton-card__lines {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }

  .skeleton-card__thumb {
    flex: none;
    width: 3rem;
    height: 3rem;
    border-radius: var(--c-radius-md);
  }

  .skeleton-table {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }

  .skeleton-row {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-md);
    padding-block: var(--c-spacing-sm);
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }

  .skeleton-cell {
    flex: 1;
  }

  .skeleton-cell--first {
    flex: 2;
  }

  .skeleton-bar {
    display: block;
    width: 100%;
    height: 0.75rem;
    border-radius: var(--c-radius-sm, 3px);
    background-color: var(--c-color-neutral-fill-quiet);
    position: relative;
    overflow: hidden;
  }

  .skeleton-bar--title {
    width: 40%;
    height: 1rem;
  }

  .skeleton-bar--short {
    width: 60%;
  }

  // Sweeping highlight. Kept on a pseudo-element so it can be disabled wholesale
  // for users who prefer reduced motion without affecting the static bars.
  .skeleton-bar::after {
    content: '';
    position: absolute;
    inset: 0;
    transform: translateX(-100%);
    background: linear-gradient(
      90deg,
      transparent,
      var(--c-color-neutral-fill-normal, rgba(0, 0, 0, 0.06)),
      transparent
    );
    animation: skeleton-shimmer 1.4s ease-in-out infinite;
  }

  @media (prefers-reduced-motion: reduce) {
    .skeleton-bar::after {
      animation: none;
    }
  }

  @keyframes skeleton-shimmer {
    100% {
      transform: translateX(100%);
    }
  }
</style>
