<script setup lang="ts">
  import CraftTextarea from '@craftcms/ui/components/textarea/textarea';
  import type {TextExpanderTriggers} from '@craftcms/ui/components/text-expander/text-expander';
  import '@craftcms/ui/components/text-expander/text-expander';
  import {useId} from 'vue';
  import type {UiControlPayload} from './types';
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
    singleLine?: boolean;
    textExpanderTriggers?: TextExpanderTriggers;
  };

  const props = defineProps<{
    control: UiControlPayload<TextareaControlProps>;
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

  /** Single-line values wrap, but can't contain line breaks. */
  function onBeforeInput(event: InputEvent): void {
    if (
      props.control.props.singleLine &&
      ['insertLineBreak', 'insertParagraph'].includes(event.inputType)
    ) {
      event.preventDefault();
    }
  }

  function onInput(event: Event): void {
    const textarea = event.target as HTMLTextAreaElement;
    if (!props.control.props.singleLine || !/[\r\n]/.test(textarea.value)) {
      return;
    }

    const caret = textarea.selectionStart;
    textarea.value = textarea.value.replace(/\r\n?|\n/g, ' ');
    textarea.setSelectionRange(caret, caret);
  }

  const onModelValueChanged = ignoreModelValueInitialization((event) => {
    if (!(event.target instanceof CraftTextarea)) {
      throw new TypeError('Expected a textarea event target.');
    }

    emit('update:value', String(event.target.modelValue ?? ''), 'typing');
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
      @beforeinput="onBeforeInput"
      @input="onInput"
    ></textarea>
  </craft-textarea>
  <craft-text-expander
    v-if="editable && control.props.textExpanderTriggers"
    slot="input"
    :for="inputId"
    .triggers="control.props.textExpanderTriggers"
  />
</template>
