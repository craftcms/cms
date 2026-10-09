<script setup lang="ts">
  import {computed, onMounted, provide, ref, useTemplateRef, watch} from 'vue';
  import {onLongPress} from '@vueuse/core';
  import {ButtonVariant, t} from '@craftcms/ui';
  import PaginationControls from '@/common/components/PaginationControls.vue';
  import {TableSpacing} from '@/common/types';
  import ElementTable from '@/modules/elements/index/components/ElementTable.vue';
  import ElementCards from '@/modules/elements/components/ElementCards.vue';
  import ElementThumbs from '@/modules/elements/components/ElementThumbs.vue';
  import ElementIndexToolbar from '@/modules/elements/index/components/ElementIndexToolbar.vue';
  import type {ElementIndexView} from '@/modules/elements/index/types/model';
  import type {StructureMove} from '@/modules/elements/index/composables/useElementIndexStructure';
  import type {ElementIndexItemBehavior} from '@/modules/elements/types/item-behavior';
  import ElementIndexExportMenu from '@/modules/elements/index/components/ElementIndexExportMenu.vue';
  import {useInlineEditing} from '@/modules/elements/index/composables/useInlineEditing';
  import {elementIndexContextKey} from '@/modules/elements/index/index-context';

  interface ElementIndexQuickEdit {
    longPress?: boolean;
    onDblClick(event: MouseEvent): void;
    deferChipClick?(event: MouseEvent): boolean;
    onPrimaryLink?(event: MouseEvent): void;
    suppressPrimaryLinkVisit?(event: MouseEvent): void;
  }

  const props = withDefaults(
    defineProps<{
      view: ElementIndexView;
      contained?: boolean;
      footerActive?: boolean;
      itemBehavior?: ElementIndexItemBehavior;
      showSites?: boolean;
      enableAdjustPageSize?: boolean;
      withBottomBorder?: boolean;
      toolbarAsForm?: boolean;
      quickEdit?: ElementIndexQuickEdit;
    }>(),
    {
      contained: false,
      footerActive: false,
      enableAdjustPageSize: false,
      withBottomBorder: true,
      toolbarAsForm: true,
    }
  );
  const emit = defineEmits<{
    'site-change': [handle: string];
    dblclick: [event: MouseEvent];
  }>();
  const {
    elementIndex,
    table,
    data,
    search,
    status,
    conditions,
    mode,
    sortField,
    sortDirection,
    tableColumns,
    columnOptions,
    visibleViewModes,
    loading,
    processing,
    filterContext,
    submit,
    reorder,
    structureView,
    toggleStructure,
    selection,
  } = props.view;
  const structure = props.view.structure;
  const rowReorder = props.view.rowReorder;
  const inlineEditing = props.view.inlineEditing;
  const exportElements = props.view.exportElements;
  const activeSiteHandle = computed(
    () =>
      elementIndex.sites.find((site) => site.id === elementIndex.siteId)
        ?.handle ?? null
  );
  provide(elementIndexContextKey, filterContext);

  const body = useTemplateRef<HTMLElement>('body');
  const editableRows = computed(() =>
    data.value.filter((row) => row.inlineEditable)
  );
  const inlineEditor = useInlineEditing({
    container: body,
    editableRows,
    busy: processing,
    loading,
    load: () => inlineEditing?.load() ?? Promise.resolve(),
    save: (request) => inlineEditing?.save(request) ?? Promise.resolve(false),
  });
  const isInlineEditing = computed(
    () =>
      Boolean(inlineEditing?.active.value) ||
      inlineEditor.editingIds.value.length > 0
  );
  const canEditInline = computed(
    () =>
      Boolean(inlineEditing) &&
      mode.value === 'table' &&
      !selection.readOnly.value &&
      editableRows.value.length > 0
  );
  const selectable = computed(() => !isInlineEditing.value);
  const showFooter = computed(
    () =>
      props.footerActive ||
      isInlineEditing.value ||
      props.enableAdjustPageSize ||
      elementIndex.pagination.total > 0 ||
      table.getPageCount() > 1
  );
  const itemBehavior = computed<ElementIndexItemBehavior>(() => ({
    ...props.itemBehavior,
    ...(isInlineEditing.value
      ? {onClick: undefined, onKeydown: undefined}
      : {}),
    attrs: (row) => ({
      ...props.itemBehavior?.attrs?.(row),
      'data-index-id': row.id,
    }),
  }));

  watch(inlineEditor.editingIds, (ids) => {
    if (inlineEditing) {
      inlineEditing.active.value = ids.length > 0;
    }
  });

  onMounted(() => {
    if (inlineEditing?.active.value && canEditInline.value) {
      void startInlineEditing();
    }
  });

  onLongPress(body, (event) => {
    if (
      !isInlineEditing.value &&
      event.pointerType === 'touch' &&
      props.quickEdit?.longPress
    ) {
      props.quickEdit.onDblClick(event);
    }
  });
  const liveMessage = ref('');
  watch(loading, (isLoading, wasLoading) => {
    if (isLoading) liveMessage.value = t('Loading…');
    else if (wasLoading)
      liveMessage.value = t('{total, plural, =1{# item} other{# items}}', {
        total: elementIndex.pagination.total,
      });
  });
  watch(selection.selectedIds, (ids) => {
    liveMessage.value = ids.length
      ? t('{num, plural, =1{# item selected} other{# items selected}}', {
          num: ids.length,
        })
      : t('Selection cleared');
  });

  function moveRow(id: string | number, move: StructureMove): void {
    void structure?.moveRow(id, move);
  }

  async function startInlineEditing(): Promise<void> {
    if (!inlineEditing) {
      return;
    }

    inlineEditing.active.value = true;
    selection.clearSelection();

    try {
      await inlineEditor.start();
    } catch (cause) {
      inlineEditing.active.value = false;
      throw cause;
    }
  }

  function onBodyMouseCapture(event: MouseEvent): void {
    if (isInlineEditing.value || !props.quickEdit) {
      return;
    }

    if (props.quickEdit.deferChipClick?.(event)) {
      return;
    }

    if (event.type === 'click') {
      props.quickEdit.onPrimaryLink?.(event);
    } else {
      props.quickEdit.suppressPrimaryLinkVisit?.(event);
    }
  }

  function onBodyDblClick(event: MouseEvent): void {
    if (isInlineEditing.value) {
      return;
    }

    if (props.quickEdit) {
      props.quickEdit.onDblClick(event);
    } else {
      emit('dblclick', event);
    }
  }

  function onBodyKeydown(event: KeyboardEvent): void {
    inlineEditor.onKeydown(event);
  }
