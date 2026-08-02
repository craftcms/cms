<script setup lang="ts">
  import {computed, ref, useAttrs, useTemplateRef} from 'vue';
  import {useEventListener} from '@vueuse/core';
  import '@craftcms/ui/components/checkbox/checkbox';
  import '@craftcms/ui/components/icon/icon';

  defineOptions({inheritAttrs: false});

  type OptionValue = string | number | boolean | null;

  interface Option {
    label: string;
    value: OptionValue;
    icon?: string;
    color?: string;
    disabled?: boolean;
  }

  type Value = OptionValue | OptionValue[];

  const props = withDefaults(
    defineProps<{
      id?: string;
      name?: string;
      modelValue?: Value;
      options?: Option[];
      allOption?: OptionValue;
      readonly?: boolean;
      sortable?: boolean;
    }>(),
    {
      name: '',
      options: () => [],
      readonly: false,
      sortable: false,
    }
  );

  const emit = defineEmits<{
    'update:modelValue': [value: Value];
  }>();

  // TODO: Delete this Form-specific value adapter when Checkbox Select is ported
  // to expose the standard modelValue interface.

  const attrs = useAttrs();
  const container = useTemplateRef<HTMLElement>('container');
  const fieldset = ref<HTMLFieldSetElement>();
  const hasAllOption = computed(() => props.allOption !== undefined);
  const allValue = computed(() =>
    hasAllOption.value ? htmlValue(props.allOption as OptionValue) : undefined
  );
  const selectedValues = computed(() => {
    if (props.modelValue === undefined) {
      return hasAllOption.value ? new Set([allValue.value]) : new Set<string>();
    }

    const values = Array.isArray(props.modelValue)
      ? props.modelValue
      : [props.modelValue];

    return new Set(values.map(htmlValue));
  });
  const allSelected = computed(
    () =>
      allValue.value !== undefined && selectedValues.value.has(allValue.value)
  );
  const containerTag = computed(() =>
    props.sortable && !props.readonly ? 'craft-sortable-checkbox-select' : 'div'
  );
  const arrayName = computed(() =>
    props.name.endsWith('[]') ? props.name : `${props.name}[]`
  );
  const orderedOptions = computed(() => {
    const options = props.options.map((option, index) => ({option, index}));

    if (!props.sortable || !Array.isArray(props.modelValue)) {
      return options;
    }

    const selectedOrder = new Map(
      props.modelValue.map((value, index) => [htmlValue(value), index])
    );

    return options.sort((first, second) => {
      const firstValue = htmlValue(first.option.value);
      const secondValue = htmlValue(second.option.value);

      if (firstValue === allValue.value) {
        return -1;
      }

      if (secondValue === allValue.value) {
        return 1;
      }

      const firstIndex = selectedOrder.get(firstValue);
      const secondIndex = selectedOrder.get(secondValue);

      if (firstIndex === undefined) {
        return secondIndex === undefined ? 0 : 1;
      }

      return secondIndex === undefined ? -1 : firstIndex - secondIndex;
    });
  });

  function htmlValue(value: OptionValue): string {
    if (value === true) {
      return '1';
    }

    return value === false || value === null ? '' : String(value);
  }

  function isAllOption(option: Option): boolean {
    return (
      allValue.value !== undefined && htmlValue(option.value) === allValue.value
    );
  }

  function optionId(option: Option, index: number): string | undefined {
    if (!props.id) {
      return undefined;
    }

    return `${props.id}-${isAllOption(option) ? 'all' : index}`;
  }

  function checkedValues(): string[] {
    if (!fieldset.value) {
      return [];
    }

    return Array.from(
      fieldset.value.querySelectorAll<HTMLInputElement>(
        '.cp-checkbox-select__item:not(.all) input[type="checkbox"]:checked'
      )
    ).map((input) => input.value);
  }

  function onChange(event: Event): void {
    const input = event.target;

    if (
      props.readonly ||
      !(input instanceof HTMLInputElement) ||
      input.type !== 'checkbox'
    ) {
      return;
    }

    if (allValue.value !== undefined && input.value === allValue.value) {
      emit('update:modelValue', input.checked ? input.value : []);

      return;
    }

    emit('update:modelValue', checkedValues());
  }

  function onSortChange(): void {
    if (!props.readonly) {
      emit('update:modelValue', checkedValues());
    }
  }

  useEventListener(container, 'sortChange', onSortChange);
</script>

<template>
  <component
    :is="containerTag"
    ref="container"
    v-bind="attrs"
    :id="id"
    role="group"
  >
    <fieldset
      ref="fieldset"
      class="cp-checkbox-select"
      :disabled="readonly"
      @change="onChange"
    >
      <input
        v-if="!hasAllOption && name && !name.endsWith('[]')"
        type="hidden"
        :name="name"
        value=""
      />
      <div
        v-for="{option, index} in orderedOptions"
        :key="`${htmlValue(option.value)}:${index}`"
        class="cp-checkbox-select__item"
        :class="{all: isAllOption(option)}"
      >
        <craft-checkbox>
          <input
            slot="input"
            type="checkbox"
            class="checkbox"
            :id="optionId(option, index)"
            :name="isAllOption(option) ? name : arrayName"
            :value="htmlValue(option.value)"
            :checked="
              allSelected || selectedValues.has(htmlValue(option.value))
            "
            :disabled="
              readonly ||
              option.disabled ||
              (allSelected && !isAllOption(option))
            "
            :data-option-disabled="option.disabled ? 'true' : 'false'"
          />
          <label slot="label" :for="optionId(option, index)">
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
    </fieldset>
  </component>
</template>
