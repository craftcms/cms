<script setup lang="ts">
  /**
   * A view setting's visible label, with the Edited badge an element shows
   * when the setting differs from the source's. The control keeps its own
   * label for assistive tech, so this one is hidden from it.
   */
  import {t} from '@craftcms/ui';

  defineProps<{
    label: string;
    edited?: boolean;
  }>();
</script>

<template>
  <div class="view-setting-label">
    <span aria-hidden="true">{{ label }}</span>
    <craft-badge v-if="edited" fill="violet" size="small">
      <craft-icon name="pen-circle" slot="prefix"></craft-icon>
      {{ t('Edited') }}
    </craft-badge>
  </div>
</template>

<style scoped lang="scss">
  // Matches a field label, with room for the badge beside it.
  .view-setting-label {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-md);
    margin-block-end: var(--c-spacing-xs);
    font-weight: bold;
  }
</style>
