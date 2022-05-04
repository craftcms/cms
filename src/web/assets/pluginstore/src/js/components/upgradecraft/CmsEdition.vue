<template>
  <div class="cms-editions-edition">
    <div class="description">
      <edition-badge :name="edition.name" :block="true" :big="true" />
      <p class="edition-description">{{ editionDescription }}</p>
      <div class="price">
        <template v-if="edition.price && edition.price > 0">
          {{ edition.price | currency }}
        </template>
        <template v-else>
          {{ 'Free' | t('app') }}
        </template>
      </div>

      <p
        v-if="edition.price && edition.price > 0"
        class="tw--mt-8 tw-py-6 tw-text-gray-700"
      >
        {{ 'Price includes 1 year of updates.' | t('app') }}<br />
        {{
          '{renewalPrice}/year per site for updates after that.'
            | t('app', {
              renewalPrice: $options.filters.currency(edition.renewalPrice),
            })
        }}
      </p>

      <ul>
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
    props: ['edition'],

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
              'For when you’re building a website for yourself or a friend.',
              'app'
            );
          case 'pro':
            return this.$options.filters.t(
              'For when you’re building something professionally for a client or team.',
              'app'
            );
          default:
            return null;
        }
      },

      editionIndex() {
        return this.getCmsEditionIndex(this.edition.handle);
      },

      features() {
        return this.getCmsEditionFeatures(this.edition.handle);
      },
    },
  };
</script>

<style lang="scss">
  .cms-editions-edition {
    @apply tw-border tw-border-gray-200 tw-border-solid tw-p-8 tw-rounded tw-text-center tw-flex tw-flex-col;

    .description {
      @apply tw-flex-1;

      .edition-name {
        @apply tw-border-b tw-border-gray-200 tw-border-solid tw-text-gray-700 tw-inline-block tw-py-1 tw-uppercase tw-text-lg tw-font-bold;
      }

      .edition-description {
        @apply tw-text-lg tw-my-6 tw-leading-normal;
      }

      .price {
        @apply tw-text-3xl tw-font-bold tw-my-8;
      }

      ul {
        @apply tw-text-left tw-mb-8;

        li {
          @apply tw-py-2 tw-border-b tw-border-gray-200 tw-border-solid;

          &:first-child {
            @apply tw-border-t;
          }
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

      .c-btn {
        @apply tw-mt-3;
      }
    }
  }
</style>
