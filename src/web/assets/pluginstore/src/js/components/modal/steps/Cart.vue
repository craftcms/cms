<template>
  <step>
    <template slot="header">
      <h1>{{ 'Cart' | t('app') }}</h1>
    </template>

    <template slot="main">
      <template v-if="!activeTrialsLoading">
        <h2>{{ 'Items in your cart' | t('app') }}</h2>

        <template v-if="cart">
          <template v-if="cartItems.length">
            <!-- Cart data -->
            <div
              class="cart-data tw-border-t tw-border-solid tw-border-gray-200"
            >
              <!-- Line Item -->
              <template v-for="(item, itemKey) in cartItems">
                <item
                  :item="item"
                  :key="'item' + itemKey"
                  :item-key="itemKey"
                />
              </template>
              <!-- /Line Item -->

              <!-- Total price -->
              <div class="tw-flex tw-mt-4 tw-text-lg">
                <div class="tw-w-14 tw-mr-14"></div>
                <div class="tw-flex-1 tw-flex tw-justify-between">
                  <div>
                    <strong>{{ 'Total Price' | t('app') }}</strong>
                  </div>
                  <div>
                    <strong>{{ cart.totalPrice | currency }}</strong>
                  </div>
                </div>
              </div>
              <!-- /Total price -->
            </div>
            <!-- /Cart data -->

            <!-- Checkout button -->
            <div class="tw-mt-4 tw-py-4 tw-text-right">
              <c-btn
                :disabled="totalLoadingItems > 0"
                kind="primary"
                @click="payment()"
                :loading="loadingCheckout"
                >{{ 'Checkout' | t('app') }}
              </c-btn>
            </div>
          </template>

          <!-- Empty cart -->
          <div v-else>
            <p>
              {{ 'Your cart is empty.' | t('app') }}
              <a @click="$emit('continue-shopping')">{{
                'Continue shopping' | t('app')
              }}</a>
            </p>
          </div>
        </template>

        <!-- Active trials -->
        <active-trials></active-trials>
      </template>
      <template v-else>
        <c-spinner />
      </template>
    </template>
  </step>
</template>

<script>
  /* global Craft */

  import {mapState, mapGetters} from 'vuex';
  import Step from '../Step';
  import ActiveTrials from './cart/ActiveTrials';
  import Item from './cart/Item.vue';

  export default {
    data() {
      return {
        activeTrialsLoading: true,
        loadingCheckout: false,
      };
    },

    components: {
      Item,
      ActiveTrials,
      Step,
    },

    computed: {
      ...mapState({
        cart: (state) => state.cart.cart,
      }),

      ...mapGetters({
        cartItems: 'cart/cartItems',
        cartItemsData: 'cart/cartItemsData',
        totalLoadingItems: 'cart/totalLoadingItems',
      }),
    },

    methods: {
      payment() {
        // Redirect to Craft Console with the order number
        this.$store.dispatch('cart/getOrderNumber').then((orderNumber) => {
          window.location.href = `${window.craftIdEndpoint}/cart?orderNumber=${orderNumber}`;
        });
      },
    },

    mounted() {
      this.$store
        .dispatch('cart/getActiveTrials')
        .then(() => {
          this.activeTrialsLoading = false;
        })
        .catch(() => {
          this.activeTrialsLoading = false;
        });
    },
  };
</script>
