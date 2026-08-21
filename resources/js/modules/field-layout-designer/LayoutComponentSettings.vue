<script setup lang="ts">
    /**
     * The field layout designer's component settings, as a slideout panel.
     *
     * Opened with `openSlideoutWith()` rather than `openSlideout()`: the form is
     * built by POSTing the layout currently being edited, which is unsaved client
     * state with no URL to fetch.
     */
    import {ref, shallowRef} from 'vue';
    import {useForm} from '@inertiajs/vue3';
    import {useAppLayout} from '@/common/composables/useAppLayout';
    import {useSlideout} from '@/common/slideouts';
    import FormRenderer from '@/modules/forms/FormRenderer.vue';
    import type {FormPayload} from '@/modules/forms/types';

    type FormErrors = Record<string, string | string[]>;

    const props = defineProps<{
        payload: FormPayload;
        title: string;
        /** Identifies the component being edited, for the refresh request. */
        requestData: () => Record<string, unknown>;
        /** Persists the settings. Rejects with `{errors}` on a validation failure. */
        apply: (values: Record<string, unknown>) => Promise<void>;
    }>();

    const slideout = useSlideout();
    const payload = shallowRef<FormPayload>(props.payload);
    const errors = shallowRef<FormPayload['errors']>(
        props.payload.errors ?? []
    );
    const renderer = ref<{
        currentValues(): FormPayload['values'];
    } | null>(null);

    /**
     * Backs the shell's Save button, and gives it an accurate dirty check for the
     * unsaved-changes prompt — kept in sync with the renderer's values below.
     */
    const form = useForm<any>({
        settings: settingsValues(props.payload.values),
    });

    useAppLayout(() => ({
        title: props.title,
        form,
        onSave: save,
    }));

    function settingsValues(
        values: FormPayload['values']
    ): Record<string, unknown> {
        return (values.settings ?? {}) as Record<string, unknown>;
    }

    function currentValues(): Record<string, unknown> {
        return settingsValues(renderer.value?.currentValues() ?? {});
    }

    function onChange(): void {
        form.settings = currentValues();
    }

    function setErrors(next: FormErrors): void {
        const scope = payload.value.scope ?? [];

        errors.value = Object.entries(next).map(([path, messages]) => ({
            path: [...scope, ...path.split('.')],
            messages: Array.isArray(messages) ? messages : [messages],
        }));
    }

    async function save(): Promise<void> {
        errors.value = [];

        try {
            await props.apply(currentValues());
        } catch (error: any) {
            const responseErrors = error?.response?.data?.errors;

            if (responseErrors) {
                setErrors(responseErrors);
            }

            return;
        }

        // Before close(): closing drops the panel from the store, and its handler
        // with it.
        slideout?.saved();
        slideout?.close({force: true});
    }

    async function refresh(
        values: FormPayload['values'],
        scope: string[] = payload.value.scope ?? []
    ): Promise<FormPayload> {
        const {data} = await Craft.sendActionRequest(
            'POST',
            'fields/refresh-layout-component-settings',
            {
                // `values` is already relative to `scope`, unlike currentValues().
                data: {...props.requestData(), settings: values, scope},
            }
        );

        if (!data.form) {
            throw new Error(
                'The layout component did not return a Form payload.'
            );
        }

        // Server-rendered controls (condition builders, field selects) register
        // their own assets on every render.
        const craft = Craft as typeof Craft & {
            appendHeadHtml(html: string): Promise<void>;
            appendBodyHtml(html: string): Promise<void>;
        };
        await craft.appendHeadHtml(data.headHtml);
        await craft.appendBodyHtml(data.bodyHtml);

        return data.form;
    }
</script>

<template>
    <FormRenderer
        ref="renderer"
        :payload="payload"
        :errors="errors"
        :refresh="payload.refreshable ? refresh : undefined"
        @change="onChange"
    />
</template>
