<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {
    developer as developerRoute,
    plugin as pluginRoute,
  } from '@actions/PluginStore/PluginStoreController';
  import CpLink from '@/common/components/CpLink.vue';
  import PluginBand from './PluginBand.vue';
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
  <article class="plugin-card">
    <div class="plugin-card__head">
      <PluginBand :icon-url="plugin.iconUrl" />
      <craft-badge
        v-if="!plugin.abandoned"
        no-prefix
        class="plugin-card__price"
      >
        {{ priceRangeLabel(plugin) }}
      </craft-badge>
    </div>
    <div class="plugin-card__body">
      <CpLink
        :href="pluginRoute({handle: plugin.handle})"
        tabindex="-1"
        class="plugin-card__icon"
      >
        <PluginIcon :url="plugin.iconUrl" />
      </CpLink>
      <div class="plugin-card__heading">
        <CpLink :href="pluginRoute({handle: plugin.handle})">
          <strong>{{ plugin.name }}</strong>
        </CpLink>
        <span v-if="plugin.abandoned" class="plugin-card__abandoned">
          {{ t('Abandoned') }}
        </span>
      </div>
      <CpLink
        :href="developerRoute({id: plugin.developerId})"
        class="plugin-card__developer"
      >
        {{ plugin.developerName }}
      </CpLink>
      <p class="plugin-card__description">{{ plugin.shortDescription }}</p>
      <div class="plugin-card__meta">
        <span
          v-if="plugin.totalReviews && plugin.ratingAvg"
          class="flex items-center gap-xs"
        >
          <RatingStars :rating="plugin.ratingAvg" size="sm" />
          ({{ plugin.totalReviews }})
        </span>
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
    </div>
  </article>
</template>

<style scoped>
  .plugin-card {
    display: flex;
    flex-direction: column;
    block-size: 100%;
    overflow: hidden;
    border: 1px solid var(--c-color-border-quiet);
    border-radius: var(--c-radius-lg);
    background: var(--c-surface-default);
  }

  .plugin-card__body {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: var(--c-spacing-sm);
    padding-block: 0 var(--c-spacing-lg);
    padding-inline: var(--c-spacing-lg);
  }

  .plugin-card__icon {
    position: relative;
    align-self: flex-start;
    margin-block: -2rem var(--c-spacing-md);
  }

  .plugin-card__heading {
    display: flex;
    gap: var(--c-spacing-md);
    align-items: baseline;
    justify-content: space-between;
  }

  .plugin-card__developer {
    margin-block: calc(var(--c-spacing-sm) * -1) var(--c-spacing-xs);
    font-size: var(--c-text-sm);
  }

  /* On the surface color, so it reads as white in light mode and dark in dark. */
  .plugin-card__head {
    position: relative;
  }

  .plugin-card__price {
    --c-color-fill-quiet: var(--c-surface-raised);
    --c-color-border-quiet: transparent;
    --c-color-on-quiet: var(--c-text-default);
    position: absolute;
    inset-block: 0;
    /* The same as the space above and below it in the 2.5rem band. */
    inset-inline-end: calc((2.5rem - var(--c-size-control-sm)) / 2);
    block-size: fit-content;
    margin-block: auto;
    border-radius: var(--c-radius-full);
    box-shadow: var(--c-shadow-xs);
    font-weight: 600;
  }

  .plugin-card__description {
    display: -webkit-box;
    margin: 0;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
  }

  .plugin-card__meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-xs) var(--c-spacing-md);
    align-items: center;
    margin-block-start: auto;
    padding-block-start: var(--c-spacing-xs);
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
  }

  .plugin-card__installed {
    margin-inline-start: auto;
  }

  .plugin-card__abandoned {
    flex-shrink: 0;
    color: var(--c-color-danger-on-quiet);
    font-size: var(--c-text-sm);
  }
</style>
