<script setup lang="ts">
    import IconPicker from '@/common/form/IconPicker.vue';
    import type {FormControlPayload} from './types';
    import {inputName} from './runtime';

    type IconControlProps = {freeOnly?: boolean};

    defineProps<{
        control: FormControlPayload<IconControlProps>;
        value: string | null;
        label?: string;
        editable: boolean;
        invalid: boolean;
    }>();
    const emit = defineEmits<{
        (event: 'update:value', value: string, kind: 'discrete'): void;
    }>();
</script>

<template>
    <IconPicker
        :model-value="value ?? ''"
        :name="editable ? inputName(control.path) : ''"
        :error="invalid ? 'error' : undefined"
        :free-only="control.props.freeOnly"
        :disabled="!editable"
        @update:model-value="emit('update:value', $event ?? '', 'discrete')"
    />
</template>
