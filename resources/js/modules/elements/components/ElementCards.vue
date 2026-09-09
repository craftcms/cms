<script setup lang="ts">
  import {attrs, t} from '@craftcms/ui';
  import {computed, ref} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import Empty from '@/common/components/Empty.vue';
  import DragShadow from '@/common/components/DragShadow.vue';
  import {
    useReorderableItems,
    type DropState,
  } from '@/common/composables/useReorderableItems';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import type {Selectable} from '@/common/composables/useSelectable';
  import {useFolderNavigation} from '@/modules/elements/composables/useFolderNavigation';

  interface CardElement {
    id: string | number;
    isFolder?: boolean;
    folderUrl?: string;
    folderId?: string | number;
    canMoveTo?: boolean;
    cardAttributes?: Record<
      string,
      string | number | boolean | null | undefined
    >;
    cardHeaderHtml?: string;
    cardThumbHtml?: string;
    thumbAlignment?: string;
    cardContentHtml?: string;
    cardFooterHtml?: string;
  }

  const props = withDefaults(
    defineProps<{
      selection: Selectable<any>;
      data?: Array<CardElement>;
      selectable?: boolean;
      selectAll?: boolean;
      singleColumn?: boolean;
      sortable?: boolean;
      readOnly?: boolean;
      loading?: boolean;
    }>(),
    {
      data: () => [],
      selectable: false,
      selectAll: true,
      singleColumn: false,
      sortable: false,
      loading: false,
    }
  );

  const page = usePage<{readOnly: boolean}>();
  const readOnly = computed(() => props.readOnly ?? page.props.readOnly);

  // Selection is handed in rather than derived from a table, so this body works
  // for anything with an ordered list of ids — the element index, a relation
  // field, or a third-party list.

  const pendingShiftKey = ref(false);
  function rememberShift(event: MouseEvent) {
    pendingShiftKey.value = event.shiftKey;
  }

  const emit = defineEmits<{
    (event: 'reorder', startIndex: number, finishIndex: number): void;
  }>();

  const ids = computed(() => props.data.map((element) => element.id));

  const {setItemRef, setHandleRef, getDragState, getDropState, getRowPosition} =
    useReorderableItems({
      getItemIds: () => ids.value,
      onReorder: (startIndex, finishIndex) =>
        emit('reorder', startIndex, finishIndex),
      enabled: () => props.sortable,
    });

  /**
   * A grid wraps, so its cards run in reading order rather than straight down —
   * the reorder button says "Move forward"/"Move backward" there, and
   * "Move up"/"Move down" in the single-column layout.
   */
  const reorderOrientation = computed(() =>
    props.singleColumn ? 'vertical' : 'horizontal'
  );

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

  const {navigateToFolder, isFolderRow, rowMoveAttrs} = useFolderNavigation();

  // Folder cards (asset index) navigate into the folder on click, except when
  // the click lands on an interactive control (the select checkbox, a link, …).
  // Other cards fall through to the normal click-to-select behavior.
  function onCardClick(element: CardElement, event: MouseEvent) {
    if (!isFolderRow(element)) {
      props.selection.handleClick(element.id, event);
      return;
    }

    if (
      event.target instanceof HTMLElement &&
      event.target.closest('a[href], button, input, craft-checkbox')
    ) {
      return;
    }
    navigateToFolder(element.folderUrl);
  }

  function focusCardByIndex(index: number, el: HTMLElement) {
    const list = el.closest('ul.card-grid');
    const items = list?.querySelectorAll<HTMLElement>(':scope > li[tabindex]');
    items?.[index]?.focus();
  }

  function onCardKeydown(
    id: number | string,
    index: number,
    event: KeyboardEvent
  ) {
    if (!props.selectable) return;
    if (!(event.currentTarget instanceof HTMLElement)) return;
    const target = event.currentTarget;
    const last = props.data.length - 1;
    switch (event.key) {
      case ' ':
      case 'Enter': {
        event.preventDefault();
        const element = props.data.find((el) => el.id === id);
        if (element && isFolderRow(element)) {
          navigateToFolder(element.folderUrl);
          break;
        }
        props.selection.toggle(id);
        break;
      }
      case 'ArrowRight':
      case 'ArrowDown': {
        event.preventDefault();
        const nextIndex = Math.min(index + 1, last);
        const nextEl = props.data[nextIndex];
        if (event.shiftKey && nextEl) props.selection.extendTo(nextEl.id);
        focusCardByIndex(nextIndex, target);
        break;
      }
      case 'ArrowLeft':
      case 'ArrowUp': {
        event.preventDefault();
        const prevIndex = Math.max(index - 1, 0);
        const prevEl = props.data[prevIndex];
        if (event.shiftKey && prevEl) props.selection.extendTo(prevEl.id);
        focusCardByIndex(prevIndex, target);
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
  <div class="grid place-items-center min-h-50" v-if="loading">
    <craft-spinner></craft-spinner>
  </div>
  <template v-else-if="data.length > 0">
    <div class="card-grid-header" v-if="selectable && selectAll">
      <craft-checkbox
        label-sr-only
        .checked="selection.allSelected.value"
        .indeterminate="selection.someSelected.value"
        .disabled="readOnly"
        @model-value-changed="selection.toggleAll(checkboxValue($event))"
      >
        <label slot="label">{{ t('Select all') }}</label>
      </craft-checkbox>
    </div>

    <ul class="card-grid" :class="{'card-grid--single': singleColumn}">
      <li
        v-for="(element, cardIdx) in data"
        :key="element.id"
        :ref="(el) => setItemRef(el as HTMLElement, element.id)"
        v-bind="rowMoveAttrs(element)"
        :tabindex="selectable ? 0 : undefined"
        @click="onCardClick(element, $event)"
        @keydown="onCardKeydown(element.id, cardIdx, $event)"
        :class="{
          element: true,
          'element--folder': isFolderRow(element),
          sel: selection.isSelected(element.id),
          'element--dragging': getDragState(element.id).type === 'is-dragging',
          'element--hidden':
            getDragState(element.id).type === 'is-dragging-and-left-self',
        }"
      >
        <DragShadow
          v-if="overDropState(element.id)?.closestEdge === 'top'"
          :height="overDropState(element.id)?.draggingRect?.height"
        />

        <craft-card
          v-bind="attrs(element.cardAttributes, {exclude: ['class']})"
          :active="selection.isSelected(element.id)"
          :thumb-alignment="element.thumbAlignment ?? undefined"
        >
          <div v-if="element.cardThumbHtml" slot="thumbnail">
            <DynamicHtmlRenderer :html="element.cardThumbHtml" />
          </div>

          <div
            slot="header"
            class="flex gap-2 items-center justify-between w-full"
          >
            <div class="flex gap-2 items-center">
              <craft-checkbox
                v-if="selectable"
                label-sr-only
                .checked="selection.isSelected(element.id)"
                .disabled="readOnly || !selection.canSelect(element.id)"
                @click="rememberShift($event)"
                @model-value-changed="
                  selection.setChecked(element.id, checkboxValue($event), {
                    shiftKey: pendingShiftKey,
                  })
                "
              >
                <label slot="label">{{ t('Select') }}</label>
              </craft-checkbox>
              <DynamicHtmlRenderer :html="element.cardHeaderHtml ?? ''" />
            </div>

            <div class="flex gap-1 items-center">
              <slot name="actions" :element="element" :index="cardIdx"></slot>
              <span
                v-if="sortable"
                :ref="(el) => setHandleRef(el as HTMLElement, element.id)"
                class="drag-handle"
              >
                <craft-reorder-button
                  :position="getRowPosition(cardIdx)"
                  :orientation="reorderOrientation"
                  @reorder="
                    (event: CustomEvent<{direction: 'up' | 'down'}>) =>
                      move(cardIdx, event.detail.direction === 'up' ? -1 : 1)
                  "
                ></craft-reorder-button>
              </span>
            </div>
          </div>
          <DynamicHtmlRenderer :html="element.cardContentHtml ?? ''" />
          <DynamicHtmlRenderer
            :html="element.cardFooterHtml ?? ''"
            slot="footer"
          />
        </craft-card>

        <DragShadow
          v-if="overDropState(element.id)?.closestEdge === 'bottom'"
          :height="overDropState(element.id)?.draggingRect?.height"
        />
      </li>
    </ul>
  </template>
  <template v-else>
    <slot name="empty">
      <Empty :label="t('No results')" icon="empty-set" />
    </slot>
  </template>
</template>

<style scoped lang="scss">
  .card-grid-header {
    padding: var(--c-spacing-md);
    background-color: var(--c-color-neutral-fill-quiet);
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }

  .card-grid {
    display: grid;
    align-items: stretch;
    grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
  }

  // One card per row, however wide the container gets.
  .card-grid--single {
    grid-template-columns: 1fr;
  }

  .card-grid > li {
    position: relative;
  }

  .card-grid > li.element--folder {
    cursor: pointer;
  }

  // Dragging, but still over itself — dim rather than remove, so the grid
  // doesn't reflow under the cursor.
  .card-grid > li.element--dragging {
    opacity: 0.4;
  }

  // Dragged away from itself: collapse but keep the footprint, so the grid
  // doesn't reshuffle around the gap.
  .card-grid > li.element--hidden {
    visibility: hidden;
  }

  // craft-thumbnail defaults its own size via :host, so the card thumbnail
  // renders at the tiny default instead of filling its 120px column. Match the
  // thumbnail view (and Craft 5's 120px card thumb column).
  .card-grid :deep(craft-thumbnail) {
    --c-thumbnail-size: 120px;
  }

  // Non-image thumbs (folder / file-kind SVGs) render at a fixed small size;
  // scale them up to match.
  .card-grid :deep(.card-main .thumb) {
    width: 72px;
    height: 72px;
  }

  .card-grid :deep(.card-main .thumb svg) {
    width: 100%;
    height: 100%;
  }
</style>
