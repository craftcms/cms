<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import type CraftCheckbox from '@craftcms/cp/components/checkbox/checkbox.ts.mjs';
  import type {CheckboxOption} from '@/common/types';
  import CheckboxGroupItem from '@/common/form/CheckboxGroupItem.vue';
  import {useReorderableItems} from '@/common/composables/useReorderableItems';

  const emit = defineEmits<{
    (e: 'update:modelValue', value: Array<string>): void;
    (e: 'update:options', value: Array<CheckboxOption>): void;
  }>();

  const props = withDefaults(
    defineProps<{
      name?: string;
      label?: string;
      disabled?: boolean;
      modelValue: Array<string>;
      options: Array<CheckboxOption>;
      allowSelectAll?: boolean;
      sortable?: boolean;
    }>(),
    {allowSelectAll: false, sortable: false}
  );

  function handleValueChange(event: CustomEvent) {
    const target = event.target as CraftCheckbox;
    emit('update:modelValue', target.modelValue);
  }

  function reorder(startIndex: number, finishIndex: number) {
    if (finishIndex < 0 || finishIndex > props.options.length - 1) return;
    const items = [...props.options];
    const [removed] = items.splice(startIndex, 1);
    if (removed === undefined) return;
    items.splice(finishIndex, 0, removed);
    emit('update:options', items);
  }

  function getRowPosition(index: number): 'first' | 'middle' | 'last' {
    if (index === 0) return 'first';
    if (index === props.options.length - 1) return 'last';
    return 'middle';
  }

  const {setItemRef, setHandleRef, getDragState, getDropState} =
    useReorderableItems({
      getItemIds: () => props.options.map((option) => option.value),
      onReorder: reorder,
      enabled: () => props.sortable && props.options.length > 1,
    });
</script>

<template>
  <craft-checkbox-group
    :name="name"
    :label="label"
    .modelValue="modelValue"
    @model-value-changed="handleValueChange"
    :disabled="disabled"
  >
    <craft-checkbox-indeterminate
      v-if="allowSelectAll"
      :label="t('All')"
      :class="{'checkbox-group__all--sortable': sortable}"
    >
      <div
        class="checkbox-group__items"
        :class="{'checkbox-group__items--sortable': sortable}"
      >
        <CheckboxGroupItem
          v-for="(option, index) in options"
          :key="option.value"
          :ref="(el) => setItemRef(el as HTMLElement, option.value)"
          :option="option"
          :sortable="sortable"
          :index="index"
          :position="getRowPosition(index)"
          :drag-state="getDragState(option.value)"
          :drop-state="getDropState(option.value)"
          @handle-ref="(el) => setHandleRef(el, option.value)"
          @reorder="reorder"
        >
          <template #label="{option: slotOption}">
            <slot name="label" :option="slotOption">{{
              slotOption.label
            }}</slot>
          </template>
        </CheckboxGroupItem>
      </div>
    </craft-checkbox-indeterminate>

    <div
      v-else
      class="checkbox-group__items"
      :class="{'checkbox-group__items--sortable': sortable}"
    >
      <CheckboxGroupItem
        v-for="(option, index) in options"
        :key="option.value"
        :ref="(el) => setItemRef(el as HTMLElement, option.value)"
        :option="option"
        :sortable="sortable"
        :index="index"
        :position="getRowPosition(index)"
        :drag-state="getDragState(option.value)"
        :drop-state="getDropState(option.value)"
        @handle-ref="(el) => setHandleRef(el, option.value)"
        @reorder="reorder"
      >
        <template #label="{option: slotOption}">
          <slot name="label" :option="slotOption">{{ slotOption.label }}</slot>
        </template>
      </CheckboxGroupItem>
    </div>
  </craft-checkbox-group>
</template>

<style scoped lang="scss">
  // Width of the drag-handle column + its gap. Defined on the group host so it
  // is inherited by both the slotted "All" checkbox and the item grid, keeping
  // every checkbox column aligned.
  craft-checkbox-group {
    --cg-handle-size: var(--c-size-control-sm);
    --cg-gutter: calc(var(--cg-handle-size) + var(--c-spacing-sm));
  }

  .checkbox-group__items {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }

  // Parent grid that the item rows subgrid onto: [handle | content].
  .checkbox-group__items--sortable {
    display: grid;
    grid-template-columns: var(--cg-handle-size) 1fr;
    column-gap: var(--c-spacing-sm);
    row-gap: var(--c-spacing-sm);
  }

  // The "All" checkbox lives in the indeterminate's shadow DOM and has no
  // handle, so we reserve a left gutter on it and pull the slotted item grid
  // back into that gutter. The handles sit in the gutter and the option
  // checkboxes line up with "All".
  .checkbox-group__all--sortable {
    padding-inline-start: var(--cg-gutter, 0);
  }

  .checkbox-group__all--sortable > .checkbox-group__items--sortable {
    margin-inline-start: calc(-1 * var(--cg-gutter));
  }
</style>
