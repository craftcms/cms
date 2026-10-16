<script setup lang="ts">
  import {computed, nextTick, ref} from 'vue';
  import {ButtonVariant, t} from '@craftcms/ui';
  import type {CheckboxOption} from '@/common/types';
  import DragShadow from '@/common/components/DragShadow.vue';
  import {useReorderableItems} from '@/common/composables/useReorderableItems';

  /**
   * The sortable presentation of a multi-select: only the selected options are
   * listed (draggable, each with a Remove button), and the rest are offered in
   * an "Add item" menu, which goes away once everything is selected.
   *
   * Display order is owned by `options`: reordering, and adding an option (which
   * moves it after the last selected one), emit `update:options`.
   */
  const emit = defineEmits<{
    (e: 'update:modelValue', value: Array<string>): void;
    (e: 'update:options', value: Array<CheckboxOption>): void;
  }>();

  const props = defineProps<{
    name?: string;
    label?: string;
    disabled?: boolean;
    modelValue: Array<string>;
    options: Array<CheckboxOption>;
  }>();

  // Forced-checked options (e.g. a disabled, always-on column) count as
  // selected regardless of the model value.
  const selectedValues = computed(
    () =>
      new Set([
        ...props.modelValue,
        ...props.options
          .filter((option) => option.checked)
          .map((option) => option.value),
      ])
  );

  const selectedOptions = computed(() =>
    props.options.filter((option) => selectedValues.value.has(option.value))
  );

  const availableOptions = computed(() =>
    props.disabled
      ? []
      : props.options.filter(
          (option) =>
            !option.disabled && !selectedValues.value.has(option.value)
        )
  );

  function isMovable(option: CheckboxOption): boolean {
    return (
      !props.disabled &&
      !option.disabled &&
      selectedValues.value.has(option.value)
    );
  }

  const sortableOptions = computed(() =>
    props.options.filter((option) => isMovable(option))
  );

  function sortableIndexOf(option: CheckboxOption): number {
    return sortableOptions.value.findIndex((o) => o.value === option.value);
  }

  function getRowPosition(option: CheckboxOption): 'first' | 'middle' | 'last' {
    const index = sortableIndexOf(option);
    if (index <= 0) return 'first';
    if (index === sortableOptions.value.length - 1) return 'last';
    return 'middle';
  }

  function reorder(startIndex: number, finishIndex: number) {
    const sortable = sortableOptions.value.slice();
    if (finishIndex < 0 || finishIndex > sortable.length - 1) return;
    const [removed] = sortable.splice(startIndex, 1);
    if (removed === undefined) return;
    sortable.splice(finishIndex, 0, removed);

    // Only the movable slots take the new order; pinned and unselected options
    // keep their place.
    let next = 0;
    emit(
      'update:options',
      props.options.map((option) =>
        isMovable(option) ? sortable[next++]! : option
      )
    );
  }

  const {setItemRef, setHandleRef, getDragState, getDropState} =
    useReorderableItems({
      getItemIds: () => sortableOptions.value.map((option) => option.value),
      onReorder: reorder,
      enabled: () => sortableOptions.value.length > 1,
    });

  const root = ref<HTMLElement | null>(null);

  function focusAfterRender(selector: string | null): void {
    void nextTick(() => {
      const target =
        (selector ? root.value?.querySelector<HTMLElement>(selector) : null) ??
        root.value?.querySelector<HTMLElement>('[data-add-option]');
      target?.focus();
    });
  }

  function removeButtonSelector(option: CheckboxOption): string {
    return `[data-remove-option="${CSS.escape(option.value)}"]`;
  }

  function add(option: CheckboxOption): void {
    // New selections join the end of the list.
    const lastSelected = selectedOptions.value.at(-1);
    const rest = props.options.filter((o) => o.value !== option.value);
    const insertAt = lastSelected
      ? rest.findIndex((o) => o.value === lastSelected.value) + 1
      : 0;
    const next = [...rest.slice(0, insertAt), option, ...rest.slice(insertAt)];

    if (next.some((o, index) => o.value !== props.options[index]?.value)) {
      emit('update:options', next);
    }
    emit('update:modelValue', [...props.modelValue, option.value]);

    // The menu hands focus back to its button; once nothing is left to add,
    // that button is gone, so land on the new item instead.
    if (availableOptions.value.length === 1) {
      focusAfterRender(removeButtonSelector(option));
    }
  }

  function remove(option: CheckboxOption): void {
    const rows = selectedOptions.value;
    const index = rows.findIndex((o) => o.value === option.value);
    const neighbor = [
      ...rows.slice(index + 1),
      ...rows.slice(0, index).reverse(),
    ].find((o) => isMovable(o));

    emit(
      'update:modelValue',
      props.modelValue.filter((value) => value !== option.value)
    );
    focusAfterRender(neighbor ? removeButtonSelector(neighbor) : null);
  }

  function dropEdge(option: CheckboxOption) {
    const state = getDropState(option.value);
    return state.type === 'is-over' ? state : null;
  }

  // With its own label, the list is a labelled fieldset; otherwise the
  // surrounding field labels it.
  const wrapperAttrs = computed(() =>
    props.label ? {label: props.label, fieldset: true} : {}
  );
