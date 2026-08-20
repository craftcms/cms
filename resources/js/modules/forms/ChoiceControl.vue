<script setup lang="ts">
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/button-group/button-group';
  import '@craftcms/ui/components/checkbox/checkbox';
  import '@craftcms/ui/components/checkbox-group/checkbox-group';
  import '@craftcms/ui/components/radio/radio';
  import '@craftcms/ui/components/radio-group/radio-group';
  import '@craftcms/ui/components/select/select';
  import {computed, ref, watch} from 'vue';
  import {t} from '@craftcms/ui';
  import type {CheckboxOption} from '@/common/types';
  import CheckboxGroup from '@/common/form/CheckboxGroup.vue';
  import type {FormControlPayload} from './types';
  import {inputName, serverErrorValidators} from './runtime';

  // Mirrors Choice::ALL_VALUE.
  const ALL_VALUE = '*';

  type ChoiceValue = boolean | number | string;
  type ChoicePresentation = CraftCms.Cms.Form.Enums.ChoicePresentation;
  type ChoiceOption = {
    label: string;
    labelHtml?: string;
    value: ChoiceValue;
    disabled?: boolean;
  };
  type ChoiceControlProps = {
    options: ChoiceOption[];
    multiple: boolean;
    presentation: ChoicePresentation;
    sortable?: boolean;
    allowAll?: boolean;
  };

  const props = defineProps<{
    control: FormControlPayload<ChoiceControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: string | string[]): void;
  }>();

  function inputValue(value: unknown): string {
    return value === true ? '1' : value === false ? '' : String(value);
  }

  function selected(value: ChoiceValue): boolean {
    const values = props.control.props.multiple
      ? (props.value as unknown[])
      : [props.value];

    return (values ?? []).map(inputValue).includes(inputValue(value));
  }

  function onSelect(event: Event): void {
    const select = event.target as HTMLSelectElement;
    emit(
      'update:value',
      props.control.props.multiple
        ? Array.from(select.selectedOptions, (option) => option.value)
        : select.value
    );
  }

  function onOptionChanged(event: Event): void {
    const input = event.target as HTMLInputElement;

    if (!props.control.props.multiple) {
      emit('update:value', input.value);
      return;
    }

    const values = Array.isArray(props.value)
      ? props.value.map(inputValue)
      : [];
    emit(
      'update:value',
      input.checked
        ? [...values, input.value]
        : values.filter((value) => value !== input.value)
    );
  }

  function onButtonClicked(value: ChoiceValue): void {
    const optionValue = inputValue(value);

    if (!props.control.props.multiple) {
      emit('update:value', optionValue);
      return;
    }

    const values = Array.isArray(props.value)
      ? props.value.map(inputValue)
      : [];
    emit(
      'update:value',
      values.includes(optionValue)
        ? values.filter((value) => value !== optionValue)
        : [...values, optionValue]
    );
  }

  function optionId(index: number): string {
    return `form-${props.control.path.join('-')}-${index}`;
  }

  // Sortable and All both need affordances `craft-checkbox-group` doesn't have
  // on its own, so they render through the CP's CheckboxGroup instead.
  const useCheckboxGroup = computed(
    () =>
      props.control.props.presentation === 'checkboxes' &&
      (props.control.props.sortable === true ||
        props.control.props.allowAll === true)
  );

  const allSelected = computed(
    () => props.control.props.allowAll === true && props.value === ALL_VALUE
  );

  // Display order is owned by the client: the server sends the selected options
  // first, and a drag only reorders — it never changes what is checked.
  const order = ref<string[]>(optionValues(props.control.props.options));

  watch(
    () => props.control.props.options,
    (options) => {
      const values = optionValues(options);
      order.value = [
        ...order.value.filter((value) => values.includes(value)),
        ...values.filter((value) => !order.value.includes(value)),
      ];
    }
  );

  function optionValues(options: ChoiceOption[]): string[] {
    return options.map((option) => inputValue(option.value));
  }

  function optionHtml(value: string): string | undefined {
    return props.control.props.options.find(
      (option) => inputValue(option.value) === value
    )?.labelHtml;
  }

  const groupOptions = computed<CheckboxOption[]>(() => {
    const byValue = new Map(
      props.control.props.options.map((option) => [
        inputValue(option.value),
        option,
      ])
    );

    const options: CheckboxOption[] = order.value.flatMap((value) => {
      const option = byValue.get(value);

      return option
        ? [
            {
              label: option.label,
              value,
              // A checked All disables every other option, per
              // Garnish.CheckboxSelect.
              disabled: !props.editable || option.disabled || allSelected.value,
            },
          ]
        : [];
    });

    if (props.control.props.allowAll) {
      options.unshift({
        label: t('All'),
        value: ALL_VALUE,
        disabled: !props.editable,
      });
    }

    return options;
  });

  const groupValue = computed<string[]>(() =>
    allSelected.value
      ? [ALL_VALUE, ...order.value]
      : Array.isArray(props.value)
        ? props.value.map(inputValue)
        : []
  );

  function onGroupValue(values: string[]): void {
    if (props.control.props.allowAll) {
      const hasAll = values.includes(ALL_VALUE);

      // Resolve from the transition rather than the raw set: while All is
      // checked every other option reports checked too.
      if (hasAll && !allSelected.value) {
        emit('update:value', ALL_VALUE);
        return;
      }

      // Legacy clears the selection when All is unchecked rather than
      // restoring the previous one.
      if (!hasAll && allSelected.value) {
        emit('update:value', []);
        return;
      }
    }

    emit(
      'update:value',
      order.value.filter((value) => values.includes(value))
    );
  }

  function onGroupReorder(options: CheckboxOption[]): void {
    order.value = options
      .map((option) => option.value)
      .filter((value) => value !== ALL_VALUE);

    if (allSelected.value) {
      return;
    }

    // The value carries the display order, so a reorder changes it too.
    const selected = Array.isArray(props.value)
      ? props.value.map(inputValue)
      : [];
    emit(
      'update:value',
      order.value.filter((value) => selected.includes(value))
    );
  }
