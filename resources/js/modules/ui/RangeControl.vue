<script setup lang="ts">
  import CraftInput from '@craftcms/ui/components/input/input';
  import {t} from '@craftcms/ui/utilities/translate';
  import {ref, useId, useTemplateRef} from 'vue';
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
  const emit = defineEmits<{
    (event: 'update:value', value: string, kind?: UiChangeKind): void;
  }>();

  const range = useTemplateRef<HTMLInputElement>('range');
  const descriptionId = useId();

  /**
   * What's typed in the number input while it has focus. The value it sets
   * is the slider's, clamped and snapped to a step, which would overwrite
   * the typing mid-number, so the number input shows that value on blur.
   */
  const typed = ref<string | null>(null);

  function onRangeInput(): void {
    emit('update:value', range.value!.value, 'discrete');
  }

  function onNumberInput(event: Event): void {
    const target = event.currentTarget;
    if (!(target instanceof CraftInput)) {
      throw new TypeError('Expected a craft input event target.');
    }

    typed.value = target.value;
    range.value!.value = target.value;
    emit('update:value', range.value!.value, 'typing');
  }
</script>

<template>
  <div class="range-group">
    <div class="range-wrapper">
      <input
        ref="range"
        type="range"
        class="range"
        :name="editable ? inputName(control.path) : undefined"
        :min="control.props.min"
        :max="control.props.max"
        :step="control.props.step"
        .value="String(value ?? '')"
        :dir="control.props.dir"
        :aria-label="label"
        :aria-invalid="invalid ? 'true' : undefined"
        :disabled="control.mode !== 'editable'"
        @input="onRangeInput"
      />
    </div>
    <craft-input
      type="number"
      width="auto"
      :label="label"
      label-sr-only
      .value="typed ?? String(value ?? '')"
      :min="control.props.min"
      :max="control.props.max"
      :step="control.props.step"
      .inputSize="control.props.size"
      .inputMode="'decimal'"
      :readonly="control.mode === 'readOnly'"
      .disabled="control.mode === 'disabled'"
      :invalid="invalid"
      @input="onNumberInput"
      @focusout="typed = null"
    >
      <input
        slot="input"
        :aria-describedby="control.props.suffix ? descriptionId : undefined"
      />
    </craft-input>
    <template v-if="control.props.suffix">
      <span :id="descriptionId" hidden>{{
        t('Value suffixed by “{suffix}”.', {suffix: control.props.suffix})
      }}</span>
      <span class="range-suffix" aria-hidden="true">{{
        control.props.suffix
      }}</span>
    </template>
  </div>
</template>
