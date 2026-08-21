<script setup lang="ts">
    import '../link-field/craft-link-field';
    import type {
        LinkFieldValue,
        LinkTypeConfig,
    } from '../link-field/craft-link-field';
    import type {FormControlPayload} from './types';
    import {inputName} from './runtime';

    type LinkControlProps = {
        types: LinkTypeConfig[];
        showLabelField?: boolean;
        advancedFields?: Array<'urlSuffix' | 'title'>;
    };
    type LinkValue = Pick<LinkFieldValue, 'type' | 'value'> &
        Partial<Pick<LinkFieldValue, 'label' | 'title' | 'urlSuffix'>>;

    defineProps<{
        control: FormControlPayload<LinkControlProps>;
        value: LinkValue;
        editable: boolean;
    }>();
    const emit = defineEmits<{
        (event: 'update:value', value: LinkValue, kind: 'discrete'): void;
    }>();

    function apply(event: Event): void {
        const {label, title, type, urlSuffix, value} = (
            event as CustomEvent<LinkFieldValue>
        ).detail;
        emit(
            'update:value',
            {label, title, type, urlSuffix, value},
            'discrete'
        );
    }
</script>

<template>
    <div>
        <craft-link-field
            :types="control.props.types"
            .modelValue="value"
            :name="editable ? inputName(control.path) : ''"
            :show-label-field="control.props.showLabelField"
            .advancedFields="control.props.advancedFields"
            :disabled="!editable"
            @apply="apply"
        />
    </div>
</template>
