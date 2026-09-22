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
        'You can edit this draft without resetting completed stages. If it has already been approved, editing it will reopen the final stage.'
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
