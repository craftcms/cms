<script setup lang="ts">
  /**
   * What the element editor's autosave is doing. A draft save that comes back
   * 400 means the session expired, so it offers a refresh.
   */
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import type {AutosaveStatus} from '@/modules/elements/composables/useElementAutosave';

  const props = defineProps<{
    status: AutosaveStatus;
    savedAt?: string | null;
    error?: string | null;
    httpStatus?: number | null;
  }>();

  const emit = defineEmits<{
    refresh: [];
  }>();

  const message = computed(() => {
    switch (props.status) {
      case 'saving':
        return t('Saving…');
      case 'saved':
        return props.savedAt
          ? t('Saved {timestamp}', {timestamp: props.savedAt})
          : t('Saved');
      case 'failed':
        return props.error ?? t('Couldn’t save draft.');
      default:
        return null;
    }
  });

  const variant = computed(() => {
    switch (props.status) {
      case 'saved':
        return 'success';
      case 'failed':
        return 'danger';
      default:
        return 'neutral';
    }
  });

  const errorCode = computed(() =>
    props.status === 'failed' ? props.httpStatus : null
  );
</script>

<template>
  <div v-if="message" class="flex items-center gap-2">
    <craft-callout
      role="status"
      inline
      padding="none"
      appearance="plain"
      :variant="variant"
      aria-live="polite"
      size="small"
    >
      <craft-spinner
        v-if="status === 'saving'"
        style="--size: 1em"
        slot="icon"
      ></craft-spinner>
      {{ message }}
      <code v-if="errorCode" class="text-xs">{{ errorCode }}</code>
    </craft-callout>

    <craft-button
      v-if="errorCode === 400"
      type="button"
      variant="outline"
      size="small"
      @click="emit('refresh')"
    >
      {{ t('Refresh') }}
    </craft-button>
  </div>
</template>
