<script setup lang="ts">
  import {computed} from 'vue';
  import type {FormElementBinding, JsonValue} from '../types';
  import '@craftcms/ui/components/select/select';

  type Option = {
    label: string;
    value: string | number | boolean | null;
  };

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: Option['value']];
  }>();

  const options = computed<Option[]>(() =>
    Array.isArray(props.config.options)
      ? (props.config.options as Option[])
      : []
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
    const option = options.value.find(
      ({value}) => String(value ?? '') === selectedValue
    );

    emit('update:value', option?.value ?? null);
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
      <option
        v-for="option in options"
        :key="String(option.value)"
        :value="String(option.value ?? '')"
      >
        {{ option.label }}
      </option>
    </select>
  </craft-select>
</template>
