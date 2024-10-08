<template>
  <plugin-layout>
    <template v-if="status === 'loading'">
      <c-spinner />
    </template>
    <template v-if="status === 'error'">
      <div
        class="tw-py-10 tw-px-4 tw-border tw-text-red-500 tw-rounded-md"
        :style="{textAlign: 'center'}"
      >
        {{ 'Failed to load plugin reviews. Please try again' | t('app') }}
      </div>
    </template>
    <template v-else-if="status === 'success'">
      <template v-if="stats && stats.totalReviews > 0">
        <PluginRatingStats
          :stats="stats"
          :handle="plugin?.handle"
          class="tw-mb-10"
        />
      </template>

      <div>
        <div class="tw-grid sm:tw-grid-cols-2 tw-items-center tw-mb-8 tw-gap-2">
          <div>
            <c-btn :href="reviewUrl" target="_blank" rel="noopener nofollow">
              {{ 'Leave a review' | t('app') }}
            </c-btn>
          </div>
          <div class="sm:tw-justify-self-end">
            <div class="tw-flex tw-flex-wrap tw-gap-2 tw-items-center">
              <c-spinner
                v-if="fetchStatus === 'fetching'"
                class="tw-hidden sm:tw-block tw-mr-2"
              />

              <div>
                <label class="tw-sr-only" for="order-by-select">{{
                  'Order by' | t('app')
                }}</label>
                <c-dropdown
                  id="order-by-select"
                  v-model="params.orderBy"
                  :options="orderByOptions"
                />
              </div>

              <div>
                <label class="tw-sr-only" for="direction-select">{{
                  'Direction' | t('app')
                }}</label>
                <c-dropdown
                  id="direction-select"
                  v-model="params.direction"
                  :options="directionOptions"
                />
              </div>

              <c-spinner
                v-if="status === 'pending'"
                class="sm:tw-hidden tw-mr-2"
              />
            </div>
          </div>
        </div>
        <div
          v-if="reviews && reviews.length > 0"
          class="tw-grid md:tw-grid-cols-2 tw-gap-x-10 tw-gap-y-12"
        >
          <div v-for="review in reviews" :key="review.id">
            <div class="">
              <RatingStars :rating="review.rating" />

              <div class="tw-mt-2">
                {{ review.comment }}
              </div>

              <div class="tw-mt-4 tw-flex tw-gap-4">
                <ProfilePhoto :url="review.author.photo" />

                <div>
                  <div>
                    <strong>{{ review.author.name }}</strong>
                  </div>

                  <div
                    class="tw-flex tw-text-xs tw-text-gray-500 dark:tw-text-gray-400 tw-gap-1"
                  >
                    <span>
                      {{ review.dateCreated | formatDate }}
                    </span>
                    <template v-if="review.dateUpdated !== review.dateCreated">
                      <span>•</span>
                      <span>
                        {{
                          'Edited {updated}'
                            | t('app', {
                              updated: formatDate(review.dateUpdated),
                            })
                        }}
                      </span>
                    </template>
                  </div>
                </div>
              </div>

              <template v-if="review.comments.length > 0">
                <div
                  class="tw-mt-6 tw-border-l-4 tw-border-l-blue-200 dark:tw-border-l-blue-800 tw-pl-4"
                >
                  <strong class="tw-block tw-mb-1">{{
                    'Developer Response' | t('app')
                  }}</strong>
                  <div v-for="comment in review.comments" :key="comment.id">
                    {{ comment.comment }}
                    <div
                      class="tw-flex tw-text-xs tw-text-gray-500 dark:tw-text-gray-400 tw-gap-1 tw-mt-2"
                    >
                      <span>
                        {{ comment.dateCreated | formatDate }}
                      </span>
                      <template
                        v-if="comment.dateUpdated !== comment.dateCreated"
                      >
                        <span>•</span>
                        <span>
                          {{
                            'Edited {updated}'
                              | t('app', {
                                updated: formatDate(comment.dateUpdated),
                              })
                          }}
                        </span>
                      </template>
                    </div>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>
        <div v-else>
          <div class="tw-p-12 md:tw-py-24 tw-border tw-rounded-md">
            <div class="tw-text-center">
              <p>
                {{
                  'This plugin doesn’t have any reviews with comments.'
                    | t('app')
                }}
              </p>
              <div class="tw-mt-4">
                <c-btn v-if="reviewUrl" :href="reviewUrl" target="_blank">
                  {{ 'Leave a review' | t('app') }}
                </c-btn>
              </div>
            </div>
          </div>
        </div>

        <div
          v-if="meta.last_page > 1"
          class="tw-mt-12 tw-flex tw-justify-between tw-border-t tw-pt-4"
        >
          <c-btn small :disabled="params.page === 1" @click="previousPage">
            <c-icon icon="chevron-left" size="4" />
          </c-btn>
          <div class="tw-flex tw-gap-2 tw-items-center tw-justify-center">
            <div v-for="i in meta.last_page" :key="i">
              <template v-if="i === meta.current_page">
                <span
                  class="tw-font-medium tw-px-3 tw-py-2 tw-rounded-md tw-border tw-leading-4 tw-text-sm tw-border-blue-400 tw-bg-blue-50 dark:tw-bg-blue-600/40 tw-text-blue-600 dark:tw-text-blue-100"
                >
                  {{ i }}
                </span>
              </template>
              <template v-else>
                <c-btn small @click="goToPage(i)">
                  {{ i }}
                </c-btn>
              </template>
            </div>
          </div>
          <c-btn
            small
            :disabled="params.page === meta.last_page"
            @click="nextPage"
          >
            <c-icon icon="chevron-right" size="4" />
          </c-btn>
        </div>
      </div>
    </template>
    <template v-else>
      <div class="tw-p-12 md:tw-py-24 tw-border tw-rounded-md">
        <div class="tw-text-center">
          <p>{{ 'This plugin doesn’t have any reviews.' | t('app') }}</p>
          <div class="tw-mt-4">
            <c-btn v-if="reviewUrl" :href="reviewUrl" target="_blank">
              {{ 'Leave a review' | t('app') }}
            </c-btn>
          </div>
        </div>
      </div>
    </template>
  </plugin-layout>
