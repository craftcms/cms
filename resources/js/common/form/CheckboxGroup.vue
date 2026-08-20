<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import type CraftCheckbox from '@craftcms/ui/components/checkbox/checkbox';
  import type {CheckboxOption} from '@/common/types';
  import CheckboxGroupItem from '@/common/form/CheckboxGroupItem.vue';
  import {useReorderableItems} from '@/common/composables/useReorderableItems';
  import {ignoreModelValueInitialization} from '@/modules/forms/runtime';

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

  // The checkbox group derives each checkbox's checked state from its
  // modelValue, which would otherwise override a per-option `checked` flag. Fold
  // any forced-checked options (e.g. a disabled, always-on column) into the
  // value the group receives so they render checked.
  const groupModelValue = computed(() => {
    const forced = props.options
      .filter((option) => option.checked)
      .map((option) => option.value);
    return [...new Set([...props.modelValue, ...forced])];
  });

  // Lion announces a model value as its children register, before the group
  // has applied ours. Treating that as a change would emit an empty selection
  // over whatever the caller passed in.
  const handleValueChange = ignoreModelValueInitialization((event) => {
    const target = event.target as CraftCheckbox;
    emit('update:modelValue', target.modelValue);
  });

  // Only non-disabled options are reorderable; disabled options (e.g. a pinned
  // "always on" column) keep their position.
  const sortableOptions = computed(() =>
    props.options.filter((option) => !option.disabled)
  );

  function sortableIndexOf(option: CheckboxOption): number {
    return sortableOptions.value.findIndex((o) => o.value === option.value);
  }

  function reorder(startIndex: number, finishIndex: number) {
    const sortable = sortableOptions.value.slice();
    if (finishIndex < 0 || finishIndex > sortable.length - 1) return;
    const [removed] = sortable.splice(startIndex, 1);
    if (removed === undefined) return;
    sortable.splice(finishIndex, 0, removed);

    // Rebuild the full list, leaving disabled (pinned) options in place and
    // filling the reorderable slots with the new order.
    let next = 0;
    const items = props.options.map((option) =>
      option.disabled ? option : sortable[next++]!
    );
    emit('update:options', items);
  }

  function getRowPosition(index: number): 'first' | 'middle' | 'last' {
    if (index <= 0) return 'first';
    if (index === sortableOptions.value.length - 1) return 'last';
    return 'middle';
  }

  const {setItemRef, setHandleRef, getDragState, getDropState} =
    useReorderableItems({
      getItemIds: () => sortableOptions.value.map((option) => option.value),
      onReorder: reorder,
      enabled: () => props.sortable && sortableOptions.value.length > 1,
    });
</script>

<template>
  <craft-checkbox-group
    :name="name"
    :label="label"
    .modelValue="groupModelValue"
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
          v-for="option in options"
          :key="option.value"
          :ref="(el) => setItemRef(el as HTMLElement, option.value)"
          :option="option"
          :sortable="sortable"
          :index="sortableIndexOf(option)"
          :position="getRowPosition(sortableIndexOf(option))"
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
        v-for="option in options"
        :key="option.value"
        :ref="(el) => setItemRef(el as HTMLElement, option.value)"
        :option="option"
        :sortable="sortable"
        :index="sortableIndexOf(option)"
        :position="getRowPosition(sortableIndexOf(option))"
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
    gap: var(--c-checkbox-group-spacing);
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
