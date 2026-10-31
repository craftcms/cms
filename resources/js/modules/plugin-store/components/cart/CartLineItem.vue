<script setup lang="ts">
  import {formatDate, t} from '@craftcms/ui';
  import CraftSwitch from '@craftcms/ui/vue/CraftSwitch.vue';
  import {computed} from 'vue';
  import Select from '@/common/form/Select.vue';
  import PluginIcon from '../PluginIcon.vue';
  import {useCart} from '../../composables/useCart';
  import {
    useCoreData,
    usePluginLicenseInfo,
  } from '../../composables/usePluginStoreData';
  import {canExtendUpdates, showError, updateOptions} from '../../cart';
  import {apiDate} from '../../dates';
  import {formatPrice} from '../../pricing';
  import craftIconUrl from '../../images/craft.svg?url';
  import type {CartLineItem} from '../../types';

  const {lineItem, index} = defineProps<{
    lineItem: CartLineItem;
    index: number;
  }>();

  const {busy, pluginFor, update, remove} = useCart();
  const {coreData} = useCoreData();
  const {licenseInfoFor} = usePluginLicenseInfo();

  const plugin = computed(() => pluginFor(lineItem.purchasable.plugin?.id));
  const isCms = computed(() => lineItem.purchasable.type === 'cms-edition');
  const name = computed(() =>
    isCms.value
      ? 'Craft CMS'
      : (plugin.value?.name ?? lineItem.purchasable.name)
  );
  const showEdition = computed(
    () => isCms.value || (plugin.value?.editions.length ?? 0) > 1
  );

  const expiryDateOptions = computed(
    () => coreData.value?.expiryDateOptions ?? []
  );
  const renewalDate = computed(() => expiryDateOptions.value[0]?.[1] ?? null);

  const extendable = computed(() =>
    canExtendUpdates(
      lineItem,
      plugin.value ? licenseInfoFor(plugin.value.handle) : null
    )
  );

  const extendedUpdates = computed(() =>
    lineItem.adjustments.filter(
      (adjustment) => adjustment.sourceSnapshot.type === 'extendedUpdates'
    )
  );
  const otherAdjustments = computed(() =>
    lineItem.adjustments.filter(
      (adjustment) => adjustment.sourceSnapshot.type !== 'extendedUpdates'
    )
  );

  async function run(change: Promise<void>, failure: string): Promise<void> {
    try {
      await change;
    } catch (error) {
      showError(error, failure);
    }
  }

  function setAutoRenew(autoRenew: boolean): void {
    void run(
      update(index, {autoRenew, expiryDate: '1y'}),
      t('Couldn’t update item in cart.')
    );
  }

  function setExpiryDate(expiryDate: string | number): void {
    void run(
      update(index, {expiryDate: String(expiryDate)}),
      t('Couldn’t update item in cart.')
    );
  }

  function removeItem(): void {
    void run(remove(index), t('Couldn’t remove item from cart.'));
  }
</script>

<template>
  <li class="cart-line-item">
    <PluginIcon :url="isCms ? craftIconUrl : (plugin?.iconUrl ?? null)" />

    <div class="grid gap-sm">
      <div class="flex items-start justify-between gap-md">
        <div>
          <div class="flex items-center gap-sm">
            <strong>{{ name }}</strong>
            <craft-badge v-if="showEdition" size="small" no-prefix>
              {{ lineItem.purchasable.name }}
            </craft-badge>
          </div>
          <div class="cart-line-item__quiet">
            {{ t('Includes one year of updates.') }}
          </div>
        </div>
        <div class="grid justify-items-end">
          <strong>{{ formatPrice(lineItem.price) }}</strong>
          <craft-button
            variant="link"
            size="small"
            :aria-label="`${t('Remove')} ${name}`"
            .disabled="busy"
            @click="removeItem"
          >
            {{ t('Remove') }}
          </craft-button>
        </div>
      </div>

      <CraftSwitch
        :label="
          t('Auto-renew for {price} annually, starting on {date}.', {
            price: formatPrice(lineItem.purchasable.renewalPrice),
            date: renewalDate ? formatDate(apiDate(renewalDate)) : '',
          })
        "
        :model-value="lineItem.options.autoRenew"
        :disabled="busy"
        @update:model-value="setAutoRenew"
      />

      <div
        v-if="!lineItem.options.autoRenew"
        class="flex items-end justify-between gap-md"
      >
        <Select
          v-if="extendable"
          small
          :label="t('Updates')"
          :options="updateOptions(lineItem, expiryDateOptions)"
          :model-value="lineItem.options.expiryDate"
          :disabled="busy"
          @update:model-value="setExpiryDate"
        />
        <div
          v-for="(adjustment, i) in extendedUpdates"
          :key="i"
          class="grid justify-items-end"
        >
          <strong>{{ formatPrice(adjustment.amount) }}</strong>
          <craft-button
            variant="link"
            size="small"
            .disabled="busy"
            @click="setAutoRenew(true)"
          >
            {{ t('Remove') }}
          </craft-button>
        </div>
      </div>

      <ul v-if="otherAdjustments.length" class="cart-line-item__adjustments">
        <li v-for="(adjustment, i) in otherAdjustments" :key="i">
          <span>{{ adjustment.name }}</span>
          <span>{{ formatPrice(adjustment.amount) }}</span>
        </li>
      </ul>
    </div>
  </li>
</template>

<style scoped>
  .cart-line-item {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: var(--c-spacing-lg);
    padding-block: var(--c-spacing-lg);
    border-block-end: 1px solid var(--c-color-border-quiet);
  }

  .cart-line-item__quiet {
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
  }

  .cart-line-item__adjustments {
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .cart-line-item__adjustments li {
    display: flex;
    justify-content: space-between;
    padding-block: var(--c-spacing-sm);
    border-block-start: 1px solid var(--c-color-border-quiet);
  }
</style>
