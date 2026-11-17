<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import useCraftData from '@/common/composables/useCraftData';
  import CpLink from '@/common/components/CpLink.vue';
  import {upgradeCraft} from '@actions/PluginStore/PluginStoreController';

  const {app} = useCraftData();
</script>

<template>
  <div class="edition-info">
    <span class="edition-info__app">
      <span lang="en" class="edition-info__app">
        Craft CMS
        <span class="edition-logo">
          <span aria-hidden="true">{{ app.edition.name }}</span>
          <span class="sr-only">{{
            t('{edition} edition', {edition: app.edition.name})
          }}</span>
        </span>
      </span>
      {{ app.version }}
    </span>
    <CpLink v-if="app.canUpgradeEdition" :href="upgradeCraft().url">
      {{ t('Upgrade to Craft Pro') }}
    </CpLink>
  </div>
</template>

<style scoped lang="css">
  .edition-info {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    column-gap: var(--c-spacing-md);
    row-gap: var(--c-spacing-xs);
    font-size: var(--c-text-sm);
    color: var(--c-text-quiet);
  }

  .edition-info__app {
    display: inline-flex;
    align-items: center;
    gap: calc(var(--c-spacing) * 1.5);
  }

  .edition-logo {
    user-select: none;
    border: 1px solid currentColor;
    border-radius: 3px;
    display: inline-flex;
    box-sizing: content-box;
    font-size: 10px;
    padding-block: 3px;
    padding-inline: 4px 3px;
    line-height: 1;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
  }
</style>
