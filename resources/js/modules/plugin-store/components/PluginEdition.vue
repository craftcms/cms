<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import EditionActions from './EditionActions.vue';
  import {formatPrice, isEditionFree} from '../pricing';
  import type {Plugin, PluginEdition} from '../types';

  const {
    plugin,
    edition,
    compact = false,
  } = defineProps<{
    plugin: Plugin;
    edition: PluginEdition;
    /** The sidebar version: no feature list, and no frame around a lone free edition. */
    compact?: boolean;
  }>();

  const multiple = plugin.editions.length > 1;
  const framed = !compact || multiple || !isEditionFree(edition);
</script>

<template>
  <div
    class="plugin-edition"
    :class="{
      'plugin-edition--framed': framed,
      'plugin-edition--compact': compact,
    }"
  >
    <div v-if="multiple" class="plugin-edition__name">{{ edition.name }}</div>

    <ul
      v-if="!compact && multiple && edition.features?.length"
      class="plugin-edition__features"
    >
      <li v-for="feature in edition.features" :key="feature.name">
        <craft-icon name="check"></craft-icon>
        {{ feature.name }}
        <craft-info-icon v-if="feature.description">
          {{ feature.description }}
        </craft-info-icon>
      </li>
    </ul>

    <EditionActions :plugin="plugin" :edition="edition" />

    <p
      v-if="!isEditionFree(edition) && !plugin.abandoned"
      class="plugin-edition__renewal"
    >
      {{
        t('Plus {renewalPrice}/year for updates after one year.', {
          renewalPrice: formatPrice(edition.renewalPrice),
        })
      }}
    </p>
  </div>
</template>

<style scoped>
  .plugin-edition {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
  }

  .plugin-edition--framed {
    padding: var(--c-spacing-lg);
    border: 1px solid var(--c-color-border-quiet);
    border-radius: var(--c-radius-md);
  }

  .plugin-edition:not(.plugin-edition--compact) {
    text-align: center;
  }

  .plugin-edition__name {
    font-size: var(--c-text-lg);
    font-weight: bold;
  }

  .plugin-edition__features {
    flex: 1;
    margin: 0;
    padding: 0;
    list-style: none;
    text-align: start;
  }

  .plugin-edition__features li {
    display: flex;
    gap: var(--c-spacing-sm);
    align-items: center;
    padding-block: var(--c-spacing-md);
    border-block-start: 1px solid var(--c-color-border-quiet);
  }

  .plugin-edition__renewal {
    margin: 0;
    color: var(--c-text-quiet);
  }
</style>
