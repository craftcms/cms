<script setup lang="ts">
    import CraftInput from '@craftcms/ui/components/input/input';
    import type {TextExpanderTriggers} from '@craftcms/ui/components/text-expander/text-expander';
    import '@craftcms/ui/components/text-expander/text-expander';
    import {useId} from 'vue';
    import type {FormChangeKind, FormControlPayload} from './types';
    import {
        ignoreModelValueInitialization,
        inputName,
        serverErrorValidators,
    } from './runtime';

    type TextControlProps = {
        inputType?: string;
        min?: number | string;
        max?: number | string;
        step?: number | string;
        maxLength?: number;
        placeholder?: string;
        inputMode?: string;
        autofocus?: boolean;
        autocomplete?: boolean | string;
        autocorrect?: boolean;
        autocapitalize?: boolean;
        size?: number;
        dir?: string;
        monospace?: boolean;
        textExpanderTriggers?: TextExpanderTriggers;
    };

    defineProps<{
        control: FormControlPayload<TextControlProps>;
        value: unknown;
        label?: string;
        editable: boolean;
        invalid: boolean;
        required: boolean;
    }>();
    const inputId = useId();
    const emit = defineEmits<{
        (event: 'update:value', value: string, kind?: FormChangeKind): void;
    }>();

    function autocompleteValue(value: boolean | string | undefined): string {
        if (typeof value === 'string') {
            return value;
        }

        return value === true ? 'on' : 'off';
    }

    const onModelValueChanged = ignoreModelValueInitialization((event) => {
        emit(
            'update:value',
            String((event.target as CraftInput).modelValue ?? ''),
            ['text', 'email', 'url', 'tel', 'password', 'number'].includes(
                String((event.target as CraftInput).type)
            )
                ? 'typing'
                : 'discrete'
        );
    });
</script>

<template>
    <craft-input
        v-bind="$attrs"
        :name="editable ? inputName(control.path) : ''"
        :type="control.props.inputType ?? 'text'"
        .modelValue="String(value ?? '')"
        :min="control.props.min"
        :max="control.props.max"
        :step="control.props.step"
        :maxlength="control.props.maxLength"
        :placeholder="control.props.placeholder"
        .inputMode="control.props.inputMode"
        :autofocus="control.props.autofocus"
        .autocomplete="autocompleteValue(control.props.autocomplete)"
        .autoCorrect="control.props.autocorrect ?? true"
        .autoCapitalize="control.props.autocapitalize ?? true"
        .inputSize="control.props.size"
        :dir="control.props.dir"
        :monospace="control.props.monospace"
        :required="editable && required"
        :readonly="control.mode === 'readOnly'"
        :disabled="
            control.mode === 'disabled' ||
            (control.mode === 'readOnly' && control.props.inputType === 'range')
        "
        .validators="serverErrorValidators(invalid)"
        @model-value-changed="onModelValueChanged"
    >
        <input :id="inputId" slot="input" />
    </craft-input>
    <craft-text-expander
        v-if="editable && control.props.textExpanderTriggers"
        slot="input"
        :for="inputId"
        .triggers="control.props.textExpanderTriggers"
    />
</template>
