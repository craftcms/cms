<script>
  /* global Craft */
  import {defineComponent} from 'vue';
  import Stat from './Stat.vue';
  import RatingStars from './RatingStars.vue';

  export default defineComponent({
    components: {
      RatingStars,
      Stat,
    },
    name: 'PluginRatingStat',
    methods: {
      getPluginReviewUrl(handle) {
        if (!handle) {
          return '';
        }

        return `https://console.craftcms.com/accounts/me/plugin-store/reviews/${this.plugin.handle}`;
      },
    },
    computed: {
      headingText() {
        return Craft.t(
          'app',
          '{totalReviews, plural, =1{# Review} other{# Reviews}}',
          {
            totalReviews: this.stats.totalReviews,
          }
        );
      },
    },
    props: {
      stats: Object,
      plugin: Object,
    },
  });
</script>

<template>
  <div>
    <div v-if="stats">
      <Stat>
        <template #title>
          <div class="tw-flex tw-items-baseline tw-justify-between">
            {{ headingText }}
            <router-link
              v-if="stats.totalReviews > 0"
              :to="`${plugin?.handle}/reviews`"
              class="tw-text-xs"
            >
              {{ 'All reviews' | t('app') }}
            </router-link>
          </div>
        </template>
        <template #content>
          <div v-if="stats.totalReviews > 0">
            <div class="tw-flex tw-items-center tw-gap-3 tw-mt-2">
              <div class="tw-flex tw-items-baseline tw-gap-1 tw-relative">
                <span class="tw-text-3xl tw-font-normal">{{
                  stats.ratingAvg
                }}</span>
                <span class="tw-text-sm tw-text-gray-500 dark:tw-text-gray-400"
                  >/ 5</span
                >
              </div>
              <div class="tw-flex tw-items-center tw-gap-1">
                <RatingStars size="lg" :rating="stats.ratingAvg" />
              </div>
            </div>
            <div
              class="tw-flex tw-items-baseline tw-text-sm tw-mt-4 tw-gap-4 tw-text-gray-300"
            >
              <a :href="getPluginReviewUrl(plugin?.handle)">{{
                'Leave a review' | t('app')
              }}</a>
            </div>
          </div>
        </template>
      </Stat>
    </div>
    <div v-else>
      <Stat>
        <template #title>Reviews</template>
        <template #content>
          <p class="tw-font-normal">
            {{ 'This plugin doesn’t have any reviews.' | t('app') }}
          </p>

          <div class="tw-mt-2">
            <c-btn
              block
              target="_blank"
              :href="getPluginReviewUrl(plugin?.handle)"
            >
              {{ 'Leave a review' | t('app') }}
            </c-btn>
          </div>
        </template>
      </Stat>
    </div>
  </div>
</template>

<style scoped lang="scss"></style>
