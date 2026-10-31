<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {index} from '@actions/PluginStore/PluginStoreController';
  import CpContainer from '@/common/components/CpContainer.vue';
  import CpLink from '@/common/components/CpLink.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import PluginSearch from './PluginSearch.vue';
  import CartButton from './cart/CartButton.vue';
  import {useCoreData} from '../composables/usePluginStoreData';

  const {failed} = useCoreData();
</script>

<template>
  <LayoutSlot name="page-header">
    <div class="store-header">
      <CpLink :href="index()" class="store-header__title text-xl font-bold">
        {{ t('Plugin Store') }}
      </CpLink>
      <div class="flex items-center gap-sm">
        <PluginSearch />
        <CartButton />
      </div>
    </div>
  </LayoutSlot>
  <CpContainer class="py-lg">
    <craft-callout v-if="failed" variant="danger" icon="triangle-exclamation">
      {{ t('The Plugin Store is not available, please try again later.') }}
    </craft-callout>
    <slot v-else></slot>
  </CpContainer>
</template>

<style scoped>
  .store-header {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-md);
    align-items: center;
    justify-content: space-between;
  }

  .store-header__title {
    color: var(--c-text-default);
    text-decoration: none;
  }
</style>
