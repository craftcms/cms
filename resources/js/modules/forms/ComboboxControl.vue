<script setup lang="ts">
  import type {ComboboxItem} from '@craftcms/ui/components/combobox/combobox';
  import CraftCombobox from '@craftcms/ui/vue/CraftCombobox.vue';
  import type {FormChangeKind, FormControlPayload} from './types';
  import {inputName, serverErrorValidators} from './runtime';

  type ComboboxControlProps = {
    options: ComboboxItem[];
    placeholder?: string;
    limit?: number;
    clearable?: boolean;
    requireOptionMatch?: boolean;
    showAllOnEmpty?: boolean;
    showSelectedHint?: boolean;
    dir?: string;
  };

  const props = defineProps<{
    control: FormControlPayload<ComboboxControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: string, kind: FormChangeKind): void;
  }>();

  function namesAnOption(value: string): boolean {
    return props.control.props.options.some((item) =>
      'options' in item
        ? item.options.some((option) => String(option.value) === value)
        : String(item.value) === value
    );
  }

  function onValueChanged(value: string | number | boolean | undefined): void {
    const next = String(value ?? '');

    // The combobox reports each keystroke as well as the eventual selection.
    // A value naming an option is a committed choice, so it shouldn't sit out
    // the long typing debounce before the form refreshes; free text on its way
    // to a match still should.
    emit('update:value', next, namesAnOption(next) ? 'discrete' : 'typing');
  }
</script>

<template>
  <CraftCombobox
    :name="editable ? inputName(control.path) : ''"
    :model-value="String(value ?? '')"
    :options="control.props.options"
    :placeholder="control.props.placeholder"
    :limit="control.props.limit"
    :clearable="control.props.clearable"
    :require-option-match="control.props.requireOptionMatch"
    :show-all-on-empty="control.props.showAllOnEmpty"
    :show-selected-hint="control.props.showSelectedHint"
    :dir="control.props.dir"
    :required="editable && required"
    :readonly="control.mode === 'readOnly'"
    :disabled="control.mode === 'disabled'"
    :validators="serverErrorValidators(invalid)"
    @update:model-value="onValueChanged"
  />
</template>
