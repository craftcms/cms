<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';

  const {
    rating,
    max = 5,
    size = 'md',
  } = defineProps<{
    rating: number;
    max?: number;
    size?: 'sm' | 'md' | 'lg' | 'xl';
  }>();

  const fill = computed(() => `${Math.round((rating / max) * 100)}%`);
</script>

<template>
  <span
    class="rating-stars"
    :class="`rating-stars--${size}`"
    role="img"
    :aria-label="t('Rating: {rating} out of {max} stars', {rating, max})"
  >
    <span class="rating-stars__track" aria-hidden="true">
      <craft-icon v-for="i in max" :key="i" name="star"></craft-icon>
    </span>
    <span
      class="rating-stars__track rating-stars__track--filled"
      :style="{inlineSize: fill}"
      aria-hidden="true"
    >
      <craft-icon v-for="i in max" :key="i" name="star"></craft-icon>
    </span>
  </span>
</template>

<style scoped>
  .rating-stars {
    position: relative;
    display: inline-flex;
    font-size: 1.25rem;
    line-height: 1;
  }

  .rating-stars--sm {
    font-size: 0.875rem;
  }

  .rating-stars--lg {
    font-size: 1.75rem;
  }

  .rating-stars--xl {
    font-size: 2.5rem;
  }

  .rating-stars__track {
    display: inline-flex;
    flex-wrap: nowrap;
    color: var(--c-color-neutral-fill-normal);
  }

  .rating-stars__track--filled {
    position: absolute;
    inset-block-start: 0;
    inset-inline-start: 0;
    overflow: hidden;
    color: var(--c-color-warning-fill-loud, #f59e0b);
  }

  @media (forced-colors: active) {
    .rating-stars__track {
      color: GrayText;
    }

    .rating-stars__track--filled {
      color: CanvasText;
    }
  }
</style>
