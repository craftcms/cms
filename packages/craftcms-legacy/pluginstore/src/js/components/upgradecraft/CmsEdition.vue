<template>
  <div class="cms-editions-edition" v-if="editionExists">
    <div class="description">
      <edition-badge :name="edition.name" :block="true" :big="true" />
      <p class="edition-description">{{ editionDescription }}</p>
    </div>

    <div class="price-container">
      <div class="price">
        <template v-if="parseInt(edition.price)">
          {{ edition.price | currency }}
        </template>
        <template v-else>
          {{ 'Free' | t('app') }}
        </template>
      </div>

      <p v-if="edition.price > 0" class="price-renewal-info">
        {{
          'Plus {renewalPrice}/year for updates after one year.'
            | t('app', {
              renewalPrice: $options.filters.currency(edition.renewalPrice),
            })
        }}
      </p>
    </div>

    <div class="feature-list">
      <ul>
        <li v-if="previousEdition" class="cms-editions-previous">
          {{
            'Everything in {edition}, plus…'
              | t('app', {
                edition: previousEdition.name,
              })
          }}
        </li>
        <li v-for="(feature, key) in features" :key="key">
          <c-icon icon="check" />
          {{ feature.name }}

          <info-hud v-if="feature.description">
            {{ feature.description }}
          </info-hud>
        </li>
      </ul>
    </div>

    <div class="cms-edition-actions">
      <status-badge :edition="editionIndex"></status-badge>

      <buy-btn
        :edition="editionIndex"
        :edition-handle="edition.handle"
      ></buy-btn>
    </div>
  </div>
</template>

<script>
  import {mapGetters} from 'vuex';
  import InfoHud from '../InfoHud';
  import StatusBadge from './StatusBadge';
  import BuyBtn from './BuyBtn';
  import EditionBadge from '../EditionBadge';

  export default {
    props: ['edition', 'previousEdition'],

    components: {
      InfoHud,
      StatusBadge,
      BuyBtn,
      EditionBadge,
    },

    computed: {
      ...mapGetters({
        getCmsEditionFeatures: 'craft/getCmsEditionFeatures',
        getCmsEditionIndex: 'craft/getCmsEditionIndex',
      }),

      editionDescription() {
        switch (this.edition.handle) {
          case 'solo':
            return this.$options.filters.t(
              'For personal sites built for yourself or a friend.',
              'app'
            );
          case 'team':
            return this.$options.filters.t(
              'For marketing sites managed by small teams.',
              'app'
            );
          case 'pro':
            return this.$options.filters.t('For everything else.', 'app');
          default:
            return null;
        }
      },

      editionIndex() {
        return this.getCmsEditionIndex(this.edition.handle);
      },

      editionExists() {
        return this.editionIndex !== null;
      },

      features() {
        return this.getCmsEditionFeatures(this.edition.handle);
      },
    },
  };
</script>

<style lang="scss">
  .cms-editions-edition {
    @apply tw-border tw-border-gray-200 tw-border-solid tw-p-8 tw-rounded tw-text-center;

    .description {
      .edition-name {
        @apply tw-border-b tw-border-gray-200 tw-border-solid tw-text-gray-700 tw-inline-block tw-py-1 tw-uppercase tw-text-lg tw-font-bold;
      }

      .edition-description {
        @apply tw-text-lg tw-my-6 tw-leading-normal;
      }
    }

    .price-container {
      .price {
        @apply tw-text-3xl tw-font-bold;
      }

      .price-renewal-info {
        @apply tw-mx-auto tw-mt-2 tw-text-gray-700;
        max-width: 12rem;
      }
    }

    .feature-list {
      ul {
        @apply tw-text-left;

        li:not(:first-child) {
          @apply tw-mt-2;
        }
      }
    }

    .cms-edition-actions {
      position: relative;

      .c-spinner {
        position: absolute;
        bottom: -30px;
        left: 50%;
        margin-left: -11px;
      }

      .cms-edition-status-badge,
      .c-btn {
        @apply tw-mt-3;
      }
    }
  }
</style>
