<script setup lang="ts">
  /** Says the mapping is on its way, under its heading. */
  import '@craftcms/ui/components/spinner/spinner';
  import {t} from '@craftcms/ui';
</script>

<template>
  <div class="mapping-loading" role="status">
    <craft-spinner aria-hidden="true"></craft-spinner>
    <span>{{ t('Loading the mapping…') }}</span>
  </div>
</template>

<style scoped>
  .mapping-loading {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-md);
    color: var(--c-text-quiet);
    --size: 1em;
  }
</style>
