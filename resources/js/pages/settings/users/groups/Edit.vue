<script setup lang="ts">
    import type {UrlMethodPair} from '@inertiajs/core';
    import {router} from '@inertiajs/vue3';
    import type {ActionItem} from '@/common/types';
    import {useAppLayout} from '@/common/composables/useAppLayout';
    import FormPage from '@/pages/Form.vue';
    import type {FormPayload} from '@/modules/forms/types';

    const props = defineProps<{
        form: FormPayload;
        submit: UrlMethodPair;
        elevatedFields?: string[] | '*';
        deleteAction?: {
            confirm: string;
            label: string;
            url: string;
        };
    }>();

    const deleteAction = props.deleteAction;
    const actions: ActionItem[] = deleteAction
        ? [
              {
                  variant: 'danger',
                  label: deleteAction.label,
                  onClick: () => {
                      if (confirm(deleteAction.confirm)) {
                          router.delete(deleteAction.url);
                      }
                  },
              },
          ]
        : [];

    useAppLayout({formActions: actions});
</script>

<template>
    <FormPage :form="form" :submit="submit" :elevated-fields="elevatedFields" />
</template>
