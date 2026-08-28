<script setup lang="ts">
  import {attrs, t} from '@craftcms/ui';
  import {computed, ref} from 'vue';
  import type {Table} from '@tanstack/vue-table';
  import {usePage} from '@inertiajs/vue3';
  import Empty from '@/common/components/Empty.vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import {useElementIndexSelection} from '@/modules/elements/composables/useElementIndexSelection';
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
    cardContentHtml?: string;
    cardFooterHtml?: string;
  }

  const props = withDefaults(
    defineProps<{
      table: Table<any>;
      data?: Array<CardElement>;
      selectable?: boolean;
      readOnly?: boolean;
      loading?: boolean;
    }>(),
    {data: () => [], selectable: false, loading: false}
  );

  const page = usePage<{readOnly: boolean}>();
  const readOnly = computed(() => props.readOnly ?? page.props.readOnly);

  const {
    onToggleAllSelected,
    selectRow,
    selectRowFromEvent,
    toggleRow,
    extendSelectionTo,
  } = useElementIndexSelection(() => props.table, {
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

  // Folder cards (asset index) navigate into the folder on click, except when
  // the click lands on an interactive control (the select checkbox, a link, …).
  // Other cards fall through to the normal click-to-select behavior.
  function onCardClick(element: CardElement, event: MouseEvent) {
    if (!isFolderRow(element)) {
      selectRowFromEvent(rowFor(element.id), event);
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
        toggleRow(rowFor(id));
        break;
      }
      case 'ArrowRight':
      case 'ArrowDown': {
        event.preventDefault();
        const nextIndex = Math.min(index + 1, last);
        const nextEl = props.data[nextIndex];
        if (event.shiftKey && nextEl) extendSelectionTo(rowFor(nextEl.id));
        focusCardByIndex(nextIndex, target);
        break;
      }
      case 'ArrowLeft':
      case 'ArrowUp': {
        event.preventDefault();
        const prevIndex = Math.max(index - 1, 0);
        const prevEl = props.data[prevIndex];
        if (event.shiftKey && prevEl) extendSelectionTo(rowFor(prevEl.id));
        focusCardByIndex(prevIndex, target);
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
    <div class="card-grid-header" v-if="selectable">
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

    <ul class="card-grid">
      <li
        v-for="(element, cardIdx) in data"
        :key="element.id"
        v-bind="rowMoveAttrs(element)"
        :tabindex="selectable ? 0 : undefined"
        @click="onCardClick(element, $event)"
        @keydown="onCardKeydown(element.id, cardIdx, $event)"
        :class="{
          element: true,
          'element--folder': isFolderRow(element),
          sel: rowFor(element.id)?.getIsSelected(),
        }"
      >
        <craft-card
          v-bind="attrs(element.cardAttributes, {exclude: ['class']})"
          :active="rowFor(element.id)?.getIsSelected()"
        >
          <div slot="header">
            <div class="cp:flex cp:gap-2 cp:items-center">
              <craft-checkbox
                v-if="selectable"
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
              <DynamicHtmlRenderer :html="element.cardHeaderHtml ?? ''" />
            </div>
          </div>
          <DynamicHtmlRenderer :html="element.cardContentHtml ?? ''" />
          <DynamicHtmlRenderer
            :html="element.cardFooterHtml ?? ''"
            slot="footer"
          />
        </craft-card>
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
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    padding: var(--c-spacing-md);
  }

  .card-grid > li {
    position: relative;
  }

  .card-grid > li.element--folder {
    cursor: pointer;
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