</script>

<template>
  <craft-select
    v-if="control.props.presentation === 'select'"
    :name="
      editable
        ? `${inputName(control.path)}${control.props.multiple ? '[]' : ''}`
        : ''
    "
    :disabled="!editable"
    :required="editable && required"
    .validators="serverErrorValidators(invalid)"
  >
    <select
      slot="input"
      :name="
        editable
          ? `${inputName(control.path)}${control.props.multiple ? '[]' : ''}`
          : ''
      "
      :multiple="control.props.multiple"
      :disabled="!editable"
      :required="editable && required"
      :aria-invalid="invalid ? 'true' : undefined"
      @change="onSelect"
    >
      <option
        v-for="option in control.props.options"
        :key="inputValue(option.value)"
        :value="inputValue(option.value)"
        :selected="selected(option.value)"
        :disabled="option.disabled"
      >
        {{ option.label }}
      </option>
    </select>
  </craft-select>
  <craft-button-group
    v-else-if="control.props.presentation === 'buttons'"
    :role="control.props.multiple ? 'group' : 'radiogroup'"
    :aria-invalid="invalid ? 'true' : undefined"
    :aria-required="required ? 'true' : undefined"
  >
    <input
      v-if="editable"
      type="hidden"
      :name="inputName(control.path)"
      :value="control.props.multiple ? '' : inputValue(value)"
    />
    <input
      v-for="option in control.props.multiple
        ? control.props.options.filter((option) => selected(option.value))
        : []"
      :key="inputValue(option.value)"
      type="hidden"
      :name="`${inputName(control.path)}[]`"
      :value="inputValue(option.value)"
    />
    <craft-button
      v-for="option in control.props.options"
      :key="inputValue(option.value)"
      type="button"
      :value="inputValue(option.value)"
      :active="selected(option.value)"
      :disabled="!editable || option.disabled"
      :aria-label="option.labelHtml ? option.label : undefined"
      @click="onButtonClicked(option.value)"
    >
      <span v-if="option.labelHtml" v-html="option.labelHtml" />
      <template v-else>{{ option.label }}</template>
    </craft-button>
  </craft-button-group>
  <CheckboxGroup
    v-else-if="useCheckboxGroup"
    :name="editable ? `${inputName(control.path)}[]` : undefined"
    :disabled="!editable"
    :model-value="groupValue"
    :options="groupOptions"
    :sortable="control.props.sortable"
    @update:model-value="onGroupValue"
    @update:options="onGroupReorder"
  >
    <template #label="{option}">
      <b v-if="option.value === ALL_VALUE">{{ option.label }}</b>
      <span
        v-else-if="optionHtml(option.value)"
        v-html="optionHtml(option.value)"
      />
      <template v-else>{{ option.label }}</template>
    </template>
  </CheckboxGroup>
  <component
    :is="control.props.multiple ? 'craft-checkbox-group' : 'craft-radio-group'"
    v-else
    :name="
      editable
        ? `${inputName(control.path)}${control.props.multiple ? '[]' : ''}`
        : ''
    "
    :required="editable && required"
    :disabled="!editable"
    :aria-invalid="invalid ? 'true' : undefined"
    :aria-required="required ? 'true' : undefined"
    .validators="serverErrorValidators(invalid)"
  >
    <input
      v-if="control.props.multiple && editable"
      type="hidden"
      :name="inputName(control.path)"
      value=""
    />
    <component
      :is="control.props.multiple ? 'craft-checkbox' : 'craft-radio'"
      v-for="(option, index) in control.props.options"
      :key="inputValue(option.value)"
      :disabled="!editable || option.disabled"
      .choiceValue="inputValue(option.value)"
      .checked="selected(option.value)"
    >
      <input
        slot="input"
        :id="optionId(index)"
        :type="control.props.multiple ? 'checkbox' : 'radio'"
        :name="
          editable
            ? `${inputName(control.path)}${control.props.multiple ? '[]' : ''}`
            : ''
        "
        :value="inputValue(option.value)"
        :checked="selected(option.value)"
        :disabled="!editable || option.disabled"
        :required="editable && required && !control.props.multiple"
        :aria-invalid="invalid ? 'true' : undefined"
        @change="onOptionChanged"
      />
      <label slot="label" :for="optionId(index)">
        <span v-if="option.labelHtml" v-html="option.labelHtml" />
        <template v-else>{{ option.label }}</template>
      </label>
    </component>
  </component>
</template>
