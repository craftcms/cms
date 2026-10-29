<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {onScopeDispose, reactive, shallowRef, watch} from 'vue';
  import PaginationControls from '@/common/components/PaginationControls.vue';
  import Select from '@/common/form/Select.vue';
  import RatingStars from './RatingStars.vue';
  import ReviewsSummary from './ReviewsSummary.vue';
  import {getPluginReviews, type PluginReviewsQuery} from '../api';
  import {formatDate} from '../dates';
  import {reviewUrl} from '../links';
  import type {Plugin, PluginReviewsPage} from '../types';

  const {plugin} = defineProps<{plugin: Plugin}>();

  const query = reactive<PluginReviewsQuery>({
    page: 1,
    orderBy: 'dateCreated',
    direction: 'desc',
  });

  const result = shallowRef<PluginReviewsPage | null>(null);
  const loading = shallowRef(false);
  const failed = shallowRef(false);
  let controller: AbortController | null = null;

  watch(
    () => ({...query}),
    async (current) => {
      controller?.abort();
      controller = new AbortController();
      const {signal} = controller;
      loading.value = true;

      try {
        result.value = await getPluginReviews(plugin.handle, current, signal);
        failed.value = false;
      } catch {
        if (!signal.aborted) {
          failed.value = true;
        }
      } finally {
        if (!signal.aborted) {
          loading.value = false;
        }
      }
    },
    {immediate: true}
  );

  onScopeDispose(() => controller?.abort());

  const orderByOptions = [
    {label: t('Date Created'), value: 'dateCreated'},
    {label: t('Rating'), value: 'rating'},
  ];

  const directionOptions = [
    {label: t('Ascending'), value: 'asc'},
    {label: t('Descending'), value: 'desc'},
  ];

  function wasEdited(item: {dateCreated: string; dateUpdated: string}) {
    return item.dateUpdated !== item.dateCreated;
  }
</script>

<template>
  <div class="grid gap-xl">
    <ReviewsSummary
      v-if="plugin.reviewStats?.totalReviews"
      :stats="plugin.reviewStats"
    />

    <div class="flex flex-wrap items-center justify-between gap-md">
      <craft-button
        :href="reviewUrl(plugin)"
        target="_blank"
        icon="arrow-up-right-from-square"
        icon-position="suffix"
        rel="noopener nofollow"
      >
        {{ t('Leave a review') }}
        <span class="sr-only">{{ t('Opens in a new window') }}</span>
      </craft-button>
      <div class="flex items-center gap-sm">
        <craft-spinner v-if="loading && result"></craft-spinner>
        <Select
          small
          :label="t('Order by')"
          label-sr-only
          :options="orderByOptions"
          :model-value="query.orderBy"
          @update:model-value="
            (value) => Object.assign(query, {orderBy: value, page: 1})
          "
        />
        <Select
          small
          :label="t('Direction')"
          label-sr-only
          :options="directionOptions"
          :model-value="query.direction"
          @update:model-value="
            (value) => Object.assign(query, {direction: value, page: 1})
          "
        />
      </div>
    </div>

    <div v-if="!result && loading" class="flex justify-center p-lg">
      <craft-spinner></craft-spinner>
    </div>

    <craft-callout v-else-if="failed" variant="danger">
      {{ t('Failed to load plugin reviews. Please try again') }}
    </craft-callout>

    <craft-empty
      v-else-if="result && result.data.length === 0"
      icon="star"
      appearance="outline"
      :label="
        plugin.reviewStats?.totalReviews
          ? t('This plugin doesn’t have any reviews with comments.')
          : t('This plugin doesn’t have any reviews.')
      "
    >
      <craft-button
        :href="reviewUrl(plugin)"
        target="_blank"
        icon="arrow-up-right-from-square"
        icon-position="suffix"
      >
        {{ t('Leave a review') }}
        <span class="sr-only">{{ t('Opens in a new window') }}</span>
      </craft-button>
    </craft-empty>

    <ul v-else-if="result" class="plugin-reviews">
      <li
        v-for="review in result.data"
        :key="review.id"
        class="flex flex-col gap-md"
      >
        <RatingStars :rating="review.rating" />
        <p class="m-0">{{ review.comment }}</p>
        <div class="flex items-center gap-md">
          <img
            v-if="review.author.photo"
            :src="review.author.photo"
            alt=""
            class="plugin-reviews__photo"
          />
          <craft-avatar v-else :label="review.author.name"></craft-avatar>
          <div>
            <strong>{{ review.author.name }}</strong>
            <div class="plugin-reviews__date">
              {{ formatDate(review.dateCreated) }}
              <template v-if="wasEdited(review)">
                •
                {{
                  t('Edited {updated}', {
                    updated: formatDate(review.dateUpdated),
                  })
                }}
              </template>
            </div>
          </div>
        </div>
        <div v-if="review.comments.length" class="plugin-reviews__response">
          <strong>{{ t('Developer Response') }}</strong>
          <div v-for="comment in review.comments" :key="comment.id">
            {{ comment.comment }}
            <div class="plugin-reviews__date">
              {{ formatDate(comment.dateCreated) }}
              <template v-if="wasEdited(comment)">
                •
                {{
                  t('Edited {updated}', {
                    updated: formatDate(comment.dateUpdated),
                  })
                }}
              </template>
            </div>
          </div>
        </div>
      </li>
    </ul>

    <PaginationControls
      v-if="result && result.last_page > 1"
      :page-index="result.current_page - 1"
      :page-size="result.per_page"
      :page-count="result.last_page"
      :disabled="loading"
      @page-change="(index) => (query.page = index + 1)"
    />
  </div>
</template>

<style scoped>
  .plugin-reviews {
    display: grid;
    gap: var(--c-spacing-2xl) var(--c-spacing-2xl);
    margin: 0;
    padding: 0;
    list-style: none;
  }

  @media (width >= var(--breakpoint-md)) {
    .plugin-reviews {
      grid-template-columns: 1fr 1fr;
    }
  }

  .plugin-reviews__photo {
    inline-size: 2.5rem;
    block-size: 2.5rem;
    border-radius: var(--c-radius-full);
  }

  .plugin-reviews__date {
    color: var(--c-text-quiet);
    font-size: var(--c-text-xs);
  }

  .plugin-reviews__response {
    display: grid;
    gap: var(--c-spacing-sm);
    padding-inline-start: var(--c-spacing-lg);
    border-inline-start: 4px solid var(--c-color-accent-border-quiet);
  }
</style>
