<script setup lang="ts">
  /**
   * A button that opens a CP screen in a slideout and reports back when it
   * saves.
   *
   * Callers listen for `success` and refresh whatever the screen affects —
   * registering `onSaved` also opts the panel out of its default
   * reload-the-whole-page-behind behavior, which is the point: the caller knows
   * which props actually changed.
   */
  import {useTemplateRef} from 'vue';
  import {ButtonVariant} from '@craftcms/ui';
  import {useSlideoutOpener} from '@/common/slideouts';

  defineProps<{
    url: string;
  }>();

  const emit = defineEmits<{
    (e: 'success'): void;
  }>();

  const invoker = useTemplateRef<HTMLElement>('invoker');
  const {open} = useSlideoutOpener();

  function openSlideout(url: string) {
    // Focus goes back to this button on close, courtesy of `opener`.
    void open(url, {
      opener: invoker.value,
      onSaved: () => emit('success'),
    });
  }
</script>

<template>
  <craft-button
    type="button"
    :variant="ButtonVariant.Dashed"
    @click="openSlideout(url)"
    ref="invoker"
  >
    <slot></slot>
  </craft-button>
</template>
