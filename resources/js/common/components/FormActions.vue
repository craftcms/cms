<script setup lang="ts">
  import {ButtonVariant, t} from '@craftcms/ui';
  import type {InertiaForm} from '@inertiajs/vue3';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import FormActionButtons from '@/common/components/FormActionButtons.vue';
  import InlineFlash from '@/common/components/InlineFlash.vue';
  import {
    PRIMARY_SUBMITTER,
    useFormSubmitter,
  } from '@/common/composables/useFormSubmitter';
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

  const submitter = useFormSubmitter(() => props.form);
</script>

<template>
  <div v-if="!readOnly" class="flex items-center justify-between gap-2">
    <craft-button-group v-if="actionItems?.length">
      <slot name="submit-button">
        <craft-button
          type="submit"
          :variant="ButtonVariant.Primary"
          :loading="submitter.isSubmitting(PRIMARY_SUBMITTER)"
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
        :loading="submitter.isSubmitting(PRIMARY_SUBMITTER)"
        :disabled="form.processing"
      >
        {{ submitLabel ?? t('Save') }}
      </craft-button>
    </slot>
    <FormActionButtons
      v-if="additionalButtons?.length"
      :form="form"
      :buttons="additionalButtons"
    />

    <ActionMenu v-if="additionalActions?.length" :actions="additionalActions" />
  </div>

  <div class="flex flex-col justify-center">
    <InlineFlash :is-active="form.recentlySuccessful || form.hasErrors" />
  </div>
</template>
