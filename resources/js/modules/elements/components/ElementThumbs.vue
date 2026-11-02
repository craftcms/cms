<script setup lang="ts">
  import {t, type ChipThumbSizeValue} from '@craftcms/ui';
  import {computed} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import DragShadow from '@/common/components/DragShadow.vue';
  import {
    useReorderableItems,
    type DropState,
  } from '@/common/composables/useReorderableItems';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import PlaceholderShape from '@/common/components/PlaceholderShape.vue';
  import type {Selectable} from '@/common/composables/useSelectable';
  import {isInteractiveClick} from '@/common/utils/dom';
  import {
    isInteractiveItemEvent,
    type ElementIndexItemBehavior,
  } from '@/modules/elements/types/item-behavior';

  interface ThumbElement {
    id: string | number;
    // The server sends the element's edit URL, which is null when it has none.
    url?: string | null;
    // A tile with no `url` can still link its label, e.g. an asset folder's.
    labelUrl?: string | null;
    labelLinkAttributes?: Record<string, unknown>;
    thumbHtml?: string;
    label: string;
  }

  const props = withDefaults(
    defineProps<{
      selection: Selectable<any>;
      data?: Array<ThumbElement>;
      selectable?: boolean;
      /**
       * Whether to draw the select-all header. Off for a host that has its own
       * selection toolbar — the chrome belongs with the count and bulk actions
       * it goes with, not duplicated per body.
       */
      selectAll?: boolean;
      /** Offer drag-and-drop and the reorder button. */
      sortable?: boolean;
      readOnly?: boolean;
      /**
       * Disables selection, sorting and reordering while a request runs,
       * keeping the controls in place so the layout doesn't shift.
       */
      interactionsDisabled?: boolean;
      loading?: boolean;
      itemBehavior?: ElementIndexItemBehavior<ThumbElement>;
      /**
       * The tiles' thumbnail size; `lg` matches a card's. The chips take a
       * size to suit it.
       */
      thumbSize?: ChipThumbSizeValue;
      /**
       * How many placeholder tiles to draw in place of the tiles, such as while
       * they load. Takes the place of the loading spinner.
       */
      placeholders?: number;
    }>(),
    {
      data: () => [],
      selectable: false,
      selectAll: true,
      sortable: false,
      readOnly: undefined,
      loading: false,
      thumbSize: 'lg',
      placeholders: 0,
    }
  );

  const page = usePage<{readOnly: boolean}>();
  const readOnly = computed(() => props.readOnly ?? page.props.readOnly);

  // Selection is handed in rather than derived from a table, so this body works
  // for anything with an ordered list of ids.

  const emit = defineEmits<{
    (event: 'reorder', startIndex: number, finishIndex: number): void;
    (event: 'edit', element: ThumbElement): void;
  }>();

  const ids = computed(() => props.data.map((element) => element.id));

  const {setItemRef, setHandleRef, getDragState, getDropState, getRowPosition} =
    useReorderableItems({
      getItemIds: () => ids.value,
      onReorder: (startIndex, finishIndex) =>
        emit('reorder', startIndex, finishIndex),
      enabled: () => props.sortable && !props.interactionsDisabled,
    });

  function overDropState(
    id: string | number
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

  function onSelectionChange(id: string | number, event: Event) {
    const {selected, shiftKey} = (
      event as CustomEvent<{selected: boolean; shiftKey: boolean}>
    ).detail;

    props.selection.setChecked(id, selected, {shiftKey});
  }

  // Only the label's link navigates. A click anywhere else on the tile selects
  // it, as its checkbox would.
  function onTileClick(element: ThumbElement, event: MouseEvent) {
    if (props.itemBehavior?.onClick?.(element, event)) {
      return;
    }

    if (props.selectable && !readOnly.value && !props.interactionsDisabled) {
      props.selection.handleClick(element.id, event);
    }
  }

  /**
   * A double-click opens the element, except on the tile's own controls — the
   * checkbox, an action menu, the reorder button, the label's link.
   */
  function onTileDblclick(element: ThumbElement, event: MouseEvent) {
    if (props.itemBehavior?.onDblClick?.(element, event)) {
      event.preventDefault();

      return;
    }

    if (!isInteractiveClick(event, event.currentTarget)) {
      emit('edit', element);
    }
  }

  function focusTileByIndex(index: number, el: HTMLElement) {
    const list = el.closest('ul.thumbsview');
    const items = list?.querySelectorAll<HTMLElement>(':scope > li[tabindex]');
    items?.[index]?.focus();
  }

  function onTileKeydown(
    id: number | string,
    index: number,
    event: KeyboardEvent
  ) {
    if (!props.selectable) return;
    if (isInteractiveItemEvent(event)) return;
    if (!(event.currentTarget instanceof HTMLElement)) return;
    const target = event.currentTarget;
    const last = props.data.length - 1;
    const element = props.data.find((el) => el.id === id);
    if (element && props.itemBehavior?.onKeydown?.(element, event)) {
      event.preventDefault();
      return;
    }
    if (props.interactionsDisabled) return;
    switch (event.key) {
      case 'Enter':
        if (element?.url) {
          event.preventDefault();
          window.location.assign(element.url);
          break;
        }
      // Without a link to open, Enter selects, the same as Space.
      // falls through
      case ' ': {
        event.preventDefault();
        props.selection.toggle(id);
        break;
      }
      case 'ArrowRight':
      case 'ArrowDown': {
        event.preventDefault();
        const nextIndex = Math.min(index + 1, last);
        const nextEl = props.data[nextIndex];
        if (event.shiftKey && nextEl) props.selection.extendTo(nextEl.id);
        focusTileByIndex(nextIndex, target);
        break;
      }
      case 'ArrowLeft':
      case 'ArrowUp': {
        event.preventDefault();
        const prevIndex = Math.max(index - 1, 0);
        const prevEl = props.data[prevIndex];
        if (event.shiftKey && prevEl) props.selection.extendTo(prevEl.id);
        focusTileByIndex(prevIndex, target);
        break;
      }
    }
  }

  function checkboxValue(event: Event): boolean {
    // `craft-checkbox` dispatches `model-value-changed` from the host, not from
    // an inner `<input>`, so an `instanceof HTMLInputElement` test reads every
    // change as unchecked. Since it also re-fires on programmatic `.checked`
    // updates, that turned each selection into an immediate deselection.
    return Boolean((event.target as {checked?: boolean} | null)?.checked);
  }
</script>

<template>
  <template v-if="placeholders > 0">
    <div
      class="thumbsview-header"
      v-if="selectable && selectAll"
      aria-hidden="true"
    >
      <PlaceholderShape class="checkbox-placeholder" />
    </div>
    <ul class="thumbsview" :data-thumb-size="thumbSize" aria-hidden="true">
      <li v-for="index in placeholders" :key="index">
        <craft-chip
          prefix-placement="block-start"
          appearance="outline-fill"
          data-color="white"
          full-width
          show-thumb
          :thumb-size="thumbSize"
        >
          <PlaceholderShape slot="thumbnail" class="thumb-placeholder" />
          <PlaceholderShape class="thumb-label-placeholder" />
        </craft-chip>
      </li>
    </ul>
  </template>
  <div class="grid flex-1 place-items-center min-h-50" v-else-if="loading">
    <craft-spinner></craft-spinner>
  </div>
  <template v-else-if="data.length > 0">
    <div class="thumbsview-header" v-if="selectable && selectAll">
      <craft-checkbox
        label-sr-only
        .checked="selection.allSelected.value"
        .indeterminate="selection.someSelected.value"
        .disabled="readOnly || interactionsDisabled"
        @model-value-changed="selection.toggleAll(checkboxValue($event))"
      >
        <label slot="label">{{ t('Select all') }}</label>
      </craft-checkbox>
    </div>

    <ul class="thumbsview" :data-thumb-size="thumbSize">
      <li
        v-for="(element, thumbIdx) in data"
        :key="element.id"
        :data-id="String(element.id)"
        :ref="(el) => setItemRef(el as HTMLElement, element.id)"
        v-bind="itemBehavior?.attrs?.(element)"
        :tabindex="selectable ? 0 : undefined"
        @keydown="onTileKeydown(element.id, thumbIdx, $event)"
        @click="onTileClick(element, $event)"
        @dblclick="onTileDblclick(element, $event)"
        :class="{
          element: true,
          'element--selectable': selectable,
          'element--selected': selection.isSelected(element.id),
          'element--dragging': getDragState(element.id).type === 'is-dragging',
          'element--hidden':
            getDragState(element.id).type === 'is-dragging-and-left-self',
        }"
      >
        <DragShadow
          v-if="overDropState(element.id)?.closestEdge === 'top'"
          :height="overDropState(element.id)?.draggingRect?.height"
        />
        <craft-chip
          prefix-placement="block-start"
          appearance="outline-fill"
          data-color="white"
          full-width
          show-thumb
          :thumb-size="thumbSize"
          .selectable="selectable"
          :select-label="t('Select {label}', {label: element.label})"
          .selected="selection.isSelected(element.id)"
          .selectDisabled="
            readOnly || interactionsDisabled || !selection.canSelect(element.id)
          "
          @craft-selection-change="onSelectionChange(element.id, $event)"
        >
          <div slot="thumbnail">
            <DynamicHtmlRenderer
              v-if="element.thumbHtml"
              :html="element.thumbHtml"
            />
            <craft-icon v-else name="asset" class="thumb-fallback"></craft-icon>
          </div>
          <craft-truncate class="thumb-label">
            <a v-if="element.url" :href="element.url" class="label-link">{{
              element.label
            }}</a>
            <a
              v-else-if="element.labelUrl"
              v-bind="element.labelLinkAttributes"
              :href="element.labelUrl"
              class="label-link"
              >{{ element.label }}</a
            >
            <template v-else>{{ element.label }}</template>
          </craft-truncate>
          <!--
            The chip keeps its suffix in the row along its top, away from
            the thumbnail, so reaching for an action never selects the
            element instead.
          -->
          <div
            v-if="$slots.actions || sortable"
            slot="suffix"
            class="thumb-actions"
          >
            <slot name="actions" :element="element" :index="thumbIdx"></slot>
            <span
              v-if="sortable"
              :ref="(el) => setHandleRef(el as HTMLElement, element.id)"
              class="thumb-handle drag-handle"
            >
              <craft-reorder-button
                .disabled="interactionsDisabled"
                :position="getRowPosition(thumbIdx)"
                orientation="horizontal"
                @craft-reorder="
                  (event: CustomEvent<{direction: 'up' | 'down'}>) =>
                    move(thumbIdx, event.detail.direction === 'up' ? -1 : 1)
                "
              ></craft-reorder-button>
            </span>
          </div>
        </craft-chip>
        <DragShadow
          v-if="overDropState(element.id)?.closestEdge === 'bottom'"
          :height="overDropState(element.id)?.draggingRect?.height"
        />
      </li>
    </ul>
  </template>
  <template v-else>
    <slot name="empty">
      <craft-empty :label="t('No results')" icon="empty-set"></craft-empty>
    </slot>
  </template>
