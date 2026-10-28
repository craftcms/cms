<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {router} from '@inertiajs/vue3';
  import {until} from '@vueuse/core';
  import {onMounted} from 'vue';
  import {index} from '@actions/PluginStore/PluginStoreController';
  import StoreContainer from '@/modules/plugin-store/components/StoreContainer.vue';
  import {getPlugin} from '@/modules/plugin-store/api';
  import {
    isPluginBuyable,
    pendingTrialRows,
    showError,
    type NewCartItem,
  } from '@/modules/plugin-store/cart';
  import {useCart} from '@/modules/plugin-store/composables/useCart';
  import {useTrialRows} from '@/modules/plugin-store/composables/useActiveTrials';
  import {usePluginLicenseInfo} from '@/modules/plugin-store/composables/usePluginStoreData';
  import {usePluginStoreLayout} from '@/modules/plugin-store/composables/usePluginStoreLayout';

  // Links from license notices land here: a plugin to buy, or no plugin to buy every trial.
  const props = defineProps<{handle: string | null; edition: string | null}>();

  usePluginStoreLayout();

  const {cart, opened, load, add} = useCart();
  const {licenseInfoFor, loaded: licenseInfoLoaded} = usePluginLicenseInfo();
  const {rows, loaded: trialsLoaded} = useTrialRows();

  async function itemsToAdd(): Promise<NewCartItem[]> {
    if (!props.handle) {
      await until(trialsLoaded).toBe(true);
      return pendingTrialRows(rows.value, cart.value).map((row) => row.item);
    }

    const plugin = await getPlugin(props.handle);
    await until(licenseInfoLoaded).toBe(true);

    if (!isPluginBuyable(plugin, licenseInfoFor(plugin.handle))) {
      return [];
    }

    const edition = props.edition ?? plugin.editions[0]!.handle;
    return [{type: 'plugin-edition', plugin: plugin.handle, edition}];
  }

  onMounted(async () => {
    try {
      await load();
      const items = await itemsToAdd();

      if (items.length) {
        await add(items);
      }

      opened.value = items.length > 0 || !props.handle;
    } catch (error) {
      showError(
        error,
        props.handle
          ? t('Couldn’t add item to cart.')
          : t('Couldn’t add all items to the cart.')
      );
    }

    router.visit(index(), {replace: true});
  });
</script>

<template>
  <StoreContainer>
    <div class="flex justify-center p-lg">
      <craft-spinner :label="t('Adding to your cart')"></craft-spinner>
    </div>
  </StoreContainer>
</template>
