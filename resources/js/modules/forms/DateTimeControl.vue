<script setup lang="ts">
  import CraftInput from '@craftcms/ui/components/input/input';
  import '@craftcms/ui/components/input-date-time/input-date-time';
  import {t} from '@craftcms/ui/utilities/translate';
  import {computed} from 'vue';
  import type {FormControlPayload} from './types';
  import {
    controlValue,
    ignoreModelValueInitialization,
    inputName,
  } from './runtime';

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
    /**
     * Read {@link model} rather than this: every part of a date is
     * dereferenced on the way to the input, so an absent value would throw.
     * See {@link controlValue}.
     */
    value: DateTimeValue | undefined;
    editable: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: DateTimeValue, kind: 'discrete'): void;
  }>();
  const model = controlValue<DateTimeValue>(() => props.value, {});
  const hasValue = computed(
    () =>
      (props.control.props.showDate && Boolean(model.value.date)) ||
      (props.control.props.showTime && Boolean(model.value.time)) ||
      (props.control.props.showTimeZone && Boolean(model.value.timezone))
  );

  function clear(): void {
    const value = {...model.value};

    if (props.control.props.showDate) value.date = '';
    if (props.control.props.showTime) value.time = '';
    if (props.control.props.showTimeZone) value.timezone = '';

    emit('update:value', value, 'discrete');
  }

  const update = ignoreModelValueInitialization((event) => {
    if (!(event.target instanceof CraftInput)) {
      throw new TypeError('Expected a date-time input event target.');
    }

    const input = event.target;
    const part = input.dataset.dateTimePart;

    if (part !== 'date' && part !== 'time' && part !== 'timezone') {
      return;
    }

    emit(
      'update:value',
      {
        ...model.value,
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
    :timezone="model.timezone"
    .dateValue="model.date ?? ''"
    .timeValue="model.time ?? ''"
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
