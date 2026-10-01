<script setup lang="ts">
  /**
   * Links to the element on the front end, in a new window. The URLs arrive
   * ready to follow: a live element points at its own URL, anything else at a
   * token-minting redirect that lands on the tokenized preview.
   */
  import {t} from '@craftcms/ui';
  import type {ElementPreviewTarget} from '@/modules/elements/composables/useElementEditor';

  const props = defineProps<{
    targets: Array<ElementPreviewTarget>;
  }>();

  function labelFor(target: ElementPreviewTarget): string {
    return props.targets.length === 1 ? t('View') : target.label;
  }
</script>

<template>
  <craft-button
    v-for="target in targets"
    :key="target.url"
    :href="target.url"
    target="_blank"
    variant="link"
    size="small"
    icon="arrow-up-right-from-square"
    icon-position="suffix"
  >
    {{ labelFor(target) }}
    <span class="sr-only">{{ t('Opens in a new window') }}</span>
  </craft-button>
</template>
