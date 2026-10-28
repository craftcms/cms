<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {plugin as pluginRoute} from '@actions/PluginStore/PluginStoreController';
  import CpLink from '@/common/components/CpLink.vue';
  import PluginIcon from './PluginIcon.vue';
  import RatingStars from './RatingStars.vue';
  import {usePluginLicenseInfo} from '../composables/usePluginStoreData';
  import {priceRangeLabel} from '../pricing';
  import type {PluginSummary} from '../types';

  const {plugin, trial = false} = defineProps<{
    plugin: PluginSummary;
    /** Names the edition being trialed, for plugins with more than one. */
    trial?: boolean;
  }>();

  const {licenseInfoFor, isInstalled} = usePluginLicenseInfo();

  const trialEdition = computed(() => {
    if (!trial || plugin.editions.length < 2) {
      return null;
    }

    const edition = licenseInfoFor(plugin.handle)?.edition;
    return plugin.editions.find((e) => e.handle === edition) ?? null;
  });
</script>

<template>
  <CpLink
    :href="pluginRoute({handle: plugin.handle})"
    class="plugin-card"
    block
  >
    <PluginIcon :url="plugin.iconUrl" />
    <div class="plugin-card__body">
      <div class="plugin-card__name">
        <strong>{{ plugin.name }}</strong>
        <craft-badge v-if="trialEdition" size="small" no-prefix>
          {{ trialEdition.name }}
        </craft-badge>
        <craft-icon
          v-if="isInstalled(plugin.handle)"
          name="check"
          class="plugin-card__installed"
          :label="t('Installed')"
        ></craft-icon>
      </div>
      <p class="plugin-card__description">{{ plugin.shortDescription }}</p>
      <div
        v-if="plugin.totalReviews && plugin.ratingAvg"
        class="flex items-center gap-xs text-sm"
      >
        <RatingStars :rating="plugin.ratingAvg" size="sm" />
        <span class="plugin-card__quiet">({{ plugin.totalReviews }})</span>
      </div>
      <div v-if="plugin.abandoned" class="plugin-card__abandoned">
        {{ t('Abandoned') }}
      </div>
      <div v-else class="plugin-card__quiet">{{ priceRangeLabel(plugin) }}</div>
    </div>
  </CpLink>
</template>

<style scoped>
  .plugin-card {
    display: flex;
    gap: var(--c-spacing-md);
    align-items: flex-start;
    block-size: 100%;
    padding-block: var(--c-spacing-lg);
    border-block-end: 1px solid var(--c-color-border-quiet);
    color: var(--c-text-default);
    text-decoration: none;
  }

  .plugin-card:hover strong {
    color: var(--c-color-accent-on-quiet, var(--c-color-accent-fill-loud));
  }

  .plugin-card__body {
    display: grid;
    gap: var(--c-spacing-xs);
    min-inline-size: 0;
  }

  .plugin-card__name {
    display: flex;
    gap: var(--c-spacing-sm);
    align-items: center;
  }

  .plugin-card__installed {
    margin-inline-start: auto;
    color: var(--c-text-quiet);
  }

  .plugin-card__description {
    display: -webkit-box;
    margin: 0;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
  }

  .plugin-card__quiet {
    color: var(--c-text-quiet);
  }

  .plugin-card__abandoned {
    color: var(--c-color-danger-on-quiet);
  }
</style>