</script>

<template>
  <component :is="label ? 'craft-field' : 'div'" v-bind="wrapperAttrs">
    <div ref="root" slot="input" class="selected-option-list">
      <div v-if="selectedOptions.length" class="selected-option-list__items">
        <div
          v-for="option in selectedOptions"
          :key="option.value"
          :ref="(el) => setItemRef(el as HTMLElement, option.value)"
          class="selected-option-list__item"
          :class="{
            'selected-option-list__item--dragging':
              getDragState(option.value).type === 'is-dragging',
            'selected-option-list__item--hidden':
              getDragState(option.value).type === 'is-dragging-and-left-self',
          }"
        >
          <DragShadow
            v-if="dropEdge(option)?.closestEdge === 'top'"
            position="top"
            :height="dropEdge(option)?.draggingRect?.height"
          />

          <span
            v-if="isMovable(option)"
            :ref="(el) => setHandleRef(el as HTMLElement, option.value)"
            class="selected-option-list__handle"
          >
            <craft-reorder-button
              :position="getRowPosition(option)"
              @craft-reorder="
                (e: CustomEvent<{direction: 'up' | 'down'}>) =>
                  reorder(
                    sortableIndexOf(option),
                    sortableIndexOf(option) +
                      (e.detail.direction === 'up' ? -1 : 1)
                  )
              "
            ></craft-reorder-button>
          </span>

          <div class="selected-option-list__label">
            <slot name="label" :option="option">{{ option.label }}</slot>
            <div
              v-if="option.info"
              class="selected-option-list__info"
              v-html="option.info"
            ></div>
          </div>

          <craft-button
            v-if="isMovable(option)"
            type="button"
            icon="xmark"
            size="small"
            :variant="ButtonVariant.Plain"
            :data-remove-option="option.value"
            :aria-label="t('Remove {name}', {name: option.label})"
            @click="remove(option)"
          ></craft-button>

          <input
            v-if="name && !disabled && !option.disabled"
            type="hidden"
            :name="name"
            :value="option.value"
          />

          <DragShadow
            v-if="dropEdge(option)?.closestEdge === 'bottom'"
            position="bottom"
            :height="dropEdge(option)?.draggingRect?.height"
          />
        </div>
      </div>

      <craft-action-menu
        v-if="availableOptions.length"
        :searchable="availableOptions.length > 10 || undefined"
      >
        <craft-button
          slot="invoker"
          type="button"
          size="small"
          data-add-option
          :variant="ButtonVariant.Dashed"
        >
          <craft-icon name="plus" slot="prefix"></craft-icon>
          {{ t('Add item') }}
        </craft-button>
        <div slot="content">
          <craft-action-item
            v-for="option in availableOptions"
            :key="option.value"
            @click="add(option)"
          >
            <slot name="label" :option="option">{{ option.label }}</slot>
          </craft-action-item>
        </div>
      </craft-action-menu>
    </div>
  </component>
</template>

<style scoped lang="scss">
  .selected-option-list {
    display: grid;
    justify-items: start;
    gap: var(--c-spacing-sm);
  }

  .selected-option-list__items {
    display: grid;
    row-gap: var(--c-spacing-xs);
    justify-self: stretch;
  }

  // The Remove button sits right after its label. Pinned options have no
  // handle, so their handle slot is reserved to keep the labels aligned.
  .selected-option-list__item {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    column-gap: var(--c-spacing-sm);
    padding-inline-start: calc(var(--c-size-control-sm) + var(--c-spacing-sm));
  }

  .selected-option-list__item :deep(.drag-shadow) {
    flex-basis: 100%;
  }

  .selected-option-list__handle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    inline-size: var(--c-size-control-sm);
    margin-inline-start: calc(
      -1 * (var(--c-size-control-sm) + var(--c-spacing-sm))
    );
    color: var(--c-text-quiet);
    cursor: grab;
  }

  .selected-option-list__handle:active {
    cursor: grabbing;
  }

  .selected-option-list__label {
    min-width: 0;
  }

  .selected-option-list__info {
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
  }

  .selected-option-list__item--dragging {
    opacity: 0.4;
  }

  .selected-option-list__item--hidden {
    visibility: hidden;
    height: 0;
    overflow: hidden;
  }
</style>
