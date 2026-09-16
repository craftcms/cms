<script setup lang="ts">
  import {ButtonVariant, t} from '@craftcms/ui';
  import type {InertiaForm} from '@inertiajs/vue3';
  import {ref, watch} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import InlineFlash from '@/common/components/InlineFlash.vue';
  import type {ActionItem, ActionItemButton} from '@/common/types';

  const props = defineProps<{
    form: InertiaForm<any>;
    actionItems?: Array<ActionItem>;
    additionalActions?: Array<ActionItem>;
    additionalButtons?: Array<ActionItemButton>;
    /** Overrides the submit button's text (e.g. "Save draft"). */
    submitLabel?: string;
    readOnly?: boolean;
  }>();

  defineSlots<{
    /** Replaces the default submit button while keeping the action menu. */
    'submit-button'?: () => any;
  }>();

  // Which button owns the in-flight submission, so only it shows a spinner
  // while every button is disabled.
  const primaryButton = 'primary';
  const activeButton = ref<string | null>(null);

  watch(
    () => props.form.processing,
    (processing) => {
      if (processing) {
        // Submissions not initiated by an additional button (submit button,
        // Enter key, form action menu) belong to the primary button.
        activeButton.value ??= primaryButton;
      } else {
        activeButton.value = null;
      }
    }
  );

  function isButtonProcessing(key: string) {
    return props.form.processing && activeButton.value === key;
  }

  function handleAdditionalButtonClick(button: ActionItemButton, event: Event) {
    if (button.disabled) {
      return;
    }

    activeButton.value = button.label;
    button.onClick?.(event);
    // Release the claim if the click didn't start a submission.
    setTimeout(() => {
      if (!props.form.processing) {
        activeButton.value = null;
      }
    });
  }
</script>

<template>
  <div class="flex flex-col justify-center">
    <InlineFlash :is-active="form.recentlySuccessful || form.hasErrors" />
  </div>

  <div v-if="!readOnly" class="flex items-center justify-between gap-2">
    <template v-for="(button, index) in additionalButtons" :key="button.label">
      <craft-button
        :id="
          button.disabled && button.disabledReason
            ? `disabled-form-action-${index}`
            : undefined
        "
        type="button"
        :variant="button.variant ?? ButtonVariant.Solid"
        :loading="isButtonProcessing(button.label)"
        :disabled="
          form.processing || (button.disabled && !button.disabledReason)
        "
        :aria-disabled="
          button.disabled && button.disabledReason ? 'true' : undefined
        "
        @click="handleAdditionalButtonClick(button, $event)"
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

    <craft-button-group v-if="actionItems?.length">
      <slot name="submit-button">
        <craft-button
          type="submit"
          :variant="ButtonVariant.Primary"
          :loading="isButtonProcessing(primaryButton)"
          :disabled="form.processing"
        >
          {{ submitLabel ?? t('Save') }}
        </craft-button>
      </slot>
      <ActionMenu icon="chevron-down" :actions="actionItems">
        <template #invoker="{label}">
          <craft-button
            slot="invoker"
            :variant="ButtonVariant.Primary"
            type="button"
            icon
          >
            <craft-icon name="chevron-down" :label="label"></craft-icon>
          </craft-button>
        </template>
      </ActionMenu>
    </craft-button-group>

    <slot v-else name="submit-button">
      <craft-button
        type="submit"
        :variant="ButtonVariant.Primary"
        :loading="isButtonProcessing(primaryButton)"
        :disabled="form.processing"
      >
        {{ submitLabel ?? t('Save') }}
      </craft-button>
    </slot>

    <ActionMenu v-if="additionalActions?.length" :actions="additionalActions" />
  </div>
</template>

<style scoped lang="scss">
  craft-button[aria-disabled='true'] {
    cursor: default;
    opacity: 0.25;
  }
</style>
