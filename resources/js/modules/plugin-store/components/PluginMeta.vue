<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {category as categoryRoute} from '@actions/PluginStore/PluginStoreController';
  import CpLink from '@/common/components/CpLink.vue';
  import ActiveInstalls from './ActiveInstalls.vue';
  import GithubActivity from './GithubActivity.vue';
  import InstallInstructions from './InstallInstructions.vue';
  import MetaStat from './MetaStat.vue';
  import PluginEdition from './PluginEdition.vue';
  import ReviewsStat from './ReviewsStat.vue';
  import {useCoreData} from '../composables/usePluginStoreData';
  import {formatDate} from '../dates';
  import {reportIssueUrl} from '../links';
  import craftIcon from '../images/craft-mask.svg?raw';
  import cloudIcon from '../images/cloud.svg?raw';
  import graphqlIcon from '../images/graphql.svg?raw';
  import type {Plugin} from '../types';

  const {plugin} = defineProps<{plugin: Plugin}>();

  const emit = defineEmits<{'show-reviews': []}>();

  const {categories} = useCoreData();

  const pluginCategories = computed(() =>
    categories.value.filter((c) =>
      plugin.categoryIds.some((id) => Number(id) === Number(c.id))
    )
  );

  const licenseLabels: Record<string, string> = {
    'apache-2.0': 'Apache-2.0',
    craft: 'Craft',
    'gpl-2.0': 'GPL-2.0',
    'gpl-3.0': 'GPL-3.0',
    mit: 'MIT',
  };

  const compatibility = computed(() => [
    {svg: craftIcon, label: plugin.compatibility},
    ...(plugin.cloudTested
      ? [{svg: cloudIcon, label: 'Tested on Craft Cloud'}]
      : []),
    ...(plugin.supportsGql
      ? [{svg: graphqlIcon, label: 'Supports GraphQL'}]
      : []),
  ]);
</script>

<template>
  <div class="flex flex-col gap-lg">
    <div class="grid gap-md">
      <PluginEdition
        v-for="edition in plugin.editions"
        :key="edition.handle"
        :plugin="plugin"
        :edition="edition"
        compact
      />
    </div>

    <div class="grid gap-sm">
      <craft-button
        v-if="plugin.documentationUrl"
        :href="plugin.documentationUrl"
        target="_blank"
        rel="noopener"
        icon="book"
      >
        {{ t('Documentation') }}
        <span class="sr-only">{{ t('Opens in a new window') }}</span>
      </craft-button>
      <craft-button
        :href="plugin.repository"
        target="_blank"
        rel="noopener"
        icon="code-branch"
      >
        {{ t('Repository') }}
        <span class="sr-only">{{ t('Opens in a new window') }}</span>
      </craft-button>
    </div>

    <dl class="m-0">
      <InstallInstructions :plugin="plugin" />
      <ReviewsStat :plugin="plugin" @show-reviews="emit('show-reviews')" />
      <ActiveInstalls :plugin="plugin" />
      <div class="grid grid-cols-2">
        <MetaStat :label="t('Version')">{{ plugin.version }}</MetaStat>
        <MetaStat :label="t('License')">
          {{ licenseLabels[plugin.license] ?? plugin.license }}
        </MetaStat>
      </div>
      <div :class="{'grid grid-cols-2': plugin.totalReleases}">
        <MetaStat :label="t('Compatibility')">
          <ul class="plugin-meta__compatibility">
            <li v-for="item in compatibility" :key="item.label">
              <span class="plugin-meta__compat-icon" v-html="item.svg"></span>
              {{ item.label }}
            </li>
          </ul>
        </MetaStat>
        <MetaStat v-if="plugin.totalReleases" :label="t('Total releases')">
          {{ plugin.totalReleases }}
        </MetaStat>
      </div>
      <MetaStat :label="t('Last release')">
        {{ formatDate(plugin.lastUpdate) }}
      </MetaStat>
      <MetaStat v-if="pluginCategories.length" :label="t('Categories')">
        <ul class="plugin-meta__categories">
          <li v-for="category in pluginCategories" :key="category.id">
            <CpLink :href="categoryRoute({id: category.id})">
              <craft-chip>{{ category.title }}</craft-chip>
            </CpLink>
          </li>
        </ul>
      </MetaStat>
      <GithubActivity :plugin="plugin" />
    </dl>

    <div>
      <craft-button variant="danger" :href="reportIssueUrl(plugin)" icon="flag">
        {{ t('Report plugin') }}
      </craft-button>
    </div>
  </div>
</template>

<style scoped>
  .plugin-meta__compatibility,
  .plugin-meta__categories {
    display: grid;
    gap: var(--c-spacing-sm);
    margin-block: var(--c-spacing-sm) 0;
    margin-inline: 0;
    padding: 0;
    list-style: none;
  }

  .plugin-meta__categories {
    display: flex;
    flex-wrap: wrap;
  }

  .plugin-meta__compatibility li {
    display: flex;
    gap: var(--c-spacing-sm);
    align-items: center;
  }

  .plugin-meta__compat-icon {
    display: inline-flex;
    inline-size: 1.25rem;
    block-size: 1.25rem;
    opacity: 0.7;
  }

  .plugin-meta__compat-icon :deep(svg) {
    inline-size: 100%;
    block-size: 100%;
  }
</style>
