<template>
  <div
    class="tw-border-t tw-border-solid tw-border-gray-200 md:tw-flex md:tw-justify-between md:tw-items-center tw-py-4 md:tw-py-2 tw-space-y-2 md:tw-space-y-0"
  >
    <div class="tw-flex tw-items-center tw-w-3/5">
      <!-- Icon -->
      <div class="tw-mr-4 tw-leading-3">
        <img
          v-if="activeTrial.iconUrl"
          :src="activeTrial.iconUrl"
          class="tw-w-10 tw-h-10"
        />
        <div class="default-icon" v-else></div>
      </div>

      <!-- Item name -->
      <div class="item-name">
        <a
          :title="activeTrial.name"
          class="tw-mr-2"
          @click.prevent="navigateToPlugin"
        >
          <strong>{{ activeTrial.name }}</strong>
        </a>

        <edition-badge
          v-if="activeTrial.editionName && activeTrial.showEditionBadge"
          :name="activeTrial.editionName"
        ></edition-badge>
      </div>
    </div>

    <!-- Price -->
    <div class="tw-flex-1">
      <template v-if="activeTrial.price">
        <template v-if="activeTrial.discountPrice">
          <del class="tw-mr-1">{{ activeTrial.price | currency }}</del>
          <strong>{{ activeTrial.discountPrice | currency }}</strong>
        </template>
        <template v-else>
          <strong>{{ activeTrial.price | currency }}</strong>
        </template>
      </template>
    </div>

    <!-- Add to cart -->
    <div class="md:tw-w-1/4">
      <div class="md:tw-text-right">
        <template v-if="!addToCartLoading && !loading">
          <button
            @click="addToCart()"
            :loading="addToCartLoading"
            :disabled="loading"
            :class="{
              'tw-text-blue-600 hover:tw-underline': true,
              'disabled hover:tw-no-underline': activeTrial.licenseMismatched,
            }"
          >
            {{ 'Add to cart' | t('app') }}
          </button>
        </template>
        <template v-else>
          <c-spinner size="sm" />
        </template>
      </div>
    </div>
  </div>
</template>

<script>
  import EditionBadge from '../../../EditionBadge';

  export default {
    components: {EditionBadge},

    props: {
      activeTrial: {
        type: Object,
        required: true,
      },
      loading: {
        type: Boolean,
        default: false,
      },
    },

    data() {
      return {
        addToCartLoading: false,
      };
    },

    methods: {
      addToCart() {
        this.addToCartLoading = true;

        const item = {
          type: this.activeTrial.type,
          edition: this.activeTrial.editionHandle,
        };

        if (this.activeTrial.type === 'plugin-edition') {
          item.plugin = this.activeTrial.pluginHandle;
        }

        this.$store
          .dispatch('cart/addToCart', [item])
          .then(() => {
            this.addToCartLoading = false;
          })
          .catch((response) => {
            this.addToCartLoading = false;
            const errorMessage =
              response.errors &&
              response.errors[0] &&
              response.errors[0].message
                ? response.errors[0].message
                : 'Couldn’t add item to cart.';
            this.$root.displayError(errorMessage);
          });
      },

      navigateToPlugin() {
        const path = this.activeTrial.navigateTo;

        this.$root.closeModal();

        if (this.$route.path !== path) {
          this.$router.push({path});
        }
      },
    },
  };
</script>
