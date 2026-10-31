<script setup lang="ts">
  import {formatDate, t} from '@craftcms/ui';
  import {computed, ref, useId, watch} from 'vue';
  import {
    developer as developerRoute,
    plugin as pluginRoute,
  } from '@actions/PluginStore/PluginStoreController';
  import CpLink from '@/common/components/CpLink.vue';
  import EditionPurchase from './EditionPurchase.vue';
  import InstallCommands from './InstallCommands.vue';
  import PartnerBadge from './PartnerBadge.vue';
  import PluginBand from './PluginBand.vue';
  import PluginIcon from './PluginIcon.vue';
  import {usePluginLicenseInfo} from '../composables/usePluginStoreData';
  import {apiDate} from '../dates';
  import {hasSingleFreeEdition} from '../pricing';
  import type {Plugin} from '../types';

  const {plugin} = defineProps<{plugin: Plugin}>();

  const emit = defineEmits<{'show-reviews': []}>();

  const {licenseInfoFor} = usePluginLicenseInfo();

  const editionHandle = ref(plugin.editions[0]?.handle ?? '');

  // Starts on the edition the install already runs or holds a license for.
  watch(
    () => licenseInfoFor(plugin.handle),
    (info) => {
      const handle = info?.edition ?? info?.licensedEdition;

      if (handle && plugin.editions.some((e) => e.handle === handle)) {
        editionHandle.value = handle;
      }
    },
    {immediate: true}
  );

  const edition = computed(
    () =>
      plugin.editions.find((e) => e.handle === editionHandle.value) ??
      plugin.editions[0]
  );

  const panelIdPrefix = useId();

  const editionIndex = computed(() =>
    Math.max(
      0,
      plugin.editions.findIndex((e) => e.handle === editionHandle.value)
    )
  );

  function onEditionShow(event: Event): void {
    const index = (event.target as HTMLElement & {selectedIndex: number})
      .selectedIndex;
    const handle = plugin.editions[index]?.handle;

    if (handle) {
      editionHandle.value = handle;
    }
  }

  const totalReviews = computed(
    () => plugin.reviewStats?.totalReviews ?? plugin.totalReviews ?? 0
  );

  const reviewsLabel = computed(() =>
    t('{rating} · {totalReviews, plural, =1{# review} other{# reviews}}', {
      rating: Number(
        plugin.reviewStats?.ratingAvg ?? plugin.ratingAvg ?? 0
      ).toFixed(1),
      totalReviews: totalReviews.value,
    })
  );

  // A plain click switches tabs in place; a modified one opens the link as usual.
  function showReviews(event: MouseEvent): void {
    if (
      event.button !== 0 ||
      event.metaKey ||
      event.ctrlKey ||
      event.shiftKey ||
      event.altKey
    ) {
      return;
    }

    event.preventDefault();
    emit('show-reviews');
  }
</script>

