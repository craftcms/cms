<script setup lang="ts">
  import {computed} from 'vue';
  import type {FormElementBinding, JsonValue} from '../types';
  import '@craftcms/ui/components/checkbox/checkbox';
  import '@craftcms/ui/components/checkbox-group/checkbox-group';

  type OptionValue = string | number | boolean | null;
  type Option = {label: string; value: OptionValue};

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: OptionValue | OptionValue[]];
  }>();

  const options = computed<Option[]>(() =>
    Array.isArray(props.config.options)
      ? (props.config.options as Option[])
      : []
  );
  const groupName = computed(() => `${String(props.attributes.name)}[]`);
  const allOption = computed<OptionValue | undefined>(() => {
    const value = props.config.allOption;

    return isOptionValue(value) ? value : undefined;
  });
  const selectedValues = computed<OptionValue[]>(() => {
    const value = props.binding?.value;

    return Array.isArray(value)
      ? (value as OptionValue[])
      : [value as OptionValue];
  });

  function checked(value: OptionValue): boolean {
    return selectedValues.value.some((selected) => selected === value);
  }

  function updateValue(option: Option, event: Event): void {
    const input = event.target as HTMLInputElement;

    if (option.value === allOption.value) {
      emit('update:value', input.checked ? option.value : []);

      return;
    }

    const values = selectedValues.value.filter(
      (value) => value !== allOption.value && value !== option.value
    );

    if (input.checked) {
      values.push(option.value);
    }

    emit('update:value', values);
  }

  function inputAttributes(option: Option, index: number) {
    const attributes = {...props.attributes};
    const id = typeof attributes.id === 'string' ? attributes.id : '';

    delete attributes.id;
    delete attributes.name;
    delete attributes.readonly;

    return {
      ...attributes,
      id: index === 0 ? id : `${id}-${index}`,
      name: groupName.value,
      value: String(option.value ?? ''),
      disabled: props.binding?.readOnly,
    };
  }

  function isOptionValue(value: JsonValue | undefined): value is OptionValue {
    return (
      value === null ||
      typeof value === 'string' ||
      typeof value === 'number' ||
      typeof value === 'boolean'
    );
  }
</script>

<template>
  <craft-checkbox-group :name="groupName">
    <craft-checkbox
      v-for="(option, index) in options"
      :key="String(option.value)"
      :checked="checked(option.value)"
      :disabled="binding?.readOnly"
    >
      <input
        v-bind="inputAttributes(option, index)"
        slot="input"
        type="checkbox"
        :checked="checked(option.value)"
        @change="updateValue(option, $event)"
      />
      <label slot="label" :for="String(inputAttributes(option, index).id)">
        {{ option.label }}
      </label>
    </craft-checkbox>
  </craft-checkbox-group>
</template>
