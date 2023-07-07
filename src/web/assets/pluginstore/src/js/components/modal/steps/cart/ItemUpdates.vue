<template>
  <!-- Expiry date -->
  <div
    class="tw-border-t tw-border-solid tw-border-gray-200 tw-flex tw-justify-between tw-py-4"
  >
    <div>
      <div class="flex gap-3">
        <c-lightswitch
          :id="`item-${itemKey}`"
          :disabled="itemLoading({itemKey})"
          v-model:checked="itemsAutoRenew[itemKey]"
          @input="onChangeAutoRenew(itemKey)"
        />

        <label :for="`item-${itemKey}`">
          {{
            'Auto-renew for {price} annually, starting on {date}.'
              | t('app', {
                price: $options.filters.currency(
                  item.lineItem.purchasable.renewalPrice
                ),
                date: $options.filters.formatDate(renewalStartDate),
              })
          }}
        </label>
      </div>

      <template v-if="!itemsAutoRenew[itemKey]">
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
            <div>
              <div class="tw-text-sm tw-font-medium tw-mt-4">
                {{ 'Updates' | t('app') }}
              </div>
              <div class="tw-mt-1">
                <c-dropdown
                  v-model="selectedExpiryDates[itemKey]"
                  :options="itemExpiryDateOptions"
                  @input="onSelectedExpiryDateChange"
                />
              </div>
            </div>
          </template>
        </div>
      </template>

      <c-spinner v-if="itemLoading({itemKey})" class="tw-mt-4" />
    </div>

    <template
      v-for="(adjustment, adjustmentKey) in item.lineItem.adjustments.filter(
        (lineItemAdustment) =>
          lineItemAdustment.sourceSnapshot.type === 'extendedUpdates'
      )"
    >
      <div class="tw-text-right">
        <div
          class="tw-font-bold"
          :key="itemKey + 'adjustment-' + adjustmentKey"
        >
          {{ adjustment.amount | currency }}
        </div>

        <div class="mt-1">
          <a @click="removeUpdate()">{{ 'Remove' | t('app') }}</a>
        </div>
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

    computed: {
      ...mapState({
        expiryDateOptions: (state) => state.pluginStore.expiryDateOptions,
        loadingItems: (state) => state.cart.loadingItems,
      }),

      ...mapGetters({
        cartItems: 'cart/cartItems',
        cartItemsData: 'cart/cartItemsData',
        getPluginLicenseInfo: 'craft/getPluginLicenseInfo',
        itemLoading: 'cart/itemLoading',
      }),

      itemsAutoRenew: {
        get() {
          return JSON.parse(
            JSON.stringify(this.$store.state.cart.itemsAutoRenew)
          );
        },
        set(newValue) {
          this.$store.commit('cart/updateItemsAutoRenew', {
            orgId: this.orgId,
            itemsAutoRenew: newValue,
          });
        },
      },

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

      renewalStartDate() {
        return this.expiryDateOptions[0][1];
      },
    },

    methods: {
      onSelectedExpiryDateChange() {
        const itemKey = this.itemKey;

        this.$store.commit('cart/updateLoadingItem', {
          itemKey,
          value: true,
        });

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
            this.$store.commit('cart/deleteLoadingItem', {itemKey});
          });
      },

      onChangeAutoRenew(itemKey) {
        this.$store.commit('cart/updateLoadingItem', {
          itemKey,
          value: true,
        });

        let item = this.cartItemsData[itemKey];
        item.autoRenew = this.itemsAutoRenew[itemKey];
        item.expiryDate = '1y';

        this.$store
          .dispatch('cart/updateItem', {
            itemKey,
            item,
          })
          .finally(() => {
            this.$store.commit('cart/deleteLoadingItem', {itemKey});
          });
      },

      pluginLicenseInfo(pluginHandle) {
        return this.getPluginLicenseInfo(pluginHandle);
      },

      removeUpdate() {
        this.selectedExpiryDates[this.itemKey] = '1y';
        this.itemsAutoRenew[this.itemKey] = true;
        this.onSelectedExpiryDateChange();
      },
    },
  };
</script>
