<script setup lang="ts">
  /** The `x` that closes a preview, at the end of a pane's header. */
  import {t} from '@craftcms/ui';
  import {useId} from 'vue';

  const emit = defineEmits<{(e: 'click'): void}>();

  const id = `lp-close-${useId()}`;
</script>

<template>
  <craft-button
    :id="id"
    type="button"
    size="small"
    variant="plain"
    flush="inline-end"
    icon
    @click="emit('click')"
  >
    <craft-icon name="xmark-large" :label="t('Close')"></craft-icon>
  </craft-button>
  <craft-tooltip :for="id">{{ t('Close') }}</craft-tooltip>
</template>
