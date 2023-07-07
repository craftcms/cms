<template>
  <!-- Expiry date -->

  <div class="tw-border-t tw-border-solid tw-border-gray-200 tw-py-4">
    <div class="flex gap-3">
      <c-lightswitch
        :id="`item-${itemKey}`"
        :disabled="totalLoadingItems > 0"
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

    <div class="tw-flex tw-justify-between">
      <template v-if="!itemsAutoRenew[itemKey]">
        <div class="tw-mt-4 expiry-date flex flex-nowrap">
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
              <div class="tw-text-sm tw-font-medium">
                {{ 'Updates' | t('app') }}
              </div>
              <div class="tw-mt-1">
                <c-dropdown
                  :disabled="totalLoadingItems > 0"
                  v-model="selectedExpiryDates[itemKey]"
                  :options="itemUpdateOptions"
                  @input="onSelectedExpiryDateChange"
                />
              </div>
            </div>
          </template>
        </div>

        <template
          v-for="(
            adjustment, adjustmentKey
          ) in item.lineItem.adjustments.filter(
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
              <button
                :disabled="totalLoadingItems"
                class="tw-text-blue-600 hover:tw-underline"
                :class="{
                  'tw-opacity-50': totalLoadingItems,
                }"
                @click="removeUpdate()"
              >
                {{ 'Remove' | t('app') }}
              </button>
            </div>
          </div>
        </template>
      </template>
    </div>

    <c-spinner v-if="itemLoading({itemKey})" class="tw-mt-4" />
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
        totalLoadingItems: 'cart/totalLoadingItems',
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

      itemUpdateOptions() {
        const cartItems = this.cartItems;
        const item = cartItems[this.itemKey];
        const renewalPrice = parseFloat(item.lineItem.purchasable.renewalPrice);

        let options = [];
        let selectedOption = 0;

        this.expiryDateOptions.forEach((option, key) => {
          if (option[0] === item.lineItem.options.expiryDate) {
            selectedOption = key;
          }
        });

        for (let i = 0; i < this.expiryDateOptions.length; i++) {
          const expiryDateOption = this.expiryDateOptions[i];
          const value = expiryDateOption[0];
          const price = renewalPrice * (i - selectedOption);
          const nbYears = i + 1;
          let priceDifference = '';

          let label;

          if (price !== 0) {
            let sign = '';

            if (price > 0) {
              sign = '+';
            }

            priceDifference =
              ' (' + sign + this.$options.filters.currency(price) + ')';
          }

          label = this.$options.filters.t(
            '{num, number} {num, plural, =1{year} other{years}} of updates',
            'app',
            {num: nbYears}
          );

          if (nbYears === 1) {
            label += ` ${this.$options.filters.t('(included)', 'app')}`;
          }

          if (priceDifference) {
            label += ` ${priceDifference}`;
          }

          options.push({
            label: label,
            value: value,
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
