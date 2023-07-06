<template>
  <div
    v-if="item"
    class="tw-border-b tw-border-solid tw-border-gray-200 md:tw-flex"
  >
    <div class="md:tw-mr-6 tw-pt-4 md:tw-pb-4 md:tw-px-4">
      <item-icon :item="item" />
    </div>

    <div class="tw-flex-1">
      <div class="tw-flex tw-py-4">
        <!-- Item name -->
        <item-name class="tw-flex-1" :item="item" />

        <div class="tw-text-right">
          <!-- Price -->
          <div class="price tw-w-24 tw-text-right">
            <strong>{{ item.lineItem.price | currency }}</strong>
          </div>

          <!-- Remove button-->
          <div>
            <template v-if="!removeItemLoading">
              <a role="button" @click="removeFromCart">{{
                'Remove' | t('app')
              }}</a>
            </template>
            <template v-else>
              <c-spinner class="sm" />
            </template>
          </div>
        </div>
      </div>

      <!-- Expiry date -->
      <div
        class="tw-border-t tw-border-solid tw-border-gray-200 tw-flex tw-justify-between tw-py-4"
      >
        <div class="expiry-date flex flex-nowrap">
          <template
            v-if="
              item.lineItem.purchasable.type === 'cms-edition' ||
              (item.lineItem.purchasable.type === 'plugin-edition' &&
                (item.lineItem.options.licenseKey.substring(0, 4) === 'new:' ||
                  (pluginLicenseInfo(item.plugin.handle) &&
                    pluginLicenseInfo(item.plugin.handle).isTrial)))
            "
          >
            <c-dropdown
              v-model="selectedExpiryDates[itemKey]"
              :options="itemExpiryDateOptions"
              @input="onSelectedExpiryDateChange"
            />
          </template>

          <c-spinner v-if="itemLoading" />
        </div>

        <template
          v-for="(
            adjustment, adjustmentKey
          ) in item.lineItem.adjustments.filter(
            (lineItemAdustment) =>
              lineItemAdustment.sourceSnapshot.type === 'extendedUpdates'
          )"
        >
          <div :key="itemKey + 'adjustment-' + adjustmentKey">
            {{ adjustment.amount | currency }}
          </div>
        </template>
      </div>

      <item-adjustments :item="item" />
    </div>
  </div>
</template>

<script>
  /* global Craft */

  import {mapState, mapGetters} from 'vuex';
  import Step from '../../Step';
  import ItemIcon from '../cart/ItemIcon';
  import ItemName from '../cart/ItemName';
  import ItemAdjustments from '../cart/ItemAdjustments';

  export default {
    props: {
      item: {
        type: Object,
        required: true,
      },
      itemKey: {
        type: String,
        required: true,
      },
    },
    data() {
      return {
        itemLoading: false,
        removeItemLoading: false,
      };
    },

    components: {
      ItemAdjustments,
      ItemName,
      ItemIcon,
      Step,
    },

    computed: {
      ...mapState({
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

      itemExpiryDateOptions() {
        const item = this.cartItems[this.itemKey];
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
    },

    methods: {
      onSelectedExpiryDateChange() {
        const itemKey = this.itemKey;
        this.itemLoading = true;
        let item = this.cartItemsData[itemKey];
        item.expiryDate = this.selectedExpiryDates[itemKey];
        this.$store
          .dispatch('cart/updateItem', {itemKey, item})
          .catch(() => {
            this.$root.displayError(
              Craft.t('app', 'Couldn’t update item in cart.')
            );
          })
          .finally(() => {
            this.itemLoading = false;
          });
      },

      payment() {
        // Redirect to Craft Console with the order number
        this.$store.dispatch('cart/getOrderNumber').then((orderNumber) => {
          window.location.href = `${window.craftIdEndpoint}/cart?orderNumber=${orderNumber}`;
        });
      },

      removeFromCart() {
        this.removeItemLoading = true;

        this.$store
          .dispatch('cart/removeFromCart', this.itemKey)
          .then(() => {
            this.removeItemLoading = false;
          })
          .catch((response) => {
            const errorMessage =
              response.errors &&
              response.errors[0] &&
              response.errors[0].message
                ? response.errors[0].message
                : 'Couldn’t remove item from cart.';
            this.$root.displayError(errorMessage);
          })
          .finally(() => {
            this.removeItemLoading = false;
          });
      },

      pluginLicenseInfo(pluginHandle) {
        return this.getPluginLicenseInfo(pluginHandle);
      },
    },
  };
</script>
