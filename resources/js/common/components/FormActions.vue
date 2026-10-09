<script setup lang="ts">
  import {ButtonVariant} from '@craftcms/ui';
  import type {InertiaForm} from '@inertiajs/vue3';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import FormActionButtons from '@/common/components/FormActionButtons.vue';
  import PrimaryActionButton from '@/common/components/PrimaryActionButton.vue';
  import type {ActionItem, ActionItemButton} from '@/common/types';

  defineProps<{
    form: InertiaForm<any>;
    actionItems?: Array<ActionItem>;
    additionalActions?: Array<ActionItem>;
    additionalButtons?: Array<ActionItemButton>;
    /** Overrides the submit button's text (e.g. "Save draft"). */
    submitLabel?: string;
    readOnly?: boolean;
    saveDisabled?: boolean;
  }>();

  defineSlots<{
    /** Replaces the Save button while keeping the action menu. */
    'primary-action'?: () => any;
  }>();
</script>

<template>
  <div v-if="!readOnly" class="flex items-center justify-between gap-2">
    <craft-button-group v-if="!saveDisabled && actionItems?.length">
      <slot name="primary-action">
        <PrimaryActionButton :form="form" :label="submitLabel" />
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

    <slot v-else-if="!saveDisabled" name="primary-action">
      <PrimaryActionButton :form="form" :label="submitLabel" />
    </slot>
    <FormActionButtons
      v-if="additionalButtons?.length"
      :form="form"
      :buttons="additionalButtons"
    />

    <ActionMenu v-if="additionalActions?.length" :actions="additionalActions" />
  </div>
</template>
