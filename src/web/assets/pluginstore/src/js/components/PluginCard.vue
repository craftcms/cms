<template>
  <router-link
    v-if="plugin"
    :to="'/' + plugin.handle"
    :title="plugin.name"
    class="plugin-card tw-relative tw-flex tw-flex-no-wrap tw-items-start tw-py-6 tw-border-b tw-border-gray-200 tw-border-solid tw-no-underline hover:tw-no-underline tw-text-gray-900"
  >
    <div class="plugin-icon tw-mr-4 tw-w-16 tw-shrink-0">
      <template v-if="plugin.iconUrl">
        <img :src="plugin.iconUrl" class="tw-w-16 tw-h-16" />
      </template>
      <template v-else>
        <div
          class="tw-bg-gray-100 tw-w-16 tw-h-16 tw-flex tw-items-center tw-justify-center tw-rounded-full"
        >
          <c-icon icon="plug" class="tw-w-7 tw-h-7 tw-text-gray-400" />
        </div>
      </template>
    </div>

    <div>
      <div class="plugin-details-header">
        <div class="plugin-name tw-flex tw-items-center">
          <strong>{{ plugin.name }}</strong>
          <edition-badge
            v-if="
              trialMode &&
              activeTrialPluginEdition &&
              plugin.editions.length > 1
            "
            :name="activeTrialPluginEdition.name"
          ></edition-badge>
        </div>
        <div>{{ plugin.shortDescription }}</div>
      </div>

      <template
        v-if="
          plugin.totalReviews && plugin.totalReviews > 0 && plugin.ratingAvg
        "
      >
        <div class="tw-flex tw-items-center tw-gap-1 tw-text-sm tw-mt-2 light">
          <RatingStars :rating="plugin.ratingAvg" size="sm" />
          ({{ plugin.totalReviews }})
        </div>
      </template>

      <template v-if="plugin.abandoned">
        <div class="error">{{ 'Abandoned' | t('app') }}</div>
      </template>
      <template v-else>
        <div class="light">
          {{ fullPriceLabel }}
        </div>
      </template>

      <div
        v-if="isPluginInstalled(plugin.handle)"
        class="installed"
        data-icon="check"
      ></div>
    </div>
  </router-link>
</template>

<script>
  /* global Craft */

  import {mapGetters} from 'vuex';
  import EditionBadge from './EditionBadge';
  import RatingStars from '../components/RatingStars.vue';

  export default {
    props: ['plugin', 'trialMode'],

    components: {
      RatingStars,
      EditionBadge,
    },

    computed: {
      ...mapGetters({
        isPluginInstalled: 'craft/isPluginInstalled',
        getActiveTrialPluginEdition: 'cart/getActiveTrialPluginEdition',
      }),

      activeTrialPluginEdition() {
        return this.getActiveTrialPluginEdition(this.plugin);
      },

      priceRange() {
        const editions = this.plugin.editions;

        let min = null;
        let max = null;

        for (let i = 0; i < editions.length; i++) {
          const edition = editions[i];

          let price = 0;

          if (edition.price) {
            price = parseInt(edition.price);
          }

          if (min === null) {
            min = price;
          }

          if (max === null) {
            max = price;
          }

          if (price < min) {
            min = price;
          }

          if (price > max) {
            max = price;
          }
        }

        return {
          min,
          max,
        };
      },

      fullPriceLabel() {
        const {min, max} = this.priceRange;

        if (min !== max) {
          return `${this.priceLabel(min)}–${this.priceLabel(max)}`;
        }

        return this.priceLabel(min);
      },
    },

    methods: {
      priceLabel(price) {
        return price > 0
          ? this.$options.filters.currency(price)
          : Craft.t('app', 'Free');
      },
    },
  };
</script>

<style lang="scss" scoped>
  @import '@craftcms/sass/mixins';

  .plugin-details-header {
    @apply tw-leading-normal tw-overflow-hidden tw-mb-1;
    max-height: 4.75em;

    .plugin-name {
      @apply tw-flex tw-mb-1;

      .edition-badge {
        @apply tw-ml-2;
      }
    }
  }

  .plugin-rating {
    display: flex;
    align-items: center;
  }

  a.plugin-card {
    box-sizing: border-box;
    @apply tw-text-gray-900;

    &:hover {
      @apply tw-text-gray-900;

      strong {
        @apply tw-text-blue-600;
      }
    }

    .installed {
      @apply tw-absolute;
      top: 14px;
      @include right(18px);
      color: #ccc;
    }
  }

  .ps-grid-plugins {
    .plugin-card {
      @apply tw-h-full;
    }
  }
</style>