</template>

<script>
  /* global Craft */

  import {defineComponent} from 'vue';
  import PluginLayout from '../../components/PluginLayout.vue';
  import PluginRatingStats from '../../components/PluginRatingStats.vue';
  import RatingStars from '../../components/RatingStars.vue';
  import ProfilePhoto from '../../components/ProfilePhoto.vue';
  import {mapState} from 'vuex';
  import {formatDate} from '../..//filters/craft';

  export default defineComponent({
    name: 'ReviewsPage',
    components: {
      RatingStars,
      PluginRatingStats,
      PluginLayout,
      ProfilePhoto,
    },
    computed: {
      ...mapState({
        plugin: (state) => state.pluginStore.plugin,
        reviews: (state) => state.pluginReviews.reviews,
        status: (state) => state.pluginReviews.status,
        fetchStatus: (state) => state.pluginReviews.fetchStatus,
        meta: (state) => state.pluginReviews.meta,
      }),

      stats() {
        return this.plugin?.reviewStats;
      },

      orderByOptions() {
        return [
          {label: Craft.t('app', 'Date Created'), value: 'dateCreated'},
          {label: Craft.t('app', 'Rating'), value: 'rating'},
        ];
      },
      directionOptions() {
        return [
          {label: Craft.t('app', 'Ascending'), value: 'asc'},
          {label: Craft.t('app', 'Descending'), value: 'desc'},
        ];
      },

      reviewUrl() {
        return this.plugin
          ? `https://console.craftcms.com/accounts/me/plugin-store/reviews/${this.plugin?.handle}`
          : null;
      },
    },

    watch: {
      params: {
        handler(value) {
          this.$store.dispatch('pluginReviews/getPluginReviews', {
            handle: this.plugin.handle,
            params: value,
          });
        },
        deep: true,
      },
    },
    data() {
      return {
        params: {
          page: 1,
          orderBy: 'dateCreated',
          direction: 'desc',
        },
      };
    },
    methods: {
      formatDate,
      nextPage() {
        this.goToPage(this.meta.current_page + 1);
      },

      previousPage() {
        this.goToPage(this.meta.current_page - 1);
      },

      goToPage(page) {
        if (!this.reviews.length > 0 || !page) {
          return;
        }

        this.params.page = page;
      },
    },
  });
</script>

<style scoped lang="scss"></style>
