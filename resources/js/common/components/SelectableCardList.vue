<script setup lang="ts" generic="Id extends SelectableId = SelectableId">
  // Leaf modules, not the barrel — the barrel boots every `craft-*` element.
  import '@craftcms/ui/components/card/card';
  import '@craftcms/ui/components/checkbox/checkbox';
  import '@craftcms/ui/components/reorder-button/reorder-button';
  import {t} from '@craftcms/ui';
  import {computed, ref} from 'vue';
  import DragShadow from '@/common/components/DragShadow.vue';
  import {
    useReorderableItems,
    type DropState,
  } from '@/common/composables/useReorderableItems';
  import type {
    Selectable,
    SelectableId,
  } from '@/common/composables/useSelectable';

  /**
   * A list of cards that can be selected and reordered.
   *
   * The frame only — the card, its select checkbox, the drag handle and drop
   * shadow, and where actions go. What goes *in* a card is the consumer's:
   * the element index slots in server-rendered HTML, a Matrix field slots in a
   * live nested form. Everything list-shaped that isn't the frame (empty
   * states, select-all, add buttons, grid sizing) stays with the consumer too.
   */
  const props = withDefaults(
    defineProps<{
      /** Ids in display order. Selection ranges and reordering both walk this. */
      ids: Array<Id>;
      selection: Selectable<Id>;
      selectable?: boolean;
      sortable?: boolean;
      readOnly?: boolean;
      /**
       * Whether the list runs straight down. A wrapping grid reads left-to-right,
       * so its reorder button says "Move forward"/"Move backward" instead.
       */
      singleColumn?: boolean;
      tag?: string;
      itemTag?: string;
      listClass?: unknown;
      itemClass?: (id: Id, index: number) => unknown;
      itemAttrs?: (
        id: Id,
        index: number
      ) => Record<string, unknown> | undefined;
      cardAttrs?: (
        id: Id,
        index: number
      ) => Record<string, unknown> | undefined;
      /** The select checkbox's accessible label. */
      selectLabel?: string;
    }>(),
    {
      selectable: false,
      sortable: false,
      readOnly: false,
      singleColumn: false,
      tag: 'ul',
      itemTag: 'li',
      listClass: undefined,
      itemClass: undefined,
      itemAttrs: undefined,
      cardAttrs: undefined,
      selectLabel: undefined,
    }
  );

  const emit = defineEmits<{
    (event: 'reorder', startIndex: number, finishIndex: number): void;
    (event: 'item-click', id: Id, mouseEvent: MouseEvent): void;
    (
      event: 'item-keydown',
      id: Id,
      index: number,
      keyboardEvent: KeyboardEvent
    ): void;
  }>();

  const {setItemRef, setHandleRef, getDragState, getDropState, getRowPosition} =
    useReorderableItems({
      getItemIds: () => props.ids,
      onReorder: (startIndex, finishIndex) =>
        emit('reorder', startIndex, finishIndex),
      enabled: () => props.sortable,
    });

  const reorderOrientation = computed(() =>
    props.singleColumn ? 'vertical' : 'horizontal'
  );

  function overDropState(id: Id): Extract<DropState, {type: 'is-over'}> | null {
    const state = getDropState(id);

    return state.type === 'is-over' ? state : null;
  }

  function move(index: number, delta: number): void {
    const target = index + delta;

    if (target < 0 || target >= props.ids.length) {
      return;
    }

    emit('reorder', index, target);
  }

  /**
   * The checkbox's change event carries no modifier keys, so the shift state is
   * taken from the click that preceded it and used to extend the range.
   *
   * Captured on the list rather than the checkbox: the capture phase runs before
   * any handler on the way down, so the state is already right by the time the
   * checkbox reports — whichever order it chooses to fire in.
   */
  const pendingShiftKey = ref(false);

  function rememberShift(event: MouseEvent): void {
    pendingShiftKey.value = event.shiftKey;
  }

  /**
   * `craft-checkbox` dispatches `model-value-changed` from the host, not from an
   * inner `<input>`, so an `instanceof HTMLInputElement` test reads every change
   * as unchecked. It also re-fires on programmatic `.checked` updates, which
   * turned each selection into an immediate deselection.
   */
  function checkboxValue(event: Event): boolean {
    return Boolean((event.target as {checked?: boolean} | null)?.checked);
  }

  function onCheckboxChange(id: Id, event: Event): void {
    props.selection.setChecked(id, checkboxValue(event), {
      shiftKey: pendingShiftKey.value,
    });
  }

  /**
   * Space and Enter select; the arrows walk the list, extending the selection
   * when shifted.
   *
   * The consumer hears the key first and can take it — `preventDefault()` on a
   * card that navigates somewhere of its own, say. Only a key pressed on the
   * item itself counts: a card can hold a whole form, and Space in a text field
   * belongs to the field.
   */
  function onItemKeydown(id: Id, index: number, event: KeyboardEvent): void {
    emit('item-keydown', id, index, event);

    if (
      !props.selectable ||
      event.defaultPrevented ||
      event.target !== event.currentTarget ||
      !(event.currentTarget instanceof HTMLElement)
    ) {
      return;
    }

    const target = event.currentTarget;

    switch (event.key) {
      case ' ':
      case 'Enter':
        event.preventDefault();
        props.selection.toggle(id);

        return;

      case 'ArrowRight':
      case 'ArrowDown':
        event.preventDefault();
        stepTo(Math.min(index + 1, props.ids.length - 1), target, event);

        return;

      case 'ArrowLeft':
      case 'ArrowUp':
        event.preventDefault();
        stepTo(Math.max(index - 1, 0), target, event);
    }
  }

  function stepTo(
    index: number,
    from: HTMLElement,
    event: KeyboardEvent
  ): void {
    const id = props.ids[index];

    if (event.shiftKey && id !== undefined) {
      props.selection.extendTo(id);
    }

    from.parentElement
      ?.querySelectorAll<HTMLElement>(':scope > [tabindex]')
      ?.[index]?.focus();
  }

  defineExpose({setItemRef, setHandleRef, getDragState, getRowPosition});
