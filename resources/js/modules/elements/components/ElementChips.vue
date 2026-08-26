<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import '@craftcms/ui/components/chip/chip';
  import DragShadow from '@/common/components/DragShadow.vue';
  import {
    useReorderableItems,
    type DropState,
  } from '@/common/composables/useReorderableItems';
  import type {Selectable} from '@/common/composables/useSelectable';
  import {isInteractiveClick} from '@/common/utils/dom';

  /**
   * The element data a chip draws. Everything past `id` and `label` is optional,
   * so a caller with nothing but ids still gets a usable list.
   */
  interface ChipElement {
    id: number;
    label?: string;
    siteId?: number | string | null;
    /**
     * Already resolved to a fill and a label — which statuses exist, and what
     * they're called, is an element-type concern the server settles.
     */
    status?: {fill: string; label: string; draft: boolean} | null;
  }

  const props = withDefaults(
    defineProps<{
      data?: Array<ChipElement>;
      /** Selection, usually from `useElementList`. */
      selection: Selectable<any>;
      selectable?: boolean;
      readOnly?: boolean;
      /** Lay the chips out in a wrapping row rather than stacked. */
      inline?: boolean;
      /** Offer drag-and-drop and the reorder button. */
      sortable?: boolean;
    }>(),
    {
      data: () => [],
      selectable: false,
      readOnly: false,
      inline: false,
      sortable: false,
    }
  );

  const emit = defineEmits<{
    /** A chip was double-clicked somewhere that isn't one of its controls. */
    (event: 'edit', element: ChipElement): void;
    (event: 'reorder', startIndex: number, finishIndex: number): void;
  }>();

  const ids = computed(() => props.data.map((element) => element.id));

  /** Falls back to the id, so a list of bare ids still reads as something. */
  function labelFor(element: ChipElement): string {
    return element.label ?? String(element.id);
  }

  const {setItemRef, setHandleRef, getDragState, getDropState, getRowPosition} =
    useReorderableItems({
      getItemIds: () => ids.value,
      onReorder: (startIndex, finishIndex) =>
        emit('reorder', startIndex, finishIndex),
      enabled: () => props.sortable,
    });

  /** The drop indicator only shows while something is actually over a chip. */
  function overDropState(
    id: number | string
  ): Extract<DropState, {type: 'is-over'}> | null {
    const state = getDropState(id);

    return state.type === 'is-over' ? state : null;
  }

  function move(index: number, delta: number): void {
    const target = index + delta;

    if (target < 0 || target >= props.data.length) {
      return;
    }

    emit('reorder', index, target);
  }

  /** Selection is offered, and not frozen. */
  const canSelect = computed(() => props.selectable && !props.readOnly);

  function onChipClick(element: ChipElement, event: MouseEvent): void {
    if (!canSelect.value) {
      return;
    }

    props.selection.handleClick(element.id, event);
  }

  function onSelectedChange(
    element: ChipElement,
    detail: {selected: boolean; shiftKey: boolean}
  ): void {
    if (!canSelect.value) {
      return;
    }

    props.selection.setChecked(element.id, detail.selected, {
      shiftKey: detail.shiftKey,
    });
  }

  /**
   * A double-click opens the element, except when it landed on one of the chip's
   * own controls — the checkbox, the reorder button, an action menu, a link.
   * Those belong to the control, so the walk (which pierces shadow DOM, and so
   * sees the checkbox the chip renders inside itself) filters them out.
   */
  function onDoubleClick(element: ChipElement, event: MouseEvent): void {
    if (isInteractiveClick(event, event.currentTarget)) {
      return;
    }

    emit('edit', element);
  }
</script>

