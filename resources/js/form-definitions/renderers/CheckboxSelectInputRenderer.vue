<script setup lang="ts">
  import {computed, ref, useAttrs, watch} from 'vue';
  import type {ReorderDirection} from '@craftcms/ui';
  import type {JsonValue} from '../types';
  import '@craftcms/ui/components/checkbox/checkbox';
  import '@craftcms/ui/components/checkbox-group/checkbox-group';
  import '@craftcms/ui/components/icon/icon';
  import '@craftcms/ui/components/reorder-button/reorder-button';

  type OptionValue = string | number | boolean | null;
  type Option = {
    label: string;
    value: OptionValue;
    icon?: string;
    color?: string;
    disabled?: boolean;
  };

  defineOptions({inheritAttrs: false});

  const props = defineProps<{
    options?: Option[];
    sortable?: boolean;
    allOption?: OptionValue;
    modelValue?: unknown;
    readonly?: boolean;
  }>();

  const emit = defineEmits<{
    'update:modelValue': [value: OptionValue | OptionValue[]];
  }>();

  const attributes = useAttrs();
  const orderedOptions = ref<Option[]>([]);
  const noEmittedValue = Symbol();
  let emittedValue: unknown = noEmittedValue;
  const sortable = computed(() => props.sortable === true);
  const allOption = computed<OptionValue | undefined>(() => {
    const value = props.allOption;

    return isOptionValue(value) ? value : undefined;
  });

  watch(
    [() => props.options, sortable, allOption],
    ([options]) => {
      orderedOptions.value = orderOptions(options ?? []);
    },
    {immediate: true}
  );
  watch(
    () => props.modelValue,
    (value) => {
      if (sameValue(value, emittedValue)) {
        emittedValue = noEmittedValue;

        return;
      }

      emittedValue = noEmittedValue;
      orderedOptions.value = orderOptions(props.options ?? []);
    }
  );
  const selectedValues = computed<OptionValue[]>(() => {
    const value = props.modelValue;

    if (value == null && allOption.value !== undefined) {
      return [allOption.value];
    }

    return Array.isArray(value)
      ? (value as OptionValue[])
      : [value as OptionValue];
  });
  const selectedOptions = computed(() =>
    orderedOptions.value.filter(
      (option) => option.value !== allOption.value && checked(option.value)
    )
  );
  const allSelected = computed(
    () =>
      allOption.value !== undefined &&
      selectedValues.value.some((value) => value === allOption.value)
  );

  function checked(value: OptionValue): boolean {
    return (
      allSelected.value ||
      selectedValues.value.some((selected) => selected === value)
    );
  }

  function disabled(option: Option): boolean {
    return Boolean(
      props.readonly ||
      option.disabled ||
      (allSelected.value && option.value !== allOption.value)
    );
  }

  function updateValue(option: Option, event: Event): void {
    if (disabled(option)) {
      return;
    }

    const input = event.target as HTMLInputElement;

    if (option.value === allOption.value) {
      updateModelValue(input.checked ? option.value : []);

      return;
    }

    const values = new Set(
      selectedValues.value.filter((value) => value !== allOption.value)
    );

    if (input.checked) {
      values.add(option.value);
    } else {
      values.delete(option.value);
    }

    updateModelValue(
      orderedOptions.value
        .filter(({value}) => value !== allOption.value && values.has(value))
        .map(({value}) => value)
    );
  }

  function reorder(
    option: Option,
    event: CustomEvent<{direction: ReorderDirection}>
  ) {
    if (disabled(option) || !checked(option.value)) {
      return;
    }

    const selectedIndex = selectedOptions.value.indexOf(option);
    const target =
      event.detail.direction === 'down'
        ? selectedOptions.value[selectedIndex + 1]
        : selectedOptions.value[selectedIndex - 1];

    if (!target) {
      return;
    }

    const reordered = orderedOptions.value.slice();
    reordered.splice(reordered.indexOf(option), 1);
    const targetIndex = reordered.indexOf(target);
    reordered.splice(
      event.detail.direction === 'down' ? targetIndex + 1 : targetIndex,
      0,
      option
    );
    orderedOptions.value = reordered;
    updateModelValue(
      reordered
        .filter(
          (candidate) =>
            candidate.value !== allOption.value && checked(candidate.value)
        )
        .map(({value}) => value)
    );
  }

  function reorderPosition(option: Option): 'first' | 'middle' | 'last' {
    const index = selectedOptions.value.indexOf(option);

    if (index === 0) {
      return 'first';
    }

    return index === selectedOptions.value.length - 1 ? 'last' : 'middle';
  }

  function inputAttributes(option: Option, index: number) {
    const inputAttributes = {...attributes};
    const id = typeof inputAttributes.id === 'string' ? inputAttributes.id : '';

    delete inputAttributes.id;
    delete inputAttributes.name;

    return {
      ...inputAttributes,
      id: index === 0 ? id : `${id}-${index}`,
      name: groupName(),
      value: String(option.value ?? ''),
      disabled: disabled(option),
    };
  }

  function groupName(): string {
    return `${String(attributes.name)}[]`;
  }

  function updateModelValue(value: OptionValue | OptionValue[]): void {
    emittedValue = value;
    emit('update:modelValue', value);
  }

  function sameValue(first: unknown, second: unknown): boolean {
    if (!Array.isArray(first) || !Array.isArray(second)) {
      return first === second;
    }

    return (
      first.length === second.length &&
      first.every((value, index) => value === second[index])
    );
  }

  function orderOptions(options: Option[]): Option[] {
    if (!sortable.value || !Array.isArray(props.modelValue)) {
      return options;
    }

    const selectedOrder = new Map(
      props.modelValue.map((value, index) => [value, index])
    );

    return options.slice().sort((first, second) => {
      if (first.value === allOption.value) {
        return -1;
      }

      if (second.value === allOption.value) {
        return 1;
      }

      const firstIndex = selectedOrder.get(first.value);
      const secondIndex = selectedOrder.get(second.value);

      if (firstIndex === undefined) {
        return secondIndex === undefined ? 0 : 1;
      }

      return secondIndex === undefined ? -1 : firstIndex - secondIndex;
    });
  }

  function isOptionValue(value: unknown): value is OptionValue {
    return (
      value === null ||
      typeof value === 'string' ||
      typeof value === 'number' ||
      typeof value === 'boolean'
    );
  }
