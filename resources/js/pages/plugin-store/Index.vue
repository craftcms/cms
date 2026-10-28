<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {useMediaQuery} from '@vueuse/core';
  import {computed, onScopeDispose, shallowRef} from 'vue';
  import {featured} from '@actions/PluginStore/PluginStoreController';
  import CpLink from '@/common/components/CpLink.vue';
  import PluginGrid from '@/modules/plugin-store/components/PluginGrid.vue';
  import StoreContainer from '@/modules/plugin-store/components/StoreContainer.vue';
  import {getFeaturedSections} from '@/modules/plugin-store/api';
  import {useActiveTrials} from '@/modules/plugin-store/composables/useActiveTrials';
  import {usePluginStoreLayout} from '@/modules/plugin-store/composables/usePluginStoreLayout';
  import type {FeaturedSection} from '@/modules/plugin-store/types';

  usePluginStoreLayout();

  const {trials, failed: trialsFailed} = useActiveTrials();

  const sections = shallowRef<FeaturedSection[] | null>(null);
  const controller = new AbortController();
  onScopeDispose(() => controller.abort());

  getFeaturedSections(controller.signal)
    .then((response) => (sections.value = response))
    .catch(() => (sections.value = []));

  // Two full rows of the grid, whichever width it's at.
  const wide = useMediaQuery('(min-width: 1536px)');
  const sectionLimit = computed(() => (wide.value ? 8 : 6));
</script>

<template>
  <StoreContainer>
    <div v-if="sections === null" class="flex justify-center p-lg">
      <craft-spinner></craft-spinner>
    </div>

    <div v-else class="grid gap-xl">
      <section v-for="section in sections" :key="section.id">
        <div class="flex items-baseline justify-between gap-md">
          <h2 class="m-0">{{ section.title }}</h2>
          <CpLink :href="featured({handle: section.slug})">
            {{ t('See all') }}
          </CpLink>
        </div>
        <PluginGrid :plugins="section.plugins.slice(0, sectionLimit)" />
      </section>

      <section v-if="trials.length || trialsFailed">
        <h2 class="m-0">{{ t('Active Trials') }}</h2>
        <PluginGrid v-if="trials.length" :plugins="trials" trial />
        <p v-if="trialsFailed">{{ t('Couldn’t load active trials.') }}</p>
      </section>
    </div>
  </StoreContainer>
</template>
