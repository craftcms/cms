<script setup lang="ts">
  import {
    computed,
    type ComponentPublicInstance,
    type HTMLAttributes,
    type MaybeRef,
    ref,
    unref,
    useTemplateRef,
  } from 'vue';
  import {
    Combobox,
    ComboboxButton,
    ComboboxInput,
    ComboboxOptions,
    TransitionRoot,
  } from '@headlessui/vue';
  import type {SelectItem, SelectOption} from '@/common/types';
  import InputComboboxOption from '@/common/form/InputComboboxOption.vue';

  type ClickableRef =
    | HTMLElement
    | (ComponentPublicInstance & {$el?: HTMLElement});

  const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
  }>();
  const props = withDefaults(
    defineProps<{
      label?: string;
      options?: Array<SelectItem>;
      modelValue?: string | number | boolean;
      requireOptionMatch?: boolean;
      transformModelValue?: (newValue: SelectOption | null) => string;
      class?: HTMLAttributes['class'];
      placeholder?: string;
      disabled?: boolean;
      clearable?: boolean;
      showAllOnEmpty?: boolean;
    }>(),
    {
      modelValue: '',
      clearable: false,
      requireOptionMatch: false,
      showAllOnEmpty: false,
      options: () => [],
      transformModelValue: (newValue: SelectOption | undefined | null) =>
        newValue ? newValue.value : '',
    }
  );

  const selectedOption = computed({
    get() {
      let selectedItem = null;

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
          label: String(props.modelValue),
          value: props.modelValue,
        };
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

  const reference = useTemplateRef<HTMLElement | null>('reference');
  const comboboxButton = useTemplateRef<ClickableRef | null>('comboboxButton');
  const query = ref('');

  const canClear = computed(() => {
    return props.clearable && !props.disabled && props.modelValue !== '';
  });

  const shouldOpenOnFocus = computed(() => {
    if (props.disabled) {
      return false;
    }

    if (props.modelValue !== '') {
      return false;
    }

    return props.showAllOnEmpty || props.requireOptionMatch;
  });

  const referenceCoordinates = computed(() => {
    const coordinates = reference.value?.getBoundingClientRect();
    if (coordinates) {
      return coordinates;
    }

    return new DOMRect();
  });

  function matchesQuery(query: MaybeRef<string>, item: MaybeRef<SelectOption>) {
    const lowerQuery = String(unref(query)).toLowerCase();
    const option = unref(item);

    return (
      option.label.toLowerCase().includes(lowerQuery) ||
      option.value.toString().toLowerCase().includes(lowerQuery) ||
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

  function handleFocus(open: boolean) {
    if (open || !shouldOpenOnFocus.value) {
      return;
    }

    clickComboboxButton();
  }

  function clearValue() {
    query.value = '';
    emit('update:modelValue', '');
  }

  function clickComboboxButton() {
    const button = comboboxButton.value;

    if (button instanceof HTMLElement) {
      button.click();

      return;
    }

    button?.$el?.click();
  }
</script>

<template>
  <div class="relative w-full" ref="reference">
    <Combobox
      v-model="selectedOption"
      :disabled="props.disabled"
      v-slot="{open}"
    >
      <ComboboxInput
        @change="query = $event.target.value"
        @focus="handleFocus(open)"
        class="input"
        :class="props.class"
        :display-value="displayValue"
        :placeholder="placeholder"
      />
      <craft-button
        v-if="canClear"
        class="absolute inset-y-1 right-7 flex items-center"
        type="button"
        appearance="plain"
        size="small"
        icon
        :aria-label="`Clear ${label}`"
        @mousedown.prevent
        @click.stop="clearValue"
      >
        <craft-icon name="xmark" style="font-size: 0.8em"></craft-icon>
      </craft-button>
      <ComboboxButton
        class="absolute inset-y-1 right-1 flex items-center"
        type="button"
        as="craft-button"
        appearance="plain"
        size="small"
        icon
        :aria-label="label"
        ref="comboboxButton"
      >
        <craft-icon name="chevron-down" style="font-size: 0.8em"></craft-icon>
      </ComboboxButton>
      <TransitionRoot
        leave="transition ease-in duration-100"
        leave-from="opacity-100"
        leave-to="opacity-0"
        @after-leave="query = ''"
      >
        <ComboboxOptions
          class="options"
          :style="{
            position: 'fixed',
            insetInlineStart: `${referenceCoordinates.left}px`,
            width: `${referenceCoordinates.width}px`,
            insetBlockStart: `${referenceCoordinates.bottom}px`,
          }"
        >
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
              <template
                v-for="(option, optionIdx) in item.options"
                :key="optionIdx"
              >
                <slot name="option" :option="option">
                  <InputComboboxOption :option="option" />
                </slot>
              </template>
            </template>
            <template v-else>
              <slot name="option" :option="item">
                <InputComboboxOption :option="item" />
              </slot>
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
    border-bottom: 1px solid var(--c-color-neutral-border-quiet);
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
    margin-block-start: var(--c-spacing-1px);
    max-height: calc(var(--c-spacing) * 60);
    overflow: auto;
    border-radius: var(--c-radius-md);
    box-shadow: var(--c-shadow-sm);
    background-color: var(--c-surface-overlay);
    border: 1px solid var(--c-color-neutral-border-quiet);
    width: 100%;
    max-width: 100%;
    z-index: 40;
    display: grid;
    gap: var(--c-spacing-1px);

    &:nth-child(even) {
      background-color: var(--c-color-neutral-fill-quiet);
    }
  }
</style>
