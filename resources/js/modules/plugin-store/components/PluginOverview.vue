<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {escapeHtml} from '@craftcms/ui/utilities/escapeHtml';
  import {computed} from 'vue';
  import {plugin as pluginRoute} from '@actions/PluginStore/PluginStoreController';
  import PluginMeta from './PluginMeta.vue';
  import PluginScreenshots from './PluginScreenshots.vue';
  import {usePluginLicenseInfo} from '../composables/usePluginStoreData';
  import type {Plugin} from '../types';

  const {plugin} = defineProps<{plugin: Plugin}>();

  const emit = defineEmits<{'show-reviews': []}>();

  const {licenseInfoFor} = usePluginLicenseInfo();

  const licenseMismatched = computed(
    () => licenseInfoFor(plugin.handle)?.licenseKeyStatus === 'mismatched'
  );

  const mismatchedHtml = t(
    'This license is tied to another Craft install. Visit {accountLink} to detach it, or buy a new license.',
    {
      accountLink:
        '<a href="https://console.craftcms.com" rel="noopener" target="_blank">console.craftcms.com</a>',
    }
  );

  const replacementHtml = computed(() =>
    plugin.replacementHandle
      ? t(
          'The developer recommends using <a href="{url}">{name}</a> instead.',
          {
            name: escapeHtml(
              plugin.replacementName ?? plugin.replacementHandle
            ),
            url: pluginRoute({handle: plugin.replacementHandle}).url,
          }
        )
      : null
  );
</script>

<template>
  <div class="grid gap-xl">
    <craft-callout
      v-if="plugin.abandoned"
      variant="danger"
      icon="triangle-exclamation"
    >
      <strong>{{ t('This plugin is no longer maintained.') }}</strong>
      <span v-if="replacementHtml" v-html="replacementHtml"></span>
    </craft-callout>

    <craft-callout v-if="licenseMismatched" variant="info" icon="lightbulb">
      <span v-html="mismatchedHtml"></span>
    </craft-callout>

    <PluginScreenshots
      v-if="plugin.thumbnailUrls.length"
      :thumbnails="plugin.thumbnailUrls"
      :images="plugin.screenshotUrls"
    />

    <div class="plugin-overview">
      <!-- The description is HTML the Plugin Store renders from the developer's Markdown. -->
      <div
        v-if="plugin.longDescription"
        class="prose"
        v-html="plugin.longDescription"
      ></div>
      <p v-else-if="plugin.shortDescription" class="prose">
        {{ plugin.shortDescription }}
      </p>
      <p v-else>{{ t('No description.') }}</p>

      <PluginMeta :plugin="plugin" @show-reviews="emit('show-reviews')" />
    </div>
  </div>
</template>

<style scoped>
  .plugin-overview {
    display: grid;
    gap: var(--c-spacing-xl);
  }

  .plugin-overview .prose :deep(p) {
    margin-block: 0 1em;
  }

  @media (min-width: 1280px) {
    .plugin-overview {
      grid-template-columns: minmax(0, 1fr) 16rem;
    }
  }
</style>