<template>
  <section class="plugin-hero" :aria-label="plugin.name">
    <PluginBand :icon-url="plugin.iconUrl" />
    <div class="plugin-hero__body">
      <PluginIcon :url="plugin.iconUrl" size="lg" class="plugin-hero__icon" />

      <div class="plugin-hero__info">
        <h1 class="text-2xl">{{ plugin.name }}</h1>
        <div class="flex items-center gap-sm">
          <CpLink :href="developerRoute({id: plugin.developerId})">
            {{ plugin.developerName }}
          </CpLink>
          <PartnerBadge v-if="plugin.developerPartner" kind="craft" />
        </div>
        <p v-if="plugin.shortDescription" class="plugin-hero__description">
          {{ plugin.shortDescription }}
        </p>
        <dl class="plugin-hero__stats">
          <div v-if="totalReviews">
            <dt class="cp-group-heading">{{ t('Rating') }}</dt>
            <dd>
              <a
                :href="pluginRoute({handle: plugin.handle, tab: 'reviews'}).url"
                @click="showReviews"
              >
                ★ {{ reviewsLabel }}
              </a>
            </dd>
          </div>
          <div>
            <dt class="cp-group-heading">{{ t('Version') }}</dt>
            <dd class="font-mono">{{ plugin.version }}</dd>
          </div>
          <div>
            <dt class="cp-group-heading">{{ t('Last release') }}</dt>
            <dd>{{ formatDate(apiDate(plugin.lastUpdate)) }}</dd>
          </div>
        </dl>
      </div>

      <div v-if="edition" class="plugin-hero__purchase">
        <div class="plugin-hero__panel">
          <div v-if="plugin.editions.length > 1" class="grid gap-xs">
            <span class="cp-group-heading" aria-hidden="true">
              {{ t('Edition') }}
            </span>
            <craft-tabs
              appearance="fill"
              equal-width
              sync-location-hash="false"
              class="plugin-hero__editions"
              :label="t('Edition')"
              .selectedIndex="editionIndex"
              @craft-tab-show="onEditionShow"
            >
              <craft-tab
                v-for="e in plugin.editions"
                :key="e.handle"
                slot="tab"
                :controls="`${panelIdPrefix}-${e.handle}`"
              >
                {{ e.name }}
              </craft-tab>
            </craft-tabs>
          </div>
          <!-- Every edition takes the same spot, so switching doesn't move the page below. -->
          <div class="plugin-hero__purchases">
            <div
              v-for="e in plugin.editions"
              :id="`${panelIdPrefix}-${e.handle}`"
              :key="e.handle"
            >
              <EditionPurchase :plugin="plugin" :edition="e" />
            </div>
          </div>
          <InstallCommands
            v-if="hasSingleFreeEdition(plugin)"
            :plugin="plugin"
          />
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
  .plugin-hero {
    flex: 0 0 calc(100% + var(--cp-container-padding) * 2);
    margin-block-start: calc(var(--c-spacing-lg) * -1);
    margin-inline: calc(var(--cp-container-padding) * -1);
  }

  .plugin-hero__body {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-xl);
    align-items: flex-start;
    padding-block-start: var(--c-spacing-lg);
    padding-inline: var(--cp-container-padding);
  }

  /* Pulled up so it sits over the band. */
  .plugin-hero__icon,
  .plugin-hero__purchase {
    position: relative;
  }

  .plugin-hero__icon {
    margin-block-start: calc((var(--c-spacing-lg) + 1rem) * -1);
  }

  .plugin-hero__info {
    display: flex;
    flex: 1 1 24rem;
    flex-direction: column;
    gap: var(--c-spacing-xs);
    min-inline-size: 0;
  }

  .plugin-hero__description {
    max-inline-size: 40rem;
    margin-block: var(--c-spacing-sm) 0;
    margin-inline: 0;
    font-size: var(--c-text-base);
    line-height: 1.45;
  }

  .plugin-hero__stats {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-md) var(--c-spacing-xl);
    margin-block: var(--c-spacing-lg) 0;
    margin-inline: 0;
  }

  .plugin-hero__stats dd {
    margin-block: var(--c-spacing-xs) 0;
    margin-inline: 0;
  }

  .plugin-hero__purchase {
    display: flex;
    flex: 0 0 18rem;
    flex-direction: column;
    gap: var(--c-spacing-md);
  }

  /* Its panels are the editions below, so it needs no gap before its own. */
  .plugin-hero__editions {
    --c-tabs-gap: 0;
    margin-block-end: var(--c-spacing-sm);
  }

  .plugin-hero__purchases {
    display: grid;
  }

  .plugin-hero__purchases > * {
    grid-area: 1 / 1;
  }

  /* The tabs hide the editions not shown; here they keep their space instead. */
  .plugin-hero__purchases > :global(.hidden) {
    display: block;
    visibility: hidden;
  }

  .plugin-hero__panel {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
    padding: var(--c-spacing-lg);
    border: 1px solid var(--c-color-border-quiet);
    border-radius: var(--c-radius-lg);
    background: var(--c-surface-default);
  }

  /* Only beside the info column; once it wraps below, it would cover it. */
  @media (width >= var(--breakpoint-xl)) {
    .plugin-hero__purchase {
      margin-block-start: calc((var(--c-spacing-lg) + 1rem) * -1);
    }
  }
</style>
