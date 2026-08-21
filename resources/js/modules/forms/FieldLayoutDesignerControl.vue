<script setup lang="ts">
    import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
    import {actionClient} from '@craftcms/ui';
    import type {FormControlPayload} from './types';
    import {inputName} from './runtime';
    import {useServerRenderedControl} from './useServerRenderedControl';

    type FieldLayoutDesignerProps = {
        elementType: string;
        customizableTabs: boolean;
        withGeneratedFields: boolean;
        withCardViewDesigner: boolean;
    };

    const props = defineProps<{
        control: FormControlPayload<FieldLayoutDesignerProps>;
        value: Record<string, unknown>;
        editable: boolean;
    }>();
    const emit = defineEmits<{
        (
            event: 'update:value',
            value: Record<string, unknown>,
            kind: 'discrete'
        ): void;
    }>();
    const {host, html} = useServerRenderedControl({
        value: () => props.value,
        dependencies: [() => props.control.props, () => props.editable],
        async render() {
            const response = await actionClient.post<{html: string}>(
                'fields/render-field-layout-designer',
                {
                    value: props.value,
                    elementType: props.control.props.elementType,
                    name: inputName(props.control.path),
                    disabled: !props.editable,
                    customizableTabs: props.control.props.customizableTabs,
                    withGeneratedFields:
                        props.control.props.withGeneratedFields,
                    withCardViewDesigner:
                        props.control.props.withCardViewDesigner,
                }
            );

            return response.data.html;
        },
        readValue(host) {
            const input = host.querySelector<HTMLInputElement>(
                '[data-config-input]'
            );

            if (!input) {
                return undefined;
            }

            const value = JSON.parse(input.value) as Record<string, unknown>;
            const generatedFields = host.querySelector<
                HTMLElement & {serialize(): unknown[]}
            >('craft-generated-fields-table');

            return generatedFields
                ? {...value, generatedFields: generatedFields.serialize()}
                : value;
        },
        update: (value) => emit('update:value', value, 'discrete'),
    });
</script>

<template>
    <div ref="host">
        <DynamicHtmlRenderer :html="html" />
    </div>
</template>
