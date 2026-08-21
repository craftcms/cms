<script setup lang="ts">
    import CraftTextarea from '@craftcms/ui/components/textarea/textarea';
    import type {TextExpanderTriggers} from '@craftcms/ui/components/text-expander/text-expander';
    import '@craftcms/ui/components/text-expander/text-expander';
    import {useId} from 'vue';
    import type {FormControlPayload} from './types';
    import {
        ignoreModelValueInitialization,
        inputName,
        serverErrorValidators,
    } from './runtime';

    type TextareaControlProps = {
        rows?: number;
        maxLength?: number;
        placeholder?: string;
        monospace?: boolean;
        textExpanderTriggers?: TextExpanderTriggers;
    };

    defineProps<{
        control: FormControlPayload<TextareaControlProps>;
        value: unknown;
        label?: string;
        editable: boolean;
        invalid: boolean;
        required: boolean;
    }>();
    const inputId = useId();
    const emit = defineEmits<{
        (event: 'update:value', value: string, kind: 'typing'): void;
    }>();

    const onModelValueChanged = ignoreModelValueInitialization((event) => {
        emit(
            'update:value',
            String((event.target as CraftTextarea).modelValue ?? ''),
            'typing'
        );
    });
</script>

<template>
    <craft-textarea
        v-bind="$attrs"
        :name="editable ? inputName(control.path) : ''"
        .modelValue="String(value ?? '')"
        :rows="control.props.rows ?? 2"
        :maxlength="control.props.maxLength"
        :placeholder="control.props.placeholder"
        :monospace="control.props.monospace"
        :required="editable && required"
        :readonly="control.mode === 'readOnly'"
        :disabled="control.mode === 'disabled'"
        .validators="serverErrorValidators(invalid)"
        @model-value-changed="onModelValueChanged"
    >
        <textarea
            :id="inputId"
            slot="input"
            :name="editable ? inputName(control.path) : ''"
            :value="String(value ?? '')"
            :rows="control.props.rows ?? 2"
            :maxlength="control.props.maxLength"
            :placeholder="control.props.placeholder"
            :required="editable && required"
            :readonly="control.mode === 'readOnly'"
            :disabled="control.mode === 'disabled'"
            :aria-invalid="invalid ? 'true' : undefined"
        ></textarea>
    </craft-textarea>
    <craft-text-expander
        v-if="editable && control.props.textExpanderTriggers"
        slot="input"
        :for="inputId"
        .triggers="control.props.textExpanderTriggers"
    />
</template>
