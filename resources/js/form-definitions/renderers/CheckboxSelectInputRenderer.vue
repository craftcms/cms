<script setup lang="ts">
  import {computed, ref, watch} from 'vue';
  import type {ReorderDirection} from '@craftcms/ui';
  import type {FormElementBinding, JsonValue} from '../types';
  import '@craftcms/ui/components/checkbox/checkbox';
  import '@craftcms/ui/components/checkbox-group/checkbox-group';
  import '@craftcms/ui/components/reorder-button/reorder-button';

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

  const options = ref<Option[]>([]);

  watch(
    () => props.config.options,
    (value) => {
      options.value = Array.isArray(value) ? (value as Option[]) : [];
    },
    {immediate: true}
  );
  const groupName = computed(() => `${String(props.attributes.name)}[]`);
  const sortable = computed(() => props.config.sortable === true);
  const allOption = computed<OptionValue | undefined>(() => {
    const value = props.config.allOption;

    return isOptionValue(value) ? value : undefined;
  });
  const selectedValues = computed<OptionValue[]>(() => {
    const value = props.binding?.value;

    if (value == null && allOption.value !== undefined) {
      return [allOption.value];
    }

    return Array.isArray(value)
      ? (value as OptionValue[])
      : [value as OptionValue];
  });
  const selectedOptions = computed(() =>
    options.value.filter(
      (option) => option.value !== allOption.value && checked(option.value)
    )
  );

  function checked(value: OptionValue): boolean {
    return selectedValues.value.some((selected) => selected === value);
  }

  function updateValue(option: Option, event: Event): void {
    const input = event.target as HTMLInputElement;

    if (option.value === allOption.value) {
      emit('update:value', input.checked ? option.value : []);

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

    emit(
      'update:value',
      options.value
        .filter(({value}) => value !== allOption.value && values.has(value))
        .map(({value}) => value)
    );
  }

  function reorder(
    option: Option,
    event: CustomEvent<{direction: ReorderDirection}>
  ) {
    const selectedIndex = selectedOptions.value.indexOf(option);
    const target =
      event.detail.direction === 'down'
        ? selectedOptions.value[selectedIndex + 1]
        : selectedOptions.value[selectedIndex - 1];

    if (!target) {
      return;
    }

    const reordered = options.value.slice();
    reordered.splice(reordered.indexOf(option), 1);
    const targetIndex = reordered.indexOf(target);
    reordered.splice(
      event.detail.direction === 'down' ? targetIndex + 1 : targetIndex,
      0,
      option
    );
    options.value = reordered;
    emit(
      'update:value',
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
    <div
      v-for="(option, index) in options"
      :key="String(option.value)"
      class="checkbox-select-option"
      :class="{'checkbox-select-option--sortable': sortable}"
    >
      <craft-reorder-button
        v-if="sortable && option.value !== allOption"
        :disabled="
          binding?.readOnly ||
          !checked(option.value) ||
          selectedOptions.length < 2
        "
        :position="reorderPosition(option)"
        @reorder="reorder(option, $event)"
      />
      <craft-checkbox
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
