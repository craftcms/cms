<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import EditionActions from './EditionActions.vue';
  import {usePluginLicenseInfo} from '../composables/usePluginStoreData';
  import {upgradePrice} from '../editionActions';
  import {formatPrice, isEditionFree} from '../pricing';
  import type {Plugin, PluginEdition} from '../types';

  const {plugin, edition} = defineProps<{
    plugin: Plugin;
    edition: PluginEdition;
  }>();

  const {licenseInfoFor} = usePluginLicenseInfo();

  const upgrade = computed(() =>
    upgradePrice(plugin, edition, licenseInfoFor(plugin.handle))
  );
</script>

<template>
  <div class="edition-purchase">
    <div class="edition-purchase__price">
      <template v-if="isEditionFree(edition)">{{ t('Free') }}</template>
      <template v-else-if="upgrade !== null">
        <del>{{ formatPrice(edition.price) }}</del>
        {{ formatPrice(upgrade) }}
      </template>
      <template v-else>{{ formatPrice(edition.price) }}</template>
    </div>
    <p v-if="isEditionFree(edition)" class="edition-purchase__renewal">
      {{ t('No purchase necessary.') }}
    </p>
    <p v-else-if="!plugin.abandoned" class="edition-purchase__renewal">
      {{
        t('Plus {renewalPrice}/year for updates after one year.', {
          renewalPrice: formatPrice(edition.renewalPrice),
        })
      }}
    </p>
    <EditionActions :plugin="plugin" :edition="edition" :show-price="false" />
  </div>
</template>

<style scoped>
  .edition-purchase {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
  }

  .edition-purchase__price {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1.1;
  }

  .edition-purchase__price del {
    color: var(--c-text-quiet);
    font-size: var(--c-text-lg);
    font-weight: 400;
  }

  .edition-purchase__renewal {
    margin-block: calc(var(--c-spacing-sm) * -1) var(--c-spacing-sm);
    margin-inline: 0;
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
  }
</style>
