<script setup lang="ts">
  import {computed} from 'vue';
  import {inputName, isRecord} from './runtime';
  import TextControl from './TextControl.vue';
  import type {
    UiChangeKind,
    UiControlPayload,
    TextControlProps,
    UiValue,
  } from './types';

  defineOptions({inheritAttrs: false});

  /** A number formatted for its locale, such as `1,999.5` in `en-US`. */
  type NumberValue = {value: string | null; locale: string};

  const props = defineProps<{
    control: UiControlPayload<TextControlProps>;
    value: UiValue;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (
      event: 'update:value',
      value: string | NumberValue,
      kind?: UiChangeKind
    ): void;
  }>();

  // Without a locale, the control is a native number input holding the raw
  // number. With one, it's a text input holding the number formatted for that
  // locale, posted alongside it. A bare number is still raw.
  const locale = computed(() => {
    if (!props.control.props.locale || !isRecord(props.value)) {
      return undefined;
    }

    return typeof props.value.locale === 'string'
      ? props.value.locale
      : props.control.props.locale;
  });
  const text = computed(() =>
    isRecord(props.value) ? (props.value.value ?? null) : props.value
  );
  const inputControl = computed(() =>
    locale.value
      ? {...props.control, path: [...props.control.path, 'value']}
      : props.control
  );

  /**
   * Drops what can't be part of a formatted number as it's typed, as a native
   * number input would: anything but digits, spaces, separators, minus signs
   * and direction marks. The caret stays put.
   */
  function onInput(event: Event): void {
    const input = event.target;
    if (!locale.value || !(input instanceof HTMLInputElement)) {
      return;
    }

    const value = input.value;
    const start = input.selectionStart;
    let caret = start;
    let filtered = '';
    let index = 0;

    for (const char of value) {
      if (
        /[\p{Nd}\p{Zs}.,'’\u066b\u066c\u061c\u200e\u200f\u2212-]/u.test(char)
      ) {
        filtered += char;
      } else if (caret !== null && start !== null && index < start) {
        caret -= char.length;
      }

      index += char.length;
    }

    if (filtered !== value) {
      input.value = filtered;
      if (caret !== null) {
        input.setSelectionRange(caret, caret);
      }
    }
  }

  function onUpdate(value: string, kind?: UiChangeKind): void {
    emit(
      'update:value',
      // An empty number is reported as the server sends it, so populating the
      // field on load doesn't read as an edit.
      locale.value
        ? {value: value === '' ? null : value, locale: locale.value}
        : value,
      kind
    );
  }
</script>

<template>
  <TextControl
    v-bind="$attrs"
    :control="inputControl"
    :value="locale ? text : value"
    :label="label"
    :editable="editable"
    :invalid="invalid"
    :required="required"
    @input="onInput"
    @update:value="onUpdate"
  />
  <input
    v-if="editable && locale"
    type="hidden"
    :name="`${inputName(control.path)}[locale]`"
    :value="locale"
  />
</template>
