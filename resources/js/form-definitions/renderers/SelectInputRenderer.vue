<script setup lang="ts">
  import {computed} from 'vue';
  import type {JsonValue} from '../types';
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
    options?: Choice[];
    modelValue?: unknown;
    readonly?: boolean;
  }>();

  const emit = defineEmits<{
    'update:modelValue': [value: Option['value']];
  }>();

  const flatOptions = computed<Option[]>(() =>
    (props.options ?? []).flatMap((option) =>
      isOptgroup(option) ? option.options : [option]
    )
  );
  const value = computed(() => String(props.modelValue ?? ''));

  function updateValue(event: Event): void {
    if (props.readonly) {
      return;
    }

    const select =
      event.target instanceof HTMLSelectElement
        ? event.target
        : (event.currentTarget as HTMLElement).querySelector('select');
    const option = select ? flatOptions.value[select.selectedIndex] : undefined;

    emit('update:modelValue', option?.value ?? null);
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
  <craft-select :model-value="value" :disabled="readonly" @change="updateValue">
    <select slot="input" :value="value" :disabled="readonly">
      <template v-for="option in options ?? []" :key="option.label">
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