</script>

<template>
  <component :is="tag" :class="listClass" @click.capture="rememberShift">
    <component
      :is="itemTag"
      v-for="(id, index) in ids"
      :key="id"
      :ref="(el: unknown) => setItemRef(el as HTMLElement, id)"
      v-bind="itemAttrs?.(id, index)"
      :class="[
        itemClass?.(id, index),
        {
          sel: selection.isSelected(id),
          'is-dragging': getDragState(id).type === 'is-dragging',
          'is-dragging-away':
            getDragState(id).type === 'is-dragging-and-left-self',
        },
      ]"
      :tabindex="selectable ? 0 : undefined"
      @click="emit('item-click', id, $event)"
      @keydown="onItemKeydown(id, index, $event)"
    >
      <DragShadow
        v-if="overDropState(id)?.closestEdge === 'top'"
        :height="overDropState(id)?.draggingRect?.height"
      />

      <craft-card
        v-bind="cardAttrs?.(id, index)"
        :active="selection.isSelected(id)"
      >
        <!-- Slot content carries its own `slot="thumbnail"`, so a consumer can
             render nothing per item rather than an empty region. -->
        <slot name="thumbnail" :id="id" :index="index" />

        <div
          slot="header"
          class="flex gap-2 items-center justify-between w-full"
        >
          <div class="flex gap-2 items-center">
            <craft-checkbox
              v-if="selectable"
              class="checkbox"
              label-sr-only
              .checked="selection.isSelected(id)"
              .disabled="readOnly || !selection.canSelect(id)"
              @model-value-changed="onCheckboxChange(id, $event)"
            >
              <label slot="label">{{ selectLabel ?? t('Select') }}</label>
            </craft-checkbox>
            <slot name="label" :id="id" :index="index" />
          </div>

          <div class="flex gap-1 items-center">
            <slot name="actions" :id="id" :index="index" />
            <span
              v-if="sortable"
              :ref="(el: unknown) => setHandleRef(el as HTMLElement, id)"
              class="drag-handle"
            >
              <craft-reorder-button
                class="move-btn"
                :disabled="ids.length < 2"
                :position="getRowPosition(index)"
                :orientation="reorderOrientation"
                @reorder="
                  (event: CustomEvent<{direction: 'up' | 'down'}>) => {
                    event.stopPropagation();
                    move(index, event.detail.direction === 'up' ? -1 : 1);
                  }
                "
              />
            </span>
          </div>
        </div>

        <slot :id="id" :index="index" />

        <slot name="footer" :id="id" :index="index" />
      </craft-card>

      <DragShadow
        v-if="overDropState(id)?.closestEdge === 'bottom'"
        :height="overDropState(id)?.draggingRect?.height"
      />
    </component>
  </component>
</template>