</template>

<style scoped lang="scss">
  .checkbox-placeholder {
    inline-size: 1rem;
    block-size: 1rem;
  }

  .thumbsview-header {
    margin-block-end: var(--c-spacing-md);
    display: flex;
    align-items: center;
    // As tall as other header bars, such as a card's, plus its bottom border.
    min-height: calc(var(--c-size-header) + 1px);
    padding-block: 0 var(--c-spacing-sm);
    padding-inline: 0;
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }

  // Columns widen with the tiles' thumbnails, leaving room for the label.
  .thumbsview {
    --_column-width: 180px;
    display: grid;
    gap: var(--c-spacing-md);
    grid-template-columns: repeat(auto-fill, minmax(var(--_column-width), 1fr));

    &[data-thumb-size='sm'] {
      --_column-width: 120px;
    }

    &[data-thumb-size='md'] {
      --_column-width: 140px;
    }

    &[data-thumb-size='xl'] {
      --_column-width: 240px;
    }

    &[data-thumb-size='2xl'] {
      --_column-width: 300px;
    }
  }

  // The chip draws the tile; the item only holds it, so its focus ring
  // follows the tile's corners.
  .thumbsview > li {
    position: relative;
    display: flex;
    border-radius: var(--c-radius-lg);
  }

  .thumbsview[data-thumb-size='sm'] > li,
  .thumbsview[data-thumb-size='md'] > li {
    border-radius: var(--c-radius-md);
  }

  // Only the label links; the rest of the tile selects it.
  .thumb-label {
    font-weight: 500;
  }

  .thumb-label a {
    color: var(--c-text-link);
    text-decoration-line: var(--c-link-decoration);

    &:hover {
      text-decoration-line: var(--c-link-decoration-hover);
    }
  }

  .thumb-actions {
    display: flex;
    flex-direction: row;
    gap: var(--c-spacing-xs);
    align-items: center;
  }

  // An element without a thumbnail of its own, sized like a file icon's.
  .thumb-fallback {
    font-size: max(24px, var(--c-thumbnail-size) * 0.4);
    color: var(--c-text-quiet);
  }

  .thumb-placeholder {
    inline-size: var(--c-thumbnail-size);
    block-size: var(--c-thumbnail-size);
  }

  .thumb-label-placeholder {
    inline-size: 6rem;
    margin-block: 0.25rem;
  }

  // Dragging, but still over itself — dim rather than remove, so the grid
  // doesn't reflow under the cursor.
  .thumbsview > li.element--dragging {
    opacity: 0.4;
  }

  // Dragged away from itself: hide it but keep the footprint, so the grid
  // doesn't reshuffle around the gap.
  .thumbsview > li.element--hidden {
    visibility: hidden;
  }
</style>
