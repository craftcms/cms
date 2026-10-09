<script setup lang="ts">
  /**
   * An inline outlet for messages that target `target` — typically placed
   * beside the button that triggered them. Messages without a matching target
   * appear in the default message display instead.
   *
   * Send the target with the request via `messageTargetHeaders()` so
   * messages the server flashes while handling it land here.
   *
   * Both live regions are always rendered, so screen readers announce a
   * message as it appears: errors assertively, everything else politely.
   * Errors stay until the next message or until `busy` turns on again;
   * other messages clear after the user's notification duration.
   */
  import {computed, onBeforeUnmount, ref, watch} from 'vue';
  import TransitionFade from '@/common/components/TransitionFade.vue';
  import {useMessageOutlet} from '@/modules/messages/useMessages';
  import type {CpMessage} from '@/modules/messages';

  const props = withDefaults(
    defineProps<{
      target: string;
      /** Clears the current message when it turns on, e.g. when the form is resubmitted. */
      busy?: boolean;
    }>(),
    {busy: false}
  );

  const current = ref<CpMessage | null>(null);
  let clearTimer: ReturnType<typeof setTimeout> | null = null;

  const isError = computed(() => current.value?.type === 'error');

  function clear() {
    if (clearTimer) {
      clearTimeout(clearTimer);
      clearTimer = null;
    }

    current.value = null;
  }

  useMessageOutlet(props.target, (message) => {
    clear();
    current.value = message;

    const duration =
      (window.Craft as {notificationDuration?: number} | undefined)
        ?.notificationDuration ?? 5000;

    if (message.type !== 'error' && duration > 0) {
      clearTimer = setTimeout(clear, duration);
    }
  });

  watch(
    () => props.busy,
    (busy) => {
      if (busy) {
        clear();
      }
    }
  );

  onBeforeUnmount(clear);
</script>

<template>
  <div class="inline-flex items-center">
    <div role="alert">
      <TransitionFade>
        <craft-callout
          v-if="current && isError"
          variant="danger"
          appearance="plain"
          icon="triangle-exclamation"
          inline
          class="p-0"
          >{{ current.message }}</craft-callout
        >
      </TransitionFade>
    </div>
    <div role="status">
      <TransitionFade>
        <craft-callout
          v-if="current && !isError"
          :variant="current.type === 'success' ? 'success' : 'info'"
          appearance="plain"
          :icon="current.type === 'success' ? 'circle-check' : 'circle-info'"
          inline
          class="p-0"
          >{{ current.message }}</craft-callout
        >
      </TransitionFade>
    </div>
  </div>
</template>
