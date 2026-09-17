<script setup lang="ts">
  /**
   * Opens the element on the front end. The URLs arrive ready to follow: a live
   * element points at its own URL, anything else at a token-minting redirect
   * that lands on the tokenized preview.
   */
  import {t} from '@craftcms/ui';

  export interface ElementPreviewTarget {
    label: string;
    url: string;
    icon?: string;
  }

  const props = defineProps<{
    targets: Array<ElementPreviewTarget>;
  }>();

  function labelFor(target: ElementPreviewTarget): string {
    return props.targets.length === 1 ? t('View') : target.label;
  }

  function open(target: ElementPreviewTarget): void {
    window.open(target.url, '_blank', 'noopener');
  }
</script>

<template>
  <craft-button
    v-for="target in targets"
    :key="target.url"
    type="button"
    variant="link"
    size="small"
    icon="arrow-up-right-from-square"
    icon-position="suffix"
    @click="open(target)"
  >
    {{ labelFor(target) }}
  </craft-button>
</template>
