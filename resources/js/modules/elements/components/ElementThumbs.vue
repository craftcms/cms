<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, ref} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import Empty from '@/common/components/Empty.vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import type {Selectable} from '@/common/composables/useSelectable';
  import {useFolderNavigation} from '@/modules/elements/composables/useFolderNavigation';

  interface ThumbElement {
    id: string | number;
    isFolder?: boolean;
    folderUrl?: string;
    folderId?: string | number;
    canMoveTo?: boolean;
    // The server sends the element's edit URL, which is null when it has none.
    url?: string | null;
    thumbHtml?: string;
    label?: string;
  }

  const props = withDefaults(
    defineProps<{
      selection: Selectable<any>;
      data?: Array<ThumbElement>;
      selectable?: boolean;
      readOnly?: boolean;
      loading?: boolean;
    }>(),
    {data: () => [], selectable: false, loading: false}
  );

  const page = usePage<{readOnly: boolean}>();
  const readOnly = computed(() => props.readOnly ?? page.props.readOnly);

  // Selection is handed in rather than derived from a table, so this body works
  // for anything with an ordered list of ids.

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
  <div class="grid place-items-center min-h-50" v-if="loading">
    <craft-spinner></craft-spinner>
  </div>
  <template v-else-if="data.length > 0">
    <div class="thumbsview-header" v-if="selectable">
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
          sel: selection.isSelected(element.id),
        }"
      >
        <craft-checkbox
          v-if="selectable"
          class="thumb-check"
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
