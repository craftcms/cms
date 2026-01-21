<script setup lang="ts">
  import {computed, type MaybeRef, ref, unref} from 'vue';
  import {
    Combobox,
    ComboboxButton,
    ComboboxInput,
    ComboboxOptions,
    TransitionRoot,
  } from '@headlessui/vue';
  import type {SelectItem, SelectOption} from '@/types';
  import InputComboboxOption from '@/components/InputComboboxOption.vue';

  const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
  }>();
  const props = withDefaults(
    defineProps<{
      options?: Array<SelectItem>;
      modelValue?: string;
      requireOptionMatch?: boolean;
      transformModelValue?: (newValue: SelectOption | null) => string;
    }>(),
    {
      modelValue: '',
      requireOptionMatch: false,
      options: () => [],
      transformModelValue: (newValue: SelectOption | undefined | null) =>
        newValue ? newValue.value : '',
    }
  );

  const selectedOption = computed({
    get() {
      let selectedItem = null;

      if (props.modelValue && props.modelValue !== '') {
        props.options.forEach((item) => {
          if (item.type === 'optgroup') {
            item.options.forEach((option) => {
              if (option.value === props.modelValue) {
                selectedItem = option;
              }
            });
          } else {
            if (item.value === props.modelValue) {
              selectedItem = item;
            }
          }
        });

        if (!selectedItem && !props.requireOptionMatch) {
          selectedItem = {
            label: props.modelValue,
            value: props.modelValue,
          };
        }
      }

      return selectedItem;
    },
    set(newValue) {
      emit(
        'update:modelValue',
        props.transformModelValue(newValue as SelectOption)
      );
    },
  });

  const query = ref(props.modelValue ?? '');

  function matchesQuery(query: MaybeRef<string>, item: MaybeRef<SelectOption>) {
    const lowerQuery = unref(query).toLowerCase();
    const option = unref(item);

    return (
      option.label.toLowerCase().includes(lowerQuery) ||
      option.value.toLowerCase().includes(lowerQuery) ||
      (option.data?.keywords?.toLowerCase().includes(lowerQuery) ?? false)
    );
  }

  function getMatches(
    query: MaybeRef<string>,
    options: MaybeRef<Array<SelectItem>>
  ) {
    return unref(options)
      .map((item) => {
        if (item.type === 'optgroup') {
          const filteredOptions = item.options.filter((option) =>
            matchesQuery(query, option)
          );

          if (filteredOptions.length > 0) {
            return {...item, options: filteredOptions};
          }
          return null;
        }

        // Standalone option
        return matchesQuery(query, item) ? item : null;
      })
      .filter((item) => item !== null);
  }

  const filteredOptions = computed(() => {
    return query.value !== ''
      ? getMatches(query, props.options)
      : props.options;
  });

  function displayValue(data: unknown) {
    if (data) {
      return (data as SelectOption).label;
    }

    return '';
  }

  const customValue = computed(() => {
    return ['', '@', '$'].includes(query.value)
      ? null
      : {value: query.value, label: query.value};
  });
</script>

<template>
  <div class="relative">
    <Combobox v-model="selectedOption">
      <ComboboxInput
        @change="query = $event.target.value"
        class="input"
        :displayValue="displayValue"
      />
      <ComboboxButton
        class="absolute inset-y-1 right-1 flex items-center"
        type="button"
        as="craft-button"
        appearance="plain"
        size="small"
        icon
      >
        <craft-icon name="chevron-down" style="font-size: 0.8em"></craft-icon>
      </ComboboxButton>
      <TransitionRoot
        leave="transition ease-in duration-100"
        leaveFrom="opacity-100"
        leaveTo="opacity-0"
        @after-leave="query = ''"
      >
        <ComboboxOptions class="options">
          <InputComboboxOption
            v-if="!requireOptionMatch && customValue"
            :option="customValue"
          />
          <div v-else-if="filteredOptions.length === 0 && query !== ''">
            Nothing found.
          </div>

          <template v-for="(item, idx) in filteredOptions" :key="idx">
            <template v-if="item.type === 'optgroup'">
              <div class="group-label">{{ item.label }}</div>
              <template v-for="(option, idx) in item.options" :key="idx">
                <InputComboboxOption :option="option" />
              </template>
            </template>
            <template v-else>
              <InputComboboxOption :option="item" />
            </template>
          </template>
        </ComboboxOptions>
      </TransitionRoot>
    </Combobox>
  </div>
</template>

<style scoped lang="scss">
  .group-label {
    padding: var(--c-spacing-sm);
    font-size: 0.8em;
    text-transform: uppercase;
    padding-inline: var(--c-spacing-md);
    padding-block: var(--c-spacing-sm);
    color: var(--c-color-neutral-on-normal);
    border-bottom: 1px solid var(--c-border-faint);
  }

  .input {
    width: 100%;
    border: 0;
    border-radius: var(--c-input-radius, var(--c-radius-sm));
    min-height: var(--c-input-height, var(--c-size-control-md));
    appearance: none;
    padding-block: 0;
    padding-inline: 0;

    &:focus {
      outline: none;
    }
  }

  .options {
    padding: var(--c-spacing-sm);
    position: absolute;
    inset-inline-start: 0;
    inset-inline-end: 0;
    margin-block-start: var(--c-spacing-1px);
    max-height: calc(var(--c-spacing) * 60);
    overflow: auto;
    border-radius: var(--c-radius-md);
    box-shadow: var(--c-shadow-sm);
    background-color: var(--c-bg-overlay);
    border: 1px solid var(--c-color-neutral-border-subtle);
    width: 100%;
    max-width: 100%;
    z-index: 40;
    display: grid;
    gap: var(--c-spacing-1px);

    &:nth-child(even) {
      background-color: var(--c-color-neutral-bg-subtle);
    }
  }
</style>
