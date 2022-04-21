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
              <div
                v-for="(item, itemKey) in cartItems"
                :key="'item' + itemKey"
                class="tw-border-b tw-border-solid tw-border-gray-200 md:tw-flex"
              >
                <div class="md:tw-mr-6 tw-pt-4 md:tw-pb-4 md:tw-px-4">
                  <item-icon :item="item" />
                </div>

                <div class="tw-flex-1">
                  <div class="tw-flex tw-py-4">
                    <!-- Item name -->
                    <item-name class="tw-flex-1" :item="item" />

                    <!-- Price -->
                    <div class="price tw-w-24 tw-text-right">
                      <strong>{{ item.lineItem.price | currency }}</strong>
                    </div>
                  </div>

                  <!-- Expiry date -->
                  <div
                    class="tw-border-t tw-border-solid tw-border-gray-200 tw-flex tw-justify-between tw-py-4"
                  >
                    <div class="expiry-date">
                      <template
                        v-if="
                          item.lineItem.purchasable.type === 'cms-edition' ||
                          (item.lineItem.purchasable.type ===
                            'plugin-edition' &&
                            (item.lineItem.options.licenseKey.substring(
                              0,
                              4
                            ) === 'new:' ||
                              (pluginLicenseInfo(item.plugin.handle) &&
                                pluginLicenseInfo(item.plugin.handle).isTrial)))
                        "
                      >
                        <c-dropdown
                          v-model="selectedExpiryDates[itemKey]"
                          :options="itemExpiryDateOptions(itemKey)"
                          @input="onSelectedExpiryDateChange(itemKey)"
                        />
                      </template>

                      <c-spinner v-if="itemLoading(itemKey)" />
                    </div>

                    <template
                      v-for="(
                        adjustment, adjustmentKey
                      ) in item.lineItem.adjustments.filter(
                        (lineItemAdustment) =>
                          lineItemAdustment.sourceSnapshot.type ===
                          'extendedUpdates'
                      )"
                    >
                      <div :key="itemKey + 'adjustment-' + adjustmentKey">
                        {{ adjustment.amount | currency }}
                      </div>
                    </template>
                  </div>

                  <!-- Adjustments -->
                  <item-adjustments :item="item" />

                  <!-- Remove button-->
                  <div
                    class="tw-py-4 tw-text-right tw-border-t tw-border-solid tw-border-gray-200"
                  >
                    <template v-if="!removeFromCartLoading(itemKey)">
                      <a role="button" @click="removeFromCart(itemKey)">{{
                        'Remove' | t('app')
                      }}</a>
                    </template>
                    <template v-else>
                      <c-spinner class="sm" />
                    </template>
                  </div>
                  <!-- /Remove button-->
                </div>
              </div>
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

  import {mapState, mapGetters, mapActions} from 'vuex';
  import Step from '../Step';
  import ActiveTrials from './cart/ActiveTrials';
  import ItemIcon from './cart/ItemIcon';
  import ItemName from './cart/ItemName';
  import ItemAdjustments from './cart/ItemAdjustments';

  export default {
    data() {
      return {
        activeTrialsLoading: true,
        loadingItems: {},
        loadingRemoveFromCart: {},
        loadingCheckout: false,
      };
    },

    components: {
      ItemAdjustments,
      ItemName,
      ItemIcon,
      ActiveTrials,
      Step,
    },

    computed: {
      ...mapState({
        activeTrialPlugins: (state) => state.cart.activeTrialPlugins,
        cart: (state) => state.cart.cart,
        expiryDateOptions: (state) => state.pluginStore.expiryDateOptions,
      }),

      ...mapGetters({
        cartItems: 'cart/cartItems',
        cartItemsData: 'cart/cartItemsData',
        getPluginLicenseInfo: 'craft/getPluginLicenseInfo',
      }),

      selectedExpiryDates: {
        get() {
          return JSON.parse(
            JSON.stringify(this.$store.state.cart.selectedExpiryDates)
          );
        },
        set(newValue) {
          this.$store.commit('cart/updateSelectedExpiryDates', newValue);
        },
      },
    },

    methods: {
      ...mapActions({
        removeFromCart: 'cart/removeFromCart',
      }),

      itemExpiryDateOptions(itemKey) {
        const item = this.cartItems[itemKey];
        const renewalPrice = item.lineItem.purchasable.renewalPrice;

        let options = [];
        let selectedOption = 0;

        this.expiryDateOptions.forEach((option, key) => {
          if (option === item.lineItem.options.expiryDate) {
            selectedOption = key;
          }
        });

        for (let i = 0; i < this.expiryDateOptions.length; i++) {
          const expiryDateOption = this.expiryDateOptions[i];
          const optionValue = expiryDateOption[0];
          const date = Craft.formatDate(expiryDateOption[1]);
          let label = this.$options.filters.t('Updates until {date}', 'app', {
            date,
          });
          let price = renewalPrice * (i - selectedOption);

          if (price !== 0) {
            let sign = '';

            if (price > 0) {
              sign = '+';
            }

            price = this.$options.filters.currency(price);
            label = this.$options.filters.t(
              'Updates until {date} ({sign}{price})',
              'app',
              {date, sign, price}
            );
          }

          options.push({
            label: label,
            value: optionValue,
          });
        }

        return options;
      },

      itemLoading(itemKey) {
        if (!this.loadingItems[itemKey]) {
          return false;
        }

        return true;
      },

      onSelectedExpiryDateChange(itemKey) {
        this.$set(this.loadingItems, itemKey, true);
        let item = this.cartItemsData[itemKey];
        item.expiryDate = this.selectedExpiryDates[itemKey];
        this.$store.dispatch('cart/updateItem', {itemKey, item}).then(() => {
          this.$delete(this.loadingItems, itemKey);
        });
      },

      payment() {
        // Redirect to Craft Console with the order number
        this.$store.dispatch('cart/getOrderNumber').then((orderNumber) => {
          window.location.href = `${window.craftIdEndpoint}/cart?orderNumber=${orderNumber}`;
        });
      },

      removeFromCart(itemKey) {
        this.$set(this.loadingRemoveFromCart, itemKey, true);

        this.$store
          .dispatch('cart/removeFromCart', itemKey)
          .then(() => {
            this.$delete(this.loadingRemoveFromCart, itemKey);
          })
          .catch((response) => {
            this.$delete(this.loadingRemoveFromCart, itemKey);
            const errorMessage =
              response.errors &&
              response.errors[0] &&
              response.errors[0].message
                ? response.errors[0].message
                : 'Couldn’t remove item from cart.';
            this.$root.displayError(errorMessage);
          });
      },

      removeFromCartLoading(itemKey) {
        if (!this.loadingRemoveFromCart[itemKey]) {
          return false;
        }

        return true;
      },

      pluginLicenseInfo(pluginHandle) {
        return this.getPluginLicenseInfo(pluginHandle);
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
