<script setup lang="ts">
  import {ButtonVariant, t} from '@craftcms/ui';
  import {useTemplateRef} from 'vue';

  /** A menu pane's footer: an optional destructive action, Cancel and Apply. */
  defineProps<{
    /** Labels a destructive button at the footer's start, when given. */
    clearLabel?: string;
    applyType?: 'submit' | 'button';
    applyDisabled?: boolean;
  }>();

  const emit = defineEmits<{clear: []; cancel: []; apply: []}>();

  const cancelButton = useTemplateRef<HTMLElement>('cancelButton');

  defineExpose({focusCancel: () => cancelButton.value?.focus()});
</script>

<template>
  <craft-button
    v-if="clearLabel"
    slot="footer-start"
    type="button"
    :variant="ButtonVariant.DangerPlain"
    @click="emit('clear')"
    >{{ clearLabel }}</craft-button
  >
  <slot />
  <craft-button
    ref="cancelButton"
    slot="secondary-action"
    type="button"
    :variant="ButtonVariant.Plain"
    @click="emit('cancel')"
    >{{ t('Cancel') }}</craft-button
  >
  <craft-button
    slot="primary-action"
    :type="applyType ?? 'button'"
    .disabled="applyDisabled"
    :variant="ButtonVariant.Primary"
    @click="applyType !== 'submit' && emit('apply')"
    >{{ t('Apply') }}</craft-button
  >
</template>
