<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {onScopeDispose, shallowRef} from 'vue';
  import {getPluginChangelog} from '../api';
  import {formatDate} from '../dates';
  import type {ChangelogRelease, Plugin} from '../types';

  const {plugin} = defineProps<{plugin: Plugin}>();

  const releases = shallowRef<ChangelogRelease[] | null>(null);
  const failed = shallowRef(false);
  const controller = new AbortController();
  onScopeDispose(() => controller.abort());

  getPluginChangelog(plugin.id, controller.signal)
    .then((response) => (releases.value = response))
    .catch(() => {
      if (!controller.signal.aborted) {
        failed.value = true;
      }
    });
</script>

<template>
  <craft-callout v-if="failed" variant="danger">
    {{ t('Couldn’t load the changelog.') }}
  </craft-callout>
  <div v-else-if="releases === null" class="flex justify-center p-lg">
    <craft-spinner></craft-spinner>
  </div>
  <ol v-else class="changelog">
    <li
      v-for="release in releases"
      :key="release.version"
      class="changelog__release"
    >
      <div class="grid gap-xs content-start">
        <h2 :id="release.version" class="m-0 text-lg">
          <a :href="`#${release.version}`">
            {{ t('Version {version}', {version: release.version}) }}
          </a>
        </h2>
        <div class="changelog__date">{{ formatDate(release.date) }}</div>
        <div v-if="release.critical">
          <craft-badge fill="red" size="small">{{ t('Critical') }}</craft-badge>
        </div>
      </div>
      <!-- Release notes are HTML the Plugin Store renders from the changelog's Markdown. -->
      <div class="prose" v-html="release.notes"></div>
    </li>
  </ol>
</template>

<style scoped>
  .changelog {
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .changelog__release {
    display: grid;
    gap: var(--c-spacing-lg);
    padding-block: var(--c-spacing-xl);
    border-block-end: 1px solid var(--c-color-border-quiet);
  }

  @media (min-width: 768px) {
    .changelog__release {
      grid-template-columns: 12rem minmax(0, 1fr);
    }
  }

  .changelog__release a {
    color: inherit;
  }

  .changelog__date {
    color: var(--c-text-quiet);
  }
</style>
