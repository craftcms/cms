<script setup lang="ts">
  import type {UrlMethodPair} from '@inertiajs/core';
  import {router} from '@inertiajs/vue3';
  import {computed} from 'vue';
  import type {ActionItem} from '@/common/types';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import UiPage from '@/pages/Ui.vue';
  import type {UiPayload} from '@/modules/ui/types';

  const props = defineProps<{
    ui: UiPayload;
    submit: UrlMethodPair;
    elevatedFields?: string[] | '*';
    deleteAction?: {
      confirm: string;
      label: string;
      url: string;
    };
  }>();

  const actions = computed<ActionItem[]>(() => {
    const deleteAction = props.deleteAction;

    if (!deleteAction) {
      return [];
    }

    return [
      {
        variant: 'danger',
        label: deleteAction.label,
        onClick: () => {
          if (confirm(deleteAction.confirm)) {
            router.delete(deleteAction.url);
          }
        },
      },
    ];
  });

  useAppLayout(() => ({formActions: actions.value}));
</script>

<template>
  <UiPage :ui="ui" :submit="submit" :elevated-fields="elevatedFields" />
</template>
