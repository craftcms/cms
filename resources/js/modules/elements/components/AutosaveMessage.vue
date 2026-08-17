<script setup lang="ts">
  import {computed, type Ref} from 'vue';
  import {t} from '@craftcms/ui';
  import type {AutosaveStatus} from '@/modules/elements/composables/useElementAutosave';

  const props = defineProps<{
    autosave: {
      status: Readonly<Ref<AutosaveStatus>>;
      savedAt: Readonly<Ref<string | null>>;
      error: Readonly<Ref<string | null>>;
    };
  }>();

  const message = computed(() => {
    switch (props.autosave.status.value) {
      case 'saving':
        return t('Saving…');
      case 'saved':
        return props.autosave.savedAt.value
          ? t('Saved {timestamp}', {timestamp: props.autosave.savedAt.value})
          : t('Saved');
      case 'failed':
        return props.autosave.error.value ?? t('Couldn’t save draft.');
      default:
        return null;
    }
  });

  const variant = computed(() => {
    switch (props.autosave.status.value) {
      case 'saved':
        return 'success';
      case 'failed':
        return 'danger';
      default:
        return 'neutral';
    }
  });
</script>

<template>
  <craft-callout
    v-if="message"
    role="status"
    inline
    padding="none"
    appearance="plain"
    :variant="variant"
    aria-live="polite"
    size="small"
  >
    <craft-spinner
      v-if="autosave.status.value === 'saving'"
      style="--size: 1em"
      slot="icon"
    ></craft-spinner>
    {{ message }}
  </craft-callout>
</template>

<style scoped lang="scss"></style>