</script>

<template>
  <div class="element-index" :class="{'element-index--contained': contained}">
    <div class="element-index__header">
      <ElementIndexToolbar
        v-if="!isInlineEditing"
        v-model:search="search"
        v-model:status="status"
        v-model:conditions="conditions"
        v-model:mode="mode"
        v-model:sort-field="sortField"
        v-model:sort-direction="sortDirection"
        v-model:table-columns="tableColumns"
        :processing="processing"
        :as-form="toolbarAsForm"
        :status-options="elementIndex.statusOptions"
        :view-modes="visibleViewModes"
        :column-options="columnOptions"
        :sort-options="elementIndex.sortOptions"
        :sites="showSites ? elementIndex.sites : undefined"
        :site-handle="showSites ? activeSiteHandle : undefined"
        @submit="submit"
        @reorder="reorder"
        @site-change="emit('site-change', $event)"
      >
        <template #actions>
          <div class="flex flex-wrap gap-md">
            <craft-button
              v-if="canEditInline"
              type="button"
              :variant="ButtonVariant.Fill"
              .disabled="processing"
              @click="startInlineEditing"
              >{{ t('Edit inline') }}</craft-button
            >
            <slot name="toolbar-actions" :element-index="elementIndex" />
            <ElementIndexExportMenu
              v-if="exportElements"
              :exporters="elementIndex.exporters ?? []"
              :max-limit="elementIndex.pagination.total"
              :disabled="processing"
              @export="
                (format, type, limit) =>
                  exportElements?.(
                    format,
                    type,
                    selection.selectedIds.value,
                    limit
                  )
              "
            />
            <slot name="toolbar-actions-after" :element-index="elementIndex" />
          </div>
        </template>
        <template #search-options v-if="$slots['search-options']">
          <slot name="search-options" />
        </template>
      </ElementIndexToolbar>
    </div>
    <div class="element-index__navbar" v-if="$slots.navbar">
      <slot name="navbar" />
    </div>
    <div class="element-index__body" :aria-busy="loading ? 'true' : undefined">
      <div
        ref="body"
        @mousedown.capture="
          isInlineEditing
            ? undefined
            : quickEdit?.suppressPrimaryLinkVisit?.($event)
        "
        @mouseup.capture="onBodyMouseCapture"
        @click.capture="onBodyMouseCapture"
        @dblclick="onBodyDblClick"
        @keydown="onBodyKeydown"
      >
        <ElementCards
          v-if="mode === 'cards'"
          :selection="selection.selection"
          :read-only="selection.readOnly.value"
          :data="data"
          :selectable="selectable"
          :loading="loading"
          :item-behavior="itemBehavior"
          :sortable="rowReorder?.enabled.value ?? false"
          :interactions-disabled="processing"
          :render-server-actions="!$slots['card-actions']"
          @reorder="(from, to) => rowReorder?.move(from, to)"
        >
          <template v-if="$slots['card-actions']" #actions="{element}">
            <slot name="card-actions" :element="element" />
          </template>
        </ElementCards>
        <ElementThumbs
          v-else-if="mode === 'thumbs'"
          :selection="selection.selection"
          :read-only="selection.readOnly.value"
          :data="data"
          :selectable="selectable"
          :loading="loading"
          :item-behavior="itemBehavior"
          :interactions-disabled="processing"
        />
        <ElementTable
          v-else
          :table="table"
          :selection="selection"
          :selectable="selectable"
          :loading="loading"
          :spacing="TableSpacing.Spacious"
          :item-behavior="itemBehavior"
          :with-bottom-border="withBottomBorder"
          :structure="
            !isInlineEditing && structure !== undefined && mode === 'structure'
          "
          :is-row-collapsed="structureView.isCollapsed"
          :is-row-pending="structureView.isPending"
          :reorderable="
            !isInlineEditing &&
            (mode === 'structure'
              ? (structure?.reorderable ?? false)
              : (rowReorder?.enabled.value ?? false))
          "
          :interactions-disabled="processing"
          :inline-editing="isInlineEditing"
          :render-cell="inlineEditor.renderCell"
          :can-move-row="structure?.canMoveRow"
          @toggle-structure="toggleStructure"
          @move-structure-row="moveRow"
          @reorder="(from, to) => rowReorder?.move(from, to)"
        />
      </div>
    </div>
    <div class="element-index__footer" v-if="showFooter">
      <div v-if="isInlineEditing" class="flex gap-md">
        <craft-button
          type="button"
          :variant="ButtonVariant.Primary"
          .disabled="processing"
          @click="inlineEditor.save"
          >{{ t('Save') }}</craft-button
        >
        <craft-button
          type="button"
          :variant="ButtonVariant.Plain"
          .disabled="processing"
          @click="inlineEditor.cancel"
          >{{ t('Cancel') }}</craft-button
        >
      </div>
      <slot v-else-if="footerActive" name="footer" />
      <PaginationControls
        v-else
        :page-index="table.atoms.pagination.get().pageIndex"
        :page-size="table.atoms.pagination.get().pageSize"
        :page-count="table.getPageCount()"
        :paginated="
          Boolean(
            table.options.manualPagination ||
            'paginatedRowModel' in table.options.features
          )
        "
        :from="elementIndex.pagination.from"
        :to="elementIndex.pagination.to"
        :total="elementIndex.pagination.total"
        :enable-adjust-page-size="enableAdjustPageSize"
        :disabled="processing"
        @page-change="table.setPageIndex"
        @page-size-change="table.setPageSize"
      />
    </div>
    <span class="sr-only" role="status" aria-live="polite">{{
      liveMessage
    }}</span>
  </div>
</template>

<style scoped lang="scss">
  .element-index--contained {
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;

    .element-index__body {
      flex: 1 1 auto;
      min-height: 0;
      overflow: auto;
    }
    .element-index__header,
    .element-index__navbar,
    .element-index__footer {
      flex: none;
    }
  }

  .element-index {
    // Keep wide tables from stretching the grid track and trapping the sticky footer.
    min-width: 0;
  }

  .element-index__header,
  .element-index__navbar,
  .element-index__body,
  .element-index__footer {
    padding-inline: var(--cp-container-padding);
  }

  .element-index__header {
    margin-block-end: var(--c-spacing-md);
  }

  .element-index__body {
    overflow-x: auto;
  }

  .element-index__footer {
    position: sticky;
    inset-block-end: 0;
    z-index: 1;
    display: flex;
    align-items: center;
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
    min-height: var(--cp-footer-height);
    background-color: var(--c-surface-default);
  }
</style>
