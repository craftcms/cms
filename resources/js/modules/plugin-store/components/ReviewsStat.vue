<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import MetaStat from './MetaStat.vue';
  import RatingStars from './RatingStars.vue';
  import {reviewUrl} from '../links';
  import type {Plugin} from '../types';

  const {plugin} = defineProps<{plugin: Plugin}>();

  const emit = defineEmits<{'show-reviews': []}>();

  const heading = computed(() =>
    t('{totalReviews, plural, =1{# Review} other{# Reviews}}', {
      totalReviews: plugin.reviewStats?.totalReviews ?? 0,
    })
  );
</script>

<template>
  <MetaStat>
    <template #label>
      <span class="flex items-baseline justify-between">
        {{ heading }}
        <craft-button
          v-if="plugin.reviewStats?.totalReviews"
          variant="link"
          size="small"
          @click="emit('show-reviews')"
        >
          {{ t('All reviews') }}
        </craft-button>
      </span>
    </template>

    <div v-if="plugin.reviewStats?.totalReviews" class="grid gap-sm">
      <div class="flex items-center gap-sm">
        <span class="text-3xl font-normal">{{
          plugin.reviewStats.ratingAvg
        }}</span>
        <span class="reviews-stat__quiet">/ 5</span>
        <RatingStars :rating="plugin.reviewStats.ratingAvg" size="lg" />
      </div>
      <a :href="reviewUrl(plugin)" target="_blank" rel="noopener">
        {{ t('Leave a review') }}
        <span class="sr-only">{{ t('Opens in a new window') }}</span>
      </a>
    </div>
    <div v-else class="grid gap-sm">
      <p class="m-0 font-normal">
        {{ t('This plugin doesn’t have any reviews.') }}
      </p>
      <craft-button
        :href="reviewUrl(plugin)"
        target="_blank"
        icon="arrow-up-right-from-square"
        icon-position="suffix"
      >
        {{ t('Leave a review') }}
        <span class="sr-only">{{ t('Opens in a new window') }}</span>
      </craft-button>
    </div>
  </MetaStat>
</template>

<style scoped>
  .reviews-stat__quiet {
    color: var(--c-text-quiet);
  }
</style>
