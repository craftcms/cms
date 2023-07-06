<template>
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
      v-for="(adjustment, adjustmentKey) in item.lineItem.adjustments.filter(
        (lineItemAdustment) =>
          lineItemAdustment.sourceSnapshot.type === 'extendedUpdates'
      )"
    >
      <div :key="itemKey + 'adjustment-' + adjustmentKey">
        {{ adjustment.amount | currency }}
      </div>
    </template>
  </div>
</template>
<script>
  import {mapGetters, mapState} from 'vuex';

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
      };
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

      pluginLicenseInfo(pluginHandle) {
        return this.getPluginLicenseInfo(pluginHandle);
      },
    },
  };
</script>
