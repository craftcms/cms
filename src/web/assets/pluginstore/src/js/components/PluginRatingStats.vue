<script>
  /* global Craft */

  import {defineComponent} from 'vue';
  import RatingStars from './RatingStars.vue';
  import ProgressBar from './ProgressBar.vue';

  export default defineComponent({
    name: 'PluginRatingStats',
    components: {ProgressBar, RatingStars},
    props: {
      stats: Object,
      layout: {
        type: String,
        default: 'inline',
      },
    },
    computed: {
      ratingsText() {
        return Craft.t(
          'app',
          '{totalReviews, plural, =1{# rating} other{# ratings}}',
          {
            totalReviews: this.stats.totalReviews,
          }
        );
      },
    },
    methods: {
      getPercentage(rating) {
        if (!this.stats) {
          return 0;
        }

        if (!this.stats.totalReviewsByRating[rating]) {
          return 0;
        }

        return (
          (this.stats.totalReviewsByRating[rating] / this.stats.totalReviews) *
          100
        );
      },
    },
  });
</script>

<template>
  <div>
    <template v-if="stats">
      <div
        :class="{
          'tw-grid': true,
          'tw-gap-6 xl:tw-grid-cols-2 xl:tw-gap-20 tw-items-end ':
            layout === 'inline',
          'tw-flex-col': layout === 'stacked',
        }"
      >
        <div class="tw-flex tw-flex-wrap tw-items-baseline tw-gap-4">
          <div class="tw-flex tw-items-baseline tw-gap-1">
            <span class="tw-text-[3.25rem] tw-leading-none tw-font-light">
              {{ stats.ratingAvg }}
            </span>
            <span class="tw-text-2xl tw-font-light tw-text-gray-500">/ 5</span>
          </div>
          <RatingStars
            :rating="stats.ratingAvg"
            size="xl"
            class="tw-relative tw-top-1"
          />

          <div class="xl:tw-ml-auto tw-text-gray-500">{{ ratingsText }}</div>
        </div>

        <div class="tw-flex-grow">
          <div class="tw-space-y-1">
            <div v-for="i in [5, 4, 3, 2, 1]" :key="i" class="rating-bar">
              <span
                class="tw-text-light tw-text-xs tw-font-mono tw-inline-flex tw-text-right"
                >{{ i }}</span
              >
              <ProgressBar
                :value="getPercentage(i)"
                :height="10"
                indicator-class="tw-bg-yellow-500 dark:tw-bg-yellow-400"
              />
              <span class="tw-text-light tw-text-xs tw-font-mono tw-text-left"
                >{{ Number(getPercentage(i)).toFixed(0) }}%</span
              >
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped lang="scss">
  .rating-bar {
    display: grid;
    grid-template-columns: auto 1fr 4ch;
    align-items: center;
    gap: theme('spacing.2');
  }
</style>
