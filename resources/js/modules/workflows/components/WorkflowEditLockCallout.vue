<script setup lang="ts">
  import {t} from '@craftcms/ui';

  defineEmits<{
    startEditing: [];
  }>();
</script>

<template>
  <craft-callout variant="warning" icon="lock" appearance="fill">
    {{
      t(
        'This draft is in review. Editing it will invalidate the current review and require a new review.'
      )
    }}

    <craft-button
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
