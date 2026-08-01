<script setup lang="ts">
  import {computed, ref, watch} from 'vue';
  import {t} from '@craftcms/ui/utilities/translate';
  import type {FormElementBinding, JsonValue} from '../types';
  import {
    reorderItems,
    reorderPosition,
    type ReorderDirection,
  } from '../reorder';
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/reorder-button/reorder-button';

  type Option = {key: string; label: string; value: unknown};

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: unknown[]];
  }>();

  const selection = ref('');
  const options = computed<Option[]>(() =>
    Array.isArray(props.config.options)
      ? (props.config.options as Option[])
      : []
  );
  const identityKey = computed(() =>
    typeof props.config.identityKey === 'string' ? props.config.identityKey : ''
  );
  const values = computed<unknown[]>(() =>
    Array.isArray(props.binding?.value) ? props.binding.value : []
  );
  const availableOptions = computed(() =>
    options.value.filter(
      (option) => !values.value.some((value) => identity(value) === option.key)
    )
  );

  watch(
    availableOptions,
    (availableOptions) => {
      if (!availableOptions.some(({key}) => key === selection.value)) {
        selection.value = availableOptions[0]?.key ?? '';
      }
    },
    {immediate: true}
  );

  function identity(value: unknown): string {
    if (identityKey.value === '') {
      return String(value ?? '');
    }

    if (value && typeof value === 'object') {
      return String(
        (value as Record<string, unknown>)[identityKey.value] ?? ''
      );
    }

    return '';
  }

  function label(value: unknown): string {
    if (value && typeof value === 'object') {
      const name = (value as Record<string, unknown>).name;

      if (typeof name === 'string') {
        return name;
      }
    }

    return (
      options.value.find(({key}) => key === identity(value))?.label ??
      identity(value)
    );
  }

  function add(): void {
    const option = availableOptions.value.find(
      ({key}) => key === selection.value
    );

    if (!option || props.binding?.readOnly) {
      return;
    }

    emit('update:value', [...values.value, option.value]);
  }

  function remove(index: number): void {
    emit(
      'update:value',
      values.value.filter((_, valueIndex) => valueIndex !== index)
    );
  }

  function reorder(
    index: number,
    event: CustomEvent<{direction: ReorderDirection}>
  ): void {
    const reordered = reorderItems(values.value, index, event.detail.direction);

    if (!reordered) {
      return;
    }

    emit('update:value', reordered);
  }
</script>

<template>
  <div
    :id="typeof attributes.id === 'string' ? attributes.id : undefined"
    role="group"
    :aria-labelledby="
      typeof attributes['aria-labelledby'] === 'string'
        ? attributes['aria-labelledby']
        : undefined
    "
    :aria-describedby="
      typeof attributes['aria-describedby'] === 'string'
        ? attributes['aria-describedby']
        : undefined
    "
  >
    <div
      v-for="(value, index) in values"
      :key="identity(value)"
      data-object-select-row
      class="object-select-row"
    >
      <craft-reorder-button
        :disabled="binding?.readOnly || values.length < 2"
        :position="reorderPosition(index, values.length)"
        @reorder="reorder(index, $event)"
      ></craft-reorder-button>
      <span>{{ label(value) }}</span>
      <craft-button
        type="button"
        size="small"
        variant="plain"
        :disabled="binding?.readOnly"
        :aria-label="t('Remove {label}', {label: label(value)})"
        @activate="remove(index)"
      >
        {{ t('Remove') }}
      </craft-button>
    </div>

    <div v-if="availableOptions.length" class="object-select-add">
      <select
        v-model="selection"
        :aria-label="t('Available options')"
        :disabled="binding?.readOnly"
      >
        <option
          v-for="option in availableOptions"
          :key="option.key"
          :value="option.key"
        >
          {{ option.label }}
        </option>
      </select>
      <craft-button
        type="button"
        variant="dashed"
        :disabled="binding?.readOnly"
        @activate="add"
      >
        {{ t('Add') }}
      </craft-button>
    </div>
  </div>
</template>

<style scoped>
  .object-select-row {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

  .object-select-add {
    display: flex;
    gap: var(--c-spacing-sm);
    margin-block-start: var(--c-spacing-sm);
  }
</style>
