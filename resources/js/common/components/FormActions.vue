<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import type {InertiaForm} from '@inertiajs/vue3';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import InlineFlash from '@/common/components/InlineFlash.vue';
  import type {ActionItem} from '@/common/types';

  defineProps<{
    form: InertiaForm<any>;
    actionItems?: Array<ActionItem>;
    additionalActions?: Array<ActionItem>;
    readOnly?: boolean;
  }>();

  defineSlots<{
    /** Replaces the default submit button while keeping the action menu. */
    'submit-button'?: () => any;
  }>();
</script>

<template>
  <InlineFlash :is-active="form.recentlySuccessful || form.hasErrors" />

  <div v-if="!readOnly" class="flex items-center justify-end gap-2">
    <craft-button-group v-if="actionItems?.length">
      <slot name="submit-button">
        <craft-button type="submit" variant="accent" :loading="form.processing">
          {{ t('Save') }}
        </craft-button>
      </slot>
      <ActionMenu icon="chevron-down" :actions="actionItems">
        <template #invoker="{label}">
          <craft-button slot="invoker" variant="accent" type="button" icon>
            <craft-icon name="chevron-down" :label="label"></craft-icon>
          </craft-button>
        </template>
      </ActionMenu>
    </craft-button-group>

    <slot v-else name="submit-button">
      <craft-button type="submit" variant="accent" :loading="form.processing">
        {{ t('Save') }}
      </craft-button>
    </slot>

    <ActionMenu v-if="additionalActions?.length" :actions="additionalActions" />
  </div>
</template>
