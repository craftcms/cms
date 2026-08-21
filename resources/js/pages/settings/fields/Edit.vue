<script setup lang="ts">
    import {t} from '@craftcms/ui';
    import type {UrlMethodPair} from '@inertiajs/core';
    import {ref} from 'vue';
    import type {ActionItem, FormSaveOptions} from '@/common/types';
    import {useAppLayout} from '@/common/composables/useAppLayout';
    import {pathsMatch} from '@/modules/forms/runtime';
    import type {FormChange, FormPayload} from '@/modules/forms/types';
    import FormPage from '@/pages/Form.vue';

    const props = defineProps<{
        form: FormPayload;
        submit: UrlMethodPair;
        refreshUrl: string | null;
        supportedTranslationMethods: Record<string, string[]>;
        formActions?: ActionItem[];
    }>();

    const formPage = ref<{
        save(options?: FormSaveOptions): void;
        setValue(
            path: string[],
            value: unknown,
            kind?: FormChange['kind']
        ): void;
    }>();
    const formActions: ActionItem[] = [
        {
            label: t('Save and add another'),
            onClick: () =>
                formPage.value?.save({
                    data: {addAnother: 1},
                    preserveState: false,
                }),
        },
        ...(props.formActions ?? []),
    ];

    useAppLayout({formActions});

    function onChange(change: FormChange, values: FormPayload['values']): void {
        if (!pathsMatch(change.path, ['type'])) {
            return;
        }

        const supported =
            props.supportedTranslationMethods[String(values.type)] ?? [];

        if (!supported.includes(String(values.translationMethod))) {
            formPage.value?.setValue(
                ['translationMethod'],
                supported[0] ?? 'none',
                change.kind
            );
        }
    }
</script>

<template>
    <FormPage
        ref="formPage"
        :form="form"
        :submit="submit"
        :refresh-url="refreshUrl ?? undefined"
        @change="onChange"
    />
</template>
