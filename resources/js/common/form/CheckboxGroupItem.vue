<script setup lang="ts">
  import {computed, ref, watch} from 'vue';
  import type {CheckboxOption} from '@/common/types';
  import DragShadow from '@/common/components/DragShadow.vue';
  import type {
    DragState,
    DropState,
  } from '@/common/composables/useReorderableItems';

  const emit = defineEmits<{
    (e: 'handle-ref', el: HTMLElement | null): void;
    (e: 'reorder', from: number, to: number): void;
  }>();

  const props = defineProps<{
    option: CheckboxOption;
    sortable?: boolean;
    index: number;
    position?: 'first' | 'middle' | 'last';
    dragState: DragState;
    dropState: DropState;
  }>();

  // When sortable, the root is a subgrid that inherits the [handle | content]
  // columns from the parent `.checkbox-group__items` grid so every handle and
  // checkbox lines up across rows (and with the handle-less "All" row).
  // NOTE: keep a single root element here — a root-level template comment would
  // make this a fragment component, breaking `$el`-based ref resolution used by
  // useReorderableItems (drag-and-drop would silently stop working).
  const overDropState = computed(() =>
    props.dropState.type === 'is-over' ? props.dropState : null
  );

  // The drag handle is a dedicated grip element so pragmatic-drag-and-drop binds
  // to a plain element while the nested ReorderButton stays clickable.
  const handleRef = ref<HTMLElement | null>(null);

  watch(handleRef, (el) => emit('handle-ref', el), {immediate: true});
</script>

<template>
  <div
    v-if="sortable"
    class="checkbox-group-item"
    :class="{
      'checkbox-group-item--dragging': dragState.type === 'is-dragging',
      'checkbox-group-item--hidden':
        dragState.type === 'is-dragging-and-left-self',
    }"
  >
    <DragShadow
      v-if="overDropState?.closestEdge === 'top'"
      position="top"
      :height="overDropState?.draggingRect?.height"
    />

    <span
      v-if="!option.disabled"
      ref="handleRef"
      class="checkbox-group-item__handle"
    >
      <craft-reorder-button
        :position="position"
        @reorder="
          (e: CustomEvent<{direction: 'up' | 'down'}>) =>
            emit(
              'reorder',
              index,
              e.detail.direction === 'up' ? index - 1 : index + 1
            )
        "
      >
      </craft-reorder-button>
    </span>

    <craft-checkbox
      class="checkbox-group-item__checkbox"
      .choiceValue="option.value"
      .checked="option.checked"
      .disabled="option.disabled"
    >
      <label slot="label">
        <slot name="label" :option="option">{{ option.label }}</slot>
      </label>
      <div v-if="option.info" slot="help-text" v-html="option.info"></div>
    </craft-checkbox>

    <DragShadow
      v-if="overDropState?.closestEdge === 'bottom'"
      position="bottom"
      :height="overDropState?.draggingRect?.height"
    />
  </div>

  <craft-checkbox
    v-else
    .choiceValue="option.value"
    .checked="option.checked"
    .disabled="option.disabled"
  >
    <label slot="label">
      <slot name="label" :option="option">{{ option.label }}</slot>
    </label>
    <div v-if="option.info" slot="help-text" v-html="option.info"></div>
  </craft-checkbox>
</template>

<style scoped lang="scss">
  .checkbox-group-item {
    display: grid;
    grid-template-columns: subgrid;
    grid-column: 1 / -1;
    align-items: center;
    column-gap: var(--c-spacing-sm);
  }

  // Full-width drop indicators span both columns.
  .checkbox-group-item :deep(.drag-shadow) {
    grid-column: 1 / -1;
  }

  .checkbox-group-item__handle {
    grid-column: 1;
    justify-self: stretch;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--c-text-quiet);
    cursor: grab;
  }

  .checkbox-group-item__handle:active {
    cursor: grabbing;
  }

  .checkbox-group-item__checkbox {
    grid-column: 2;
  }

  // Item is being dragged but still over itself — show with reduced opacity.
  .checkbox-group-item--dragging {
    opacity: 0.4;
  }

  // Item dragged away from itself — collapse without affecting width.
  .checkbox-group-item--hidden {
    visibility: hidden;
    height: 0;
    overflow: hidden;
  }
</style>