</script>

<template>
  <craft-checkbox-group v-bind="attributes" :name="groupName()">
    <div
      v-for="(option, index) in orderedOptions"
      :key="String(option.value)"
      class="checkbox-select-option"
      :class="{'checkbox-select-option--sortable': sortable}"
    >
      <craft-reorder-button
        v-if="sortable && option.value !== allOption"
        :disabled="
          disabled(option) ||
          !checked(option.value) ||
          selectedOptions.length < 2
        "
        :position="reorderPosition(option)"
        @reorder="reorder(option, $event)"
      />
      <craft-checkbox
        :checked="checked(option.value)"
        :disabled="disabled(option)"
      >
        <input
          v-bind="inputAttributes(option, index)"
          slot="input"
          type="checkbox"
          :checked="checked(option.value)"
          @change="updateValue(option, $event)"
        />
        <label slot="label" :for="String(inputAttributes(option, index).id)">
          <craft-icon
            v-if="option.icon"
            :name="option.icon"
            :style="option.color ? {color: option.color} : undefined"
          ></craft-icon>
          <span v-else-if="option.color" class="color small">
            <span
              class="color-preview"
              :style="{backgroundColor: option.color}"
            ></span>
          </span>
          {{ option.label }}
        </label>
      </craft-checkbox>
    </div>
  </craft-checkbox-group>
</template>

<style scoped>
  .checkbox-select-option--sortable {
    display: grid;
    grid-template-columns: auto 1fr;
    align-items: center;
    gap: var(--c-spacing-sm);
  }
</style>
