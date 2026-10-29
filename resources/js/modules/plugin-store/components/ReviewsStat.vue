<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {plugin as pluginRoute} from '@actions/PluginStore/PluginStoreController';
  import MetaStat from './MetaStat.vue';
  import RatingStars from './RatingStars.vue';
  import {reviewUrl} from '../links';
  import type {Plugin} from '../types';

  const {plugin} = defineProps<{plugin: Plugin}>();

  const emit = defineEmits<{'show-reviews': []}>();

  // A plain click switches tabs in place; a modified one opens the link as usual.
  function showReviews(event: MouseEvent): void {
    if (
      event.button !== 0 ||
      event.metaKey ||
      event.ctrlKey ||
      event.shiftKey ||
      event.altKey
    ) {
      return;
    }

    event.preventDefault();
    emit('show-reviews');
  }

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
        <a
          v-if="plugin.reviewStats?.totalReviews"
          :href="pluginRoute({handle: plugin.handle, tab: 'reviews'}).url"
          class="reviews-stat__all"
          @click="showReviews"
        >
          {{ t('All reviews') }}
        </a>
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

  .reviews-stat__all {
    font-weight: normal;
  }
</style>
