<script setup lang="ts">
    import CraftInputHandle from '@craftcms/ui/components/input-handle/input-handle';
    import {toHandle} from '@craftcms/ui/utilities/string';
    import {useInputGenerator} from '@/common/composables/useInputGenerator';
    import type {
        FormChangeKind,
        FormControlPayload,
        FormPayload,
    } from './types';
    import {
        ignoreModelValueInitialization,
        inputName,
        serverErrorValidators,
        valueAt,
    } from './runtime';

    type HandleControlProps = {
        source?: string[];
    };

    const props = defineProps<{
        control: FormControlPayload<HandleControlProps>;
        value: unknown;
        label?: string;
        editable: boolean;
        invalid: boolean;
        required: boolean;
        values: FormPayload['values'];
    }>();
    const emit = defineEmits<{
        (event: 'update:value', value: string, kind?: FormChangeKind): void;
    }>();

    const generator = useInputGenerator(sourceValue, (source) => {
        if (props.editable && props.control.props.source) {
            emit('update:value', toHandle(source), 'typing');
        }
    });

    const onModelValueChanged = ignoreModelValueInitialization((event) => {
        emit(
            'update:value',
            String((event.target as CraftInputHandle).modelValue ?? ''),
            'typing'
        );
    });

    function sourceValue(): string {
        const source = props.control.props.source;

        if (!source) {
            return '';
        }

        return String(
            valueAt(props.values, [
                ...props.control.path.slice(0, -1),
                ...source,
            ]) ?? ''
        );
    }
</script>

<template>
    <craft-input-handle
        :name="editable ? inputName(control.path) : ''"
        .modelValue="String(value ?? '')"
        :required="editable && required"
        :readonly="control.mode === 'readOnly'"
        :disabled="control.mode === 'disabled'"
        .validators="serverErrorValidators(invalid)"
        @model-value-changed="onModelValueChanged"
        @change="generator.markDirty()"
    ></craft-input-handle>
</template>
