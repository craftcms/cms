<script setup lang="ts">
  import {attrs, t, type ServerAttributes} from '@craftcms/ui';
  import {computed, normalizeClass} from 'vue';
  import SelectableCardList from '@/common/components/SelectableCardList.vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import PlaceholderShape from '@/common/components/PlaceholderShape.vue';
  import type {CardThumbSettings} from '@/modules/elements/types/view-state';
  import type {Selectable} from '@/common/composables/useSelectable';
  import {isInteractiveClick} from '@/common/utils/dom';
  import {
    isInteractiveItemEvent,
    type ElementIndexItemBehavior,
  } from '@/modules/elements/types/item-behavior';

  interface CardElement {
    id: string | number;
    label?: string;
    cardAttributes?: ServerAttributes;
    cardHeaderHtml?: string;
    cardActionsHtml?: string;
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
      /**
       * Draws the select-all header for cards that can't be selected, with a
       * disabled checkbox that assistive tech skips, so they line up with
       * selectable cards beside them.
       */
      placeholderSelectAll?: boolean;
      singleColumn?: boolean;
      sortable?: boolean;
      interactionsDisabled?: boolean;
      readOnly?: boolean;
      loading?: boolean;
      renderServerActions?: boolean;
      itemBehavior?: ElementIndexItemBehavior<CardElement>;
      /**
       * How many placeholder cards to draw in place of the cards, such as while
       * they load. Takes the place of the loading spinner.
       */
      placeholders?: number;
      /**
       * How the placeholder cards show a thumbnail, as the cards' field layout
       * has it, or null for none.
       */
      placeholderThumb?: CardThumbSettings | null;
    }>(),
    {
      data: () => [],
      selectable: false,
      selectAll: true,
      singleColumn: false,
      sortable: false,
      readOnly: false,
      loading: false,
      renderServerActions: true,
      placeholders: 0,
      placeholderThumb: null,
    }
  );

  // Selection is handed in rather than derived from a table, so this body works
  // for anything with an ordered list of ids — the element index, a relation
  // field, or a third-party list.

  const emit = defineEmits<{
    (event: 'reorder', startIndex: number, finishIndex: number): void;
    (event: 'edit', element: CardElement): void;
  }>();

  const ids = computed(() => props.data.map((element) => element.id));

  /** Classes the list item carries instead of the card, such as for quick-edit's `.element` lookup. */
  const ITEM_CLASSES = new Set(['element', 'error']);

  function serverClasses(element: CardElement | undefined): string[] {
    return normalizeClass(attrs(element?.cardAttributes).class)
      .split(/\s+/)
      .filter(Boolean);
  }

  /** Per-card attributes the shared frame passes straight through. */
  function itemClass(id: string | number): unknown {
    const element = props.data.find((candidate) => candidate.id === id);

    return {element: true, error: serverClasses(element).includes('error')};
  }

  function itemAttrs(id: string | number): Record<string, unknown> | undefined {
    const element = props.data.find((candidate) => candidate.id === id);

    return element
      ? {'data-id': String(id), ...props.itemBehavior?.attrs?.(element)}
      : undefined;
  }

  function cardAttrs(id: string | number): Record<string, unknown> | undefined {
    const element = props.data.find((candidate) => candidate.id === id);

    if (!element) {
      return undefined;
    }

    return {
      ...attrs(element.cardAttributes, {exclude: ['class']}),
      class: serverClasses(element).filter((name) => !ITEM_CLASSES.has(name)),
      'thumb-alignment': element.thumbAlignment ?? undefined,
    };
  }

  function cardSelectLabel(id: string | number): string | undefined {
    const element = props.data.find((candidate) => candidate.id === id);

    return element?.label
      ? t('Select {label}', {label: element.label})
      : undefined;
  }

  function onCardClick(id: string | number, event: MouseEvent) {
    const element = props.data.find((candidate) => candidate.id === id);

    if (element && props.itemBehavior?.onClick?.(element, event)) {
      return;
    }

    if (props.selectable && !props.readOnly && !props.interactionsDisabled) {
      props.selection.handleClick(id, event);
    }
  }

  /**
   * A double-click opens the element, except on the card's own controls — the
   * checkbox, an action menu, the reorder button, a link.
   */
  function onCardDblclick(id: string | number, event: MouseEvent) {
    const element = props.data.find((candidate) => candidate.id === id);

    if (!element) {
      return;
    }

    if (props.itemBehavior?.onDblClick?.(element, event)) {
      event.preventDefault();

      return;
    }

    if (!isInteractiveClick(event, event.currentTarget)) {
      emit('edit', element);
    }
  }

  function onCardKeydown(
    id: number | string,
    _index: number,
    event: KeyboardEvent
  ) {
    if (!props.selectable) {
      return;
    }

    if (isInteractiveItemEvent(event)) {
      return;
    }

    const element = props.data.find((el) => el.id === id);

    if (element && props.itemBehavior?.onKeydown?.(element, event)) {
      event.preventDefault();
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
      class="card-grid-header"
      v-if="selectable && selectAll"
      aria-hidden="true"
    >
      <PlaceholderShape class="checkbox-placeholder" />
    </div>
    <ul
      :class="{'card-grid': true, 'card-grid--single': singleColumn}"
      aria-hidden="true"
    >
      <li v-for="index in placeholders" :key="index">
        <PlaceholderShape
          v-if="
            placeholderThumb?.style === 'cover' ||
            placeholderThumb?.style === 'auto'
          "
          class="card-cover-placeholder"
        />
        <craft-card v-else :thumb-alignment="placeholderThumb?.alignment">
          <PlaceholderShape
            v-if="placeholderThumb"
            slot="thumbnail"
            class="card-thumb-placeholder"
          />
          <PlaceholderShape slot="header" class="card-label-placeholder" />
          <PlaceholderShape />
          <PlaceholderShape class="card-text-placeholder" />
        </craft-card>
      </li>
    </ul>
  </template>
  <div class="grid flex-1 place-items-center min-h-50" v-else-if="loading">
    <craft-spinner></craft-spinner>
  </div>
  <template v-else-if="data.length > 0">
    <div class="card-grid-header" v-if="selectable && selectAll">
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
    <div
      v-else-if="placeholderSelectAll"
      class="card-grid-header"
      aria-hidden="true"
      inert
    >
      <craft-checkbox label-sr-only disabled>
        <label slot="label">{{ t('Select all') }}</label>
      </craft-checkbox>
    </div>
    <div v-if="$slots['header-after']" class="card-grid-header-after">
      <slot name="header-after" />
    </div>

    <SelectableCardList
      tag="ul"
      :ids="ids"
      :selection="selection"
      :selectable="selectable"
      :sortable="sortable"
      :interactions-disabled="interactionsDisabled"
      :read-only="readOnly"
      :single-column="singleColumn"
      :list-class="{'card-grid': true, 'card-grid--single': singleColumn}"
      :item-class="itemClass"
      :item-attrs="itemAttrs"
      :card-attrs="cardAttrs"
      :select-label="cardSelectLabel"
      :aria-busy="loading ? 'true' : undefined"
      @reorder="(from, to) => emit('reorder', from, to)"
      @item-click="onCardClick"
      @item-dblclick="onCardDblclick"
      @item-keydown="onCardKeydown"
    >
      <template #thumbnail="{index}">
        <div
          v-if="data[index]?.cardThumbHtml"
          slot="thumbnail"
          class="card-thumb"
        >
          <DynamicHtmlRenderer :html="data[index]!.cardThumbHtml!" />
        </div>
      </template>

      <template #label="{index}">
        <DynamicHtmlRenderer :html="data[index]?.cardHeaderHtml ?? ''" />
      </template>

      <template #actions="{index}">
        <slot name="actions" :element="data[index]" :index="index"></slot>
        <DynamicHtmlRenderer
          v-if="renderServerActions"
          :html="data[index]?.cardActionsHtml ?? ''"
        />
      </template>

      <template #default="{index}">
        <DynamicHtmlRenderer :html="data[index]?.cardContentHtml ?? ''" />
      </template>

      <template #footer="{index}">
        <DynamicHtmlRenderer
          :html="data[index]?.cardFooterHtml ?? ''"
          slot="footer"
        />
      </template>
    </SelectableCardList>
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

  .card-thumb-placeholder {
    inline-size: var(--c-thumbnail-size);
    block-size: var(--c-thumbnail-size);
  }

  .card-cover-placeholder {
    aspect-ratio: 4 / 3;
    block-size: auto;
    border-radius: var(--c-card-radius, var(--c-radius-md));
  }

  .card-label-placeholder {
    inline-size: 50%;
  }

  .card-text-placeholder {
    inline-size: 70%;
    margin-block-start: var(--c-spacing-sm);
  }

  .card-grid-header {
    margin-block-end: var(--c-spacing-md);
    display: flex;
    align-items: center;
    // As tall as other header bars, such as a card's, plus its bottom border.
    min-height: calc(var(--c-size-header) + 1px);
    padding-block: 0 var(--c-spacing-sm);
    padding-inline: 0;
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }

  .card-grid {
    display: grid;
    gap: var(--c-spacing-md);
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

  // Dragging, but still over itself — dim rather than remove, so the grid
  // doesn't reflow under the cursor.
  .card-grid > li.is-dragging {
    opacity: 0.4;
  }

  // Dragged away from itself: collapse but keep the footprint, so the grid
  // doesn't reshuffle around the gap.
  .card-grid > li.is-dragging-away {
    visibility: hidden;
  }

  .card-grid :deep(> li > craft-card) {
    height: 100%;
  }
</style>
