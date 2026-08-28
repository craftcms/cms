<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, ref} from 'vue';
  import type {Table} from '@tanstack/vue-table';
  import {usePage} from '@inertiajs/vue3';
  import Empty from '@/common/components/Empty.vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import {useElementIndexSelection} from '@/modules/elements/composables/useElementIndexSelection';
  import {useFolderNavigation} from '@/modules/elements/composables/useFolderNavigation';

  interface ThumbElement {
    id: string | number;
    isFolder?: boolean;
    folderUrl?: string;
    folderId?: string | number;
    canMoveTo?: boolean;
    url?: string;
    thumbHtml?: string;
    label?: string;
  }

  const props = withDefaults(
    defineProps<{
      table: Table<any>;
      data?: Array<ThumbElement>;
      selectable?: boolean;
      readOnly?: boolean;
      loading?: boolean;
    }>(),
    {data: () => [], selectable: false, loading: false}
  );

  const page = usePage<{readOnly: boolean}>();
  const readOnly = computed(() => props.readOnly ?? page.props.readOnly);

  const {onToggleAllSelected, selectRow, toggleRow, extendSelectionTo} =
    useElementIndexSelection(() => props.table, {
      selectable: () => props.selectable,
      readOnly,
      actions: () => [],
    });

  function rowFor(id: number | string) {
    return props.table.getRow(String(id));
  }

  const pendingShiftKey = ref(false);
  function rememberShift(event: MouseEvent) {
    pendingShiftKey.value = event.shiftKey;
  }

  const {navigateToFolder, isFolderRow, rowMoveAttrs} = useFolderNavigation();

  // Folder tiles (asset index) navigate into the folder on click, except when
  // the click lands on an interactive control (the select checkbox, a link, …).
  function onTileClick(element: ThumbElement, event: MouseEvent) {
    if (!isFolderRow(element)) return;
    if (
      event.target instanceof HTMLElement &&
      event.target.closest('a[href], button, input, craft-checkbox')
    ) {
      return;
    }
    navigateToFolder(element.folderUrl);
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
        if (element?.url) {
          window.location.assign(element.url);
          break;
        }
        toggleRow(rowFor(id));
        break;
      }
      case 'ArrowRight':
      case 'ArrowDown': {
        event.preventDefault();
        const nextIndex = Math.min(index + 1, last);
        const nextEl = props.data[nextIndex];
        if (event.shiftKey && nextEl) extendSelectionTo(rowFor(nextEl.id));
        focusTileByIndex(nextIndex, target);
        break;
      }
      case 'ArrowLeft':
      case 'ArrowUp': {
        event.preventDefault();
        const prevIndex = Math.max(index - 1, 0);
        const prevEl = props.data[prevIndex];
        if (event.shiftKey && prevEl) extendSelectionTo(rowFor(prevEl.id));
        focusTileByIndex(prevIndex, target);
        break;
      }
    }
  }

  function checkboxValue(event: Event): boolean {
    return event.target instanceof HTMLInputElement && event.target.checked;
  }
</script>

<template>
  <div class="cp:grid cp:place-items-center cp:min-h-50" v-if="loading">
    <craft-spinner></craft-spinner>
  </div>
  <template v-else-if="data.length > 0">
    <div class="thumbsview-header" v-if="selectable">
      <craft-checkbox
        label-sr-only
        .checked="table.getIsAllRowsSelected()"
        .indeterminate="table.getIsSomeRowsSelected()"
        .disabled="readOnly"
        @model-value-changed="onToggleAllSelected(checkboxValue($event))"
      >
        <label slot="label">{{ t('Select all') }}</label>
      </craft-checkbox>
    </div>

    <ul class="thumbsview">
      <li
        v-for="(element, thumbIdx) in data"
        :key="element.id"
        v-bind="rowMoveAttrs(element)"
        :tabindex="selectable ? 0 : undefined"
        @keydown="onTileKeydown(element.id, thumbIdx, $event)"
        @click="onTileClick(element, $event)"
        :class="{
          element: true,
          'element--folder': isFolderRow(element),
          sel: rowFor(element.id)?.getIsSelected(),
        }"
      >
        <craft-checkbox
          v-if="selectable"
          class="thumb-check"
          label-sr-only
          .checked="rowFor(element.id)?.getIsSelected()"
          .disabled="readOnly || !rowFor(element.id)?.getCanSelect()"
          @click="rememberShift($event)"
          @model-value-changed="
            selectRow(rowFor(element.id), {
              checked: checkboxValue($event),
              shiftKey: pendingShiftKey,
            })
          "
        >
          <label slot="label">{{ t('Select') }}</label>
        </craft-checkbox>

        <component
          :is="element.url ? 'a' : 'div'"
          :href="element.url || undefined"
          class="thumb-tile"
        >
          <span class="thumb-img">
            <DynamicHtmlRenderer
              v-if="element.thumbHtml"
              :html="element.thumbHtml"
            />
            <craft-icon v-else name="asset" class="thumb-fallback"></craft-icon>
          </span>
          <craft-truncate class="thumb-label">{{
            element.label
          }}</craft-truncate>
        </component>
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
  .thumbsview-header {
    padding: var(--c-spacing-md);
    background-color: var(--c-color-neutral-fill-quiet);
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }

  .thumbsview {
    display: grid;
    gap: var(--c-spacing-sm);
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    padding: var(--c-spacing-md);
  }

  .thumbsview > li {
    position: relative;
    border-radius: var(--c-radius-lg);
    border: 1px solid var(--c-color-neutral-border-quiet);
  }

  .thumbsview > li:has(a.thumb-tile) {
    &:hover {
      background-color: var(--c-color-neutral-fill-quiet);
    }
  }

  .thumbsview > li.element--folder {
    cursor: pointer;
  }

  .thumb-check {
    position: absolute;
    inset-block-start: var(--c-spacing-sm);
    inset-inline-start: var(--c-spacing-sm);
    z-index: 1;
  }

  .thumb-tile {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    gap: var(--c-spacing-sm);
    padding: var(--c-spacing-md);
    border-radius: var(--c-radius-lg);
    text-decoration: none;
    color: inherit;
    min-height: 100%;
  }

  .thumbsview > li.sel .thumb-tile {
    background-color: var(--c-color-accent-fill-quiet);
    border-color: var(--c-color-accent-border-quiet);
  }

  .thumb-img {
    display: grid;
    place-items: center;
    width: 100%;
    height: 128px;
  }

  // craft-thumbnail defaults its own size via :host, so the override has to land
  // on the element itself (an inherited value from the parent loses to :host).
  // 120px matches the cards view (and Craft 5's thumbnail size).
  .thumb-img :deep(craft-thumbnail) {
    --c-thumbnail-size: 120px;
  }

  // Non-image thumbs (folder/file-kind SVGs) render at a fixed small size; scale
  // them up to fill the tile.
  .thumb-img :deep(.thumb) {
    width: 72px;
    height: 72px;
  }

  .thumb-img :deep(.thumb svg) {
    width: 100%;
    height: 100%;
  }

  .thumb-fallback {
    font-size: 48px;
    color: var(--c-text-quiet);
  }

  .thumb-label {
    // craft-truncate handles the ellipsis + overflow tooltip internally; cap the
    // width so it truncates within the tile.
    max-width: 100%;
    font-weight: 500;
    text-align: center;
    height: 1lh;
  }
</style>
