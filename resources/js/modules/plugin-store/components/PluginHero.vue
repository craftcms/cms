<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {formatNumber} from '@craftcms/ui/utilities/format';
  import {computed, ref, watch} from 'vue';
  import {
    developer as developerRoute,
    plugin as pluginRoute,
  } from '@actions/PluginStore/PluginStoreController';
  import CpLink from '@/common/components/CpLink.vue';
  import Select from '@/common/form/Select.vue';
  import EditionActions from './EditionActions.vue';
  import PartnerBadge from './PartnerBadge.vue';
  import PluginIcon from './PluginIcon.vue';
  import {usePluginLicenseInfo} from '../composables/usePluginStoreData';
  import {formatPrice, isEditionFree} from '../pricing';
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

  const editionOptions = computed(() =>
    plugin.editions.map((e) => ({
      value: e.handle,
      label: `${e.name} — ${isEditionFree(e) ? t('Free') : formatPrice(e.price)}`,
    }))
  );

  const totalReviews = computed(
    () => plugin.reviewStats?.totalReviews ?? plugin.totalReviews ?? 0
  );

  const reviewsLabel = computed(() =>
    totalReviews.value
      ? t('{rating} · {totalReviews, plural, =1{# review} other{# reviews}}', {
          rating: Number(
            plugin.reviewStats?.ratingAvg ?? plugin.ratingAvg ?? 0
          ).toFixed(1),
          totalReviews: totalReviews.value,
        })
      : t('No reviews yet')
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
    <PluginIcon :url="plugin.iconUrl" size="lg" />

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
        <div>
          <dt class="cp-group-heading">{{ t('Rating') }}</dt>
          <dd>
            <a
              v-if="totalReviews"
              :href="pluginRoute({handle: plugin.handle, tab: 'reviews'}).url"
              @click="showReviews"
            >
              ★ {{ reviewsLabel }}
            </a>
            <template v-else>{{ reviewsLabel }}</template>
          </dd>
        </div>
        <div>
          <dt class="cp-group-heading">{{ t('Active Installs') }}</dt>
          <dd>{{ formatNumber(plugin.activeInstalls) }}</dd>
        </div>
        <div>
          <dt class="cp-group-heading">{{ t('Version') }}</dt>
          <dd>{{ plugin.version }}</dd>
        </div>
        <div>
          <dt class="cp-group-heading">{{ t('Compatibility') }}</dt>
          <dd>{{ plugin.compatibility }}</dd>
        </div>
      </dl>
    </div>

    <div v-if="edition" class="plugin-hero__purchase">
      <Select
        v-if="plugin.editions.length > 1"
        :label="t('Edition')"
        :options="editionOptions"
        v-model="editionHandle"
      />
      <EditionActions :plugin="plugin" :edition="edition" />
      <p
        v-if="!isEditionFree(edition) && !plugin.abandoned"
        class="plugin-hero__renewal"
      >
        {{
          t('Plus {renewalPrice}/year for updates after one year.', {
            renewalPrice: formatPrice(edition.renewalPrice),
          })
        }}
      </p>
    </div>
  </section>
</template>

<style scoped>
  .plugin-hero {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-xl);
    align-items: center;
    inline-size: 100%;
    box-sizing: border-box;
    padding: var(--c-spacing-xl);
    border: 1px solid var(--c-color-border-quiet);
    border-radius: var(--c-radius-lg);
    background: var(--c-surface-sunken);
  }

  .plugin-hero__info {
    display: flex;
    flex: 1 1 24rem;
    flex-direction: column;
    gap: var(--c-spacing-sm);
    min-inline-size: 0;
  }

  .plugin-hero__description {
    max-inline-size: 40rem;
    margin: 0;
    font-size: var(--c-text-base);
    line-height: 1.45;
  }

  .plugin-hero__stats {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-md) var(--c-spacing-xl);
    margin-block: var(--c-spacing-sm) 0;
    margin-inline: 0;
  }

  .plugin-hero__stats dd {
    margin-block: var(--c-spacing-2xs) 0;
    margin-inline: 0;
  }

  .plugin-hero__purchase {
    display: flex;
    flex: 0 0 16rem;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }

  .plugin-hero__renewal {
    margin: 0;
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
  }
</style>
