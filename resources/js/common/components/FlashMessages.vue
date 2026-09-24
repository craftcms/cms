<script setup lang="ts">
  import {computed, watch} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import {useFlashMessages} from '@/common/composables/useFlashMessages';
  import {useAnnouncer} from '@/common/composables/useAnnouncer';

  const {messages} = useFlashMessages();
  const {announce} = useAnnouncer();
  const page = usePage<{
    flash: {
      success: string | null;
      error: string | null;
    };
  }>();

  const errorFlash = computed(
    () => page.props.flash?.error ?? messages.value.error ?? null
  );
  const successFlash = computed(
    () => page.props.flash?.success ?? messages.value.success ?? null
  );

  watch(successFlash, (newMessage) => announce(newMessage));
  watch(errorFlash, (newMessage) => announce(newMessage));
</script>

<template>
  <!-- TODO: this is just temporary placement -->
  <div>
    <template v-if="errorFlash">
      <craft-callout variant="danger" rounded="none" appearance="fill">{{
        errorFlash
      }}</craft-callout>
    </template>
    <template v-if="successFlash">
      <craft-callout variant="success" rounded="none" appearance="fill">{{
        successFlash
      }}</craft-callout>
    </template>
  </div>
</template>

<style scoped lang="scss">
  craft-callout {
    --c-callout-padding-inline: calc(var(--cp-container-padding) - 4px);
  }
</style>
