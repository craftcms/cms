<script setup lang="ts">
  import {computed} from 'vue';
  import type {FormElementBinding, JsonValue} from '../types';
  import '@craftcms/ui/components/select/select';

  type Option = {
    label: string;
    value: string | number | boolean | null;
    disabled?: boolean;
    hidden?: boolean;
    data?: Record<string, JsonValue>;
  };
  type Optgroup = {
    type: 'optgroup';
    label: string;
    options: Option[];
    disabled?: boolean;
  };
  type Choice = Option | Optgroup;

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: Option['value']];
  }>();

  const options = computed<Choice[]>(() =>
    Array.isArray(props.config.options)
      ? (props.config.options as Choice[])
      : []
  );
  const flatOptions = computed<Option[]>(() =>
    options.value.flatMap((option) =>
      isOptgroup(option) ? option.options : [option]
    )
  );
  const value = computed(() => String(props.binding?.value ?? ''));

  function updateValue(event: Event): void {
    const selectedValue =
      event.target instanceof HTMLSelectElement
        ? event.target.value
        : String(
            (event.currentTarget as HTMLElementTagNameMap['craft-select'])
              .modelValue ?? ''
          );
    const option = flatOptions.value.find(
      ({value}) => String(value ?? '') === selectedValue
    );

    emit('update:value', option?.value ?? null);
  }

  function isOptgroup(option: Choice): option is Optgroup {
    return 'type' in option && option.type === 'optgroup';
  }

  function dataAttributes(option: Option): Record<string, JsonValue> {
    return Object.fromEntries(
      Object.entries(option.data ?? {}).map(([name, value]) => [
        `data-${name}`,
        value,
      ])
    );
  }
</script>

<template>
  <craft-select
    v-bind="attributes"
    :model-value="value"
    :disabled="binding?.readOnly"
    @change="updateValue"
  >
    <select slot="input" :value="value" :disabled="binding?.readOnly">
      <template v-for="option in options" :key="option.label">
        <optgroup
          v-if="isOptgroup(option)"
          :label="option.label"
          :disabled="option.disabled"
        >
          <option
            v-for="child in option.options"
            :key="String(child.value)"
            v-bind="dataAttributes(child)"
            :value="String(child.value ?? '')"
            :disabled="child.disabled"
            :hidden="child.hidden"
          >
            {{ child.label }}
          </option>
        </optgroup>
        <option
          v-else
          v-bind="dataAttributes(option)"
          :value="String(option.value ?? '')"
          :disabled="option.disabled"
          :hidden="option.hidden"
        >
          {{ option.label }}
        </option>
      </template>
    </select>
  </craft-select>
</template>
