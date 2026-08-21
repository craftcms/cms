<script setup lang="ts">
    import CraftInput from '@craftcms/ui/components/input/input';
    import '@craftcms/ui/components/input-date-time/input-date-time';
    import {t} from '@craftcms/ui/utilities/translate';
    import {computed} from 'vue';
    import type {FormControlPayload} from './types';
    import {ignoreModelValueInitialization, inputName} from './runtime';

    type DateTimeValue = {
        date?: string;
        time?: string;
        timezone?: string;
    };

    const props = defineProps<{
        control: FormControlPayload<{
            showDate: boolean;
            showTime: boolean;
            showTimeZone: boolean;
            locale: string;
            min?: string;
            max?: string;
            minuteIncrement: number;
        }>;
        value: DateTimeValue;
        editable: boolean;
        required: boolean;
    }>();
    const emit = defineEmits<{
        (event: 'update:value', value: DateTimeValue, kind: 'discrete'): void;
    }>();
    const hasValue = computed(
        () =>
            (props.control.props.showDate && Boolean(props.value.date)) ||
            (props.control.props.showTime && Boolean(props.value.time)) ||
            (props.control.props.showTimeZone && Boolean(props.value.timezone))
    );

    function clear(): void {
        emit(
            'update:value',
            {
                ...props.value,
                ...(props.control.props.showDate ? {date: ''} : {}),
                ...(props.control.props.showTime ? {time: ''} : {}),
                ...(props.control.props.showTimeZone ? {timezone: ''} : {}),
            },
            'discrete'
        );
    }

    const update = ignoreModelValueInitialization((event) => {
        const input = event.target as CraftInput;
        const part = input.dataset.dateTimePart as
            | keyof DateTimeValue
            | undefined;

        if (!part) {
            return;
        }

        emit(
            'update:value',
            {
                ...props.value,
                [part]: String(input.modelValue ?? ''),
            },
            'discrete'
        );
    });
</script>

<template>
    <craft-input-date-time
        :name="editable ? inputName(control.path) : undefined"
        :locale="control.props.locale"
        :timezone="value.timezone"
        .dateValue="value.date ?? ''"
        .timeValue="value.time ?? ''"
        .showDate="control.props.showDate"
        .showTime="control.props.showTime"
        .showTimezone="control.props.showTimeZone"
        .min="control.props.min"
        .max="control.props.max"
        .minuteIncrement="control.props.minuteIncrement"
        :required="editable && required"
        :readonly="control.mode === 'readOnly'"
        :disabled="control.mode === 'disabled'"
        @model-value-changed="update"
    >
        <button
            v-if="editable && hasValue"
            type="button"
            class="clear-btn"
            :title="t('Clear')"
            :aria-label="t('Clear')"
            @click="clear"
        ></button>
    </craft-input-date-time>
</template>
