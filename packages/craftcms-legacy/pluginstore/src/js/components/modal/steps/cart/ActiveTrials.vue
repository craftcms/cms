<template>
  <div
    v-if="pendingActiveTrials && pendingActiveTrials.length > 0"
    class="tw-border-t tw-border-solid tw-border-gray-200 tw-mt-6 tw-pt-6"
  >
    <div v-if="pendingActiveTrials.length > 1" class="right">
      <a
        :class="{
          'tw-opacity-50 tw-cursor-default': loading,
        }"
        @click="addAllTrialsToCart()"
        >{{ 'Add all to cart' | t('app') }}</a
      >
    </div>

    <h2>{{ 'Active Trials' | t('app') }}</h2>

    <div class="cart-data">
      <div v-for="(activeTrial, key) in pendingActiveTrials" :key="key">
        <active-trial
          :loading="loading"
          :activeTrial="activeTrial"
        ></active-trial>
      </div>
    </div>
  </div>
</template>

<script>
  import {mapGetters} from 'vuex';
  import licensesMixin from '../../../../mixins/licenses';
  import ActiveTrial from './ActiveTrial';

  export default {
    mixins: [licensesMixin],

    components: {
      ActiveTrial,
    },

    data() {
      return {
        loading: false,
      };
    },

    computed: {
      ...mapGetters({
        getActiveTrialPluginEdition: 'cart/getActiveTrialPluginEdition',
        pendingActiveTrials: 'cart/pendingActiveTrials',
      }),
    },

    methods: {
      addAllTrialsToCart() {
        if (this.loading) {
          return;
        }

        this.loading = true;
        this.$store.dispatch('cart/addAllTrialsToCart').catch(() => {
          this.loading = false;
          this.$root.displayError(
            this.$options.filters.t(
              'Couldn’t add all items to the cart.',
              'app'
            )
          );
        });
      },
    },
  };
</script>
