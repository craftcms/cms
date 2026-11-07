<script setup lang="ts">
  import {isMobileBrowser} from '@craftcms/garnish';
  import CraftInput from '@craftcms/ui/components/input/input';
  import '@craftcms/ui/components/text-expander/text-expander';
  import {computed, onMounted, useId, useTemplateRef} from 'vue';
  import type {UiChangeKind, UiControlPayload, TextControlProps} from './types';
  import {inputName} from './runtime';

  const props = defineProps<{
    control: UiControlPayload<TextControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const inputId = useId();
  const emit = defineEmits<{
    (event: 'update:value', value: string, kind?: UiChangeKind): void;
  }>();

  function autocompleteValue(value: boolean | string | undefined): string {
    if (value !== undefined && value !== true && value !== false) {
      return value;
    }

    return value === true ? 'on' : 'off';
  }

  const input = useTemplateRef<CraftInput>('input');

  // Respect the user's "Disable autofocus" accessibility preference, and don't pop up
  // the on-screen keyboard on mobile and tablet browsers.
  const autofocus = computed(
    () =>
      props.control.props.autofocus === true &&
      window.Craft?.autofocusPreferred === true &&
      !isMobileBrowser(true)
  );

  // The browser only autofocuses on page load, so a control rendered later (such as in a
  // modal or slideout) focuses itself.
  onMounted(() => {
    if (!autofocus.value) {
      return;
    }

    requestAnimationFrame(async () => {
      await input.value?.updateComplete;
      nativeInput()?.focus();
    });
  });

  function nativeInput(): HTMLInputElement | HTMLTextAreaElement | null {
    return (
      input.value?.querySelector<HTMLInputElement | HTMLTextAreaElement>(
        'input, textarea'
      ) ?? null
    );
  }

  // Only the input itself selects, not a suffix button.
  function onFocusIn(event: FocusEvent): void {
    if (
      props.control.props.selectOnFocus === true &&
      event.target === nativeInput()
    ) {
      nativeInput()?.select();
    }
  }

  function onInput(event: Event): void {
    const target = event.currentTarget;
    if (!(target instanceof CraftInput)) {
      throw new TypeError('Expected a craft input event target.');
    }

    emit(
      'update:value',
      target.value,
      ['text', 'email', 'url', 'tel', 'password', 'number'].includes(
        target.type ?? 'text'
      )
        ? 'typing'
        : 'discrete'
    );
  }
</script>

<template>
  <craft-input
    ref="input"
    @focusin="onFocusIn"
    v-bind="$attrs"
    :name="editable ? inputName(control.path) : ''"
    :type="control.props.inputType ?? 'text'"
    .value="String(value ?? '')"
    :min="control.props.min"
    :max="control.props.max"
    :step="control.props.step"
    :maxlength="control.props.maxLength"
    :placeholder="control.props.placeholder"
    .inputMode="control.props.inputMode"
    .autofocus="autofocus"
    .autocomplete="autocompleteValue(control.props.autocomplete)"
    .autoCorrect="control.props.autocorrect ?? true"
    .autoCapitalize="control.props.autocapitalize ?? true"
    .inputSize="control.props.size"
    :dir="control.props.dir"
    .monospace="control.props.monospace === true"
    :required="editable && required"
    :readonly="control.mode === 'readOnly'"
    .disabled="
      control.mode === 'disabled' ||
      (control.mode === 'readOnly' && control.props.inputType === 'range')
    "
    :invalid="invalid"
    @input="onInput"
  >
    <input :id="inputId" slot="input" />
    <slot name="suffix">
      <span v-if="control.props.suffix" slot="suffix">{{
        control.props.suffix
      }}</span>
    </slot>
  </craft-input>
  <craft-text-expander
    v-if="editable && control.props.textExpanderTriggers"
    slot="input"
    :for="inputId"
    .triggers="control.props.textExpanderTriggers"
  />
</template>