<!--
  The chip body of an element list — the `list` and `list-inline` view modes,
  alongside `ElementCards` and `ElementThumbs`. Like those, it takes a
  `selection` rather than owning one, so it works for a relation field, an
  index, or anything else with an ordered list of ids.

  Chips carry more caller-specific furniture than cards do, so the pieces a host
  supplies come through named slots rather than being baked in: `prefix` and
  `suffix` for controls inside the chip, and `append` for anything that has to
  ride along in the chip body (a relation field's hidden inputs, say).
-->
<template>
  <ul :class="inline ? 'element-chips element-chips--inline' : 'element-chips'">
    <li
      v-for="(element, index) in data"
      :key="element.id"
      :ref="(el) => setItemRef(el as HTMLElement, element.id)"
      class="element-chips__item"
      :class="{
        'element-chips__item--dragging':
          getDragState(element.id).type === 'is-dragging',
        'element-chips__item--hidden':
          getDragState(element.id).type === 'is-dragging-and-left-self',
      }"
    >
      <DragShadow
        v-if="overDropState(element.id)?.closestEdge === 'top'"
        :height="overDropState(element.id)?.draggingRect?.height"
      />

      <craft-chip
        size="small"
        :data-id="String(element.id)"
        :data-site-id="element.siteId ?? undefined"
        :selectable="canSelect || undefined"
        :selected="selection.isSelected(element.id) || undefined"
        :select-label="t('Select {label}', {label: labelFor(element)})"
        :show-status="!!element.status || undefined"
        @selected-change="
          (event: CustomEvent<{selected: boolean; shiftKey: boolean}>) =>
            onSelectedChange(element, event.detail)
        "
        @click="(event: MouseEvent) => onChipClick(element, event)"
        @dblclick="(event: MouseEvent) => onDoubleClick(element, event)"
      >
        <div slot="prefix" class="flex items-center px-1 gap-1">
          <span
            v-if="sortable"
            :ref="(el) => setHandleRef(el as HTMLElement, element.id)"
            class="drag-handle"
          >
            <craft-reorder-button
              :position="getRowPosition(index)"
              @reorder="
                (event: CustomEvent<{direction: 'up' | 'down'}>) =>
                  move(index, event.detail.direction === 'up' ? -1 : 1)
              "
            ></craft-reorder-button>
          </span>
          <slot name="prefix" :element="element" :index="index"></slot>
        </div>

        <!--
          The chip's status slot, as `ElementHtml::chipHtml()` fills it on the
          index: a dot filled from the status definition, or the draft icon.
          Absent entirely for element types that don't show a status.
        -->
        <div v-if="element.status" slot="status">
          <span
            v-if="element.status.draft"
            class="icon"
            data-icon="draft"
            role="img"
            :aria-label="`${t('Status:')} ${element.status.label}`"
          ></span>
          <craft-indicator
            v-else
            :fill="element.status.fill"
            :label="`${t('Status:')} ${element.status.label}`"
          ></craft-indicator>
        </div>

        {{ labelFor(element) }}
        <slot name="append" :element="element" :index="index"></slot>

        <div slot="suffix" class="flex gap-0.5 items-center">
          <slot name="suffix" :element="element" :index="index"></slot>
        </div>
      </craft-chip>

      <DragShadow
        v-if="overDropState(element.id)?.closestEdge === 'bottom'"
        :height="overDropState(element.id)?.draggingRect?.height"
      />
    </li>
  </ul>
</template>

<style scoped lang="scss">
  .element-chips {
    display: grid;
    gap: var(--c-spacing-xs);
    align-items: start;
  }

  .element-chips--inline {
    display: flex;
    flex-wrap: wrap;
  }

  // Dragging, but still over itself — dim rather than remove, so the list
  // doesn't reflow under the cursor.
  .element-chips__item--dragging {
    opacity: 0.4;
  }

  // Dragged away from itself: collapse vertically but keep the width, so the
  // container doesn't shrink when the widest chip is the one being moved.
  .element-chips__item--hidden {
    visibility: hidden;
    height: 0;
    overflow: hidden;
    margin: 0;
    padding: 0;
  }
</style>
