<script setup lang="ts">
  import {t} from '@craftcms/ui';

  withDefaults(defineProps<{canStartEditing?: boolean}>(), {
    canStartEditing: true,
  });

  defineEmits<{
    startEditing: [];
  }>();
</script>

<template>
  <craft-callout variant="warning" icon="lock" appearance="fill" rounded="none">
    {{
      t(
        'You can edit this draft without resetting completed stages. If it has already been approved, editing it will reopen the final stage.'
      )
    }}

    <craft-button
      v-if="canStartEditing"
      slot="action"
      type="button"
      appearance="outline"
      size="small"
      inherit
      @click="$emit('startEditing')"
    >
      {{ t('Start editing') }}
    </craft-button>
  </craft-callout>
</template>

<style scoped lang="scss">
  craft-callout {
    --c-callout-padding-inline: calc(var(--cp-container-padding) - 1px);
    --c-callout-icon-padding-inline: 0 var(--c-spacing-sm);
  }
</style>
