<script setup lang="ts">
  /**
   * The buttons beside a form's save button. Each shows its own spinner while
   * the submission it started is in flight.
   */
  import {ButtonVariant} from '@craftcms/ui';
  import type {InertiaForm} from '@inertiajs/vue3';
  import {useFormSubmitter} from '@/common/composables/useFormSubmitter';
  import type {ActionItemButton} from '@/common/types';

  const props = defineProps<{
    form: InertiaForm<any>;
    buttons: Array<ActionItemButton>;
  }>();

  const submitter = useFormSubmitter(() => props.form);

  function onClick(button: ActionItemButton, event: Event) {
    if (button.disabled) {
      return;
    }

    submitter.claim(button.label);
    button.onClick?.(event);
  }
</script>

<template>
  <template v-for="(button, index) in buttons" :key="button.label">
    <craft-button
      :id="
        button.disabled && button.disabledReason
          ? `disabled-form-action-${index}`
          : undefined
      "
      type="button"
      :variant="button.variant ?? ButtonVariant.Solid"
      :loading="submitter.isSubmitting(button.label)"
      :disabled="form.processing || button.disabled"
      :focusable-when-disabled="
        button.disabled && button.disabledReason ? true : undefined
      "
      @click="onClick(button, $event)"
    >
      <craft-icon
        v-if="button.icon"
        :name="button.icon"
        slot="prefix"
      ></craft-icon>
      {{ button.label }}
    </craft-button>
    <craft-tooltip
      v-if="button.disabled && button.disabledReason"
      :for="`disabled-form-action-${index}`"
    >
      {{ button.disabledReason }}
    </craft-tooltip>
  </template>
</template>
