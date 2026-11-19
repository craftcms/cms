<script setup lang="ts">
  import {
    computed,
    h,
    onBeforeUnmount,
    provide,
    ref,
    shallowRef,
    useTemplateRef,
    watch,
  } from 'vue';
  import {onLongPress, useEventListener} from '@vueuse/core';
  import {router, usePage} from '@inertiajs/vue3';
  import {ButtonVariant, t} from '@craftcms/ui';
  import PaginationControls from '@/common/components/PaginationControls.vue';
  import PlaceholderShape from '@/common/components/PlaceholderShape.vue';
  import {
    TableAppearance,
    TableSpacing,
    type TableAppearanceValue,
  } from '@/common/types';
  import ElementTable from '@/modules/elements/index/components/ElementTable.vue';
  import ElementCards from '@/modules/elements/components/ElementCards.vue';
  import ElementThumbs from '@/modules/elements/components/ElementThumbs.vue';
  import ElementIndexToolbar from '@/modules/elements/index/components/ElementIndexToolbar.vue';
  import SelectAllBanner from '@/modules/elements/index/components/SelectAllBanner.vue';
  import type {ElementIndexView} from '@/modules/elements/index/types/model';
  import type {StructureMove} from '@/modules/elements/index/composables/useElementIndexStructure';
  import type {ElementIndexItemBehavior} from '@/modules/elements/types/item-behavior';
  import ElementIndexFilterBar from '@/modules/elements/index/components/ElementIndexFilterBar.vue';
  import ViewChangeSegment from '@/modules/elements/index/components/ViewChangeSegment.vue';
  import ViewSettingsMenu from '@/modules/elements/index/components/ViewSettingsMenu.vue';
  import type {ConditionConfig} from '@/modules/conditions/types';
  import {
    VIEW_SETTING_KINDS,
    type ViewSettingKind,
  } from '@/modules/elements/index/composables/useElementIndex';
  import CustomizeSourcesModal from '@/modules/elements/index/components/customize-sources/CustomizeSourcesModal.vue';
  import type {SourcesLanding} from '@/modules/elements/index/composables/useCustomizeSources';
  import ExportModal from '@/modules/elements/index/components/ExportModal.vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItems} from '@/common/types';
  import type {CraftData} from '@/common/composables/useCraftData';
  import {showNotice} from '@/modules/messages/messages';
  import {useInlineEditing} from '@/modules/elements/index/composables/useInlineEditing';
  import {commandShortcut} from '@/common/utils/keyShortcuts';
  import {resizeThumbs} from '@/modules/elements/index/composables/useElementIndexThumbSize';
  import type {ThumbSize} from '@/modules/elements/types/view-state';
  import {elementIndexContextKey} from '@/modules/elements/index/index-context';

  interface ElementIndexQuickEdit {
    longPress?: boolean;
    onDblClick(event: MouseEvent): void;
    openEditor?(url: string, opener?: HTMLElement | null): void;
    deferChipClick?(event: MouseEvent): boolean;
    onPrimaryLink?(event: MouseEvent): void;
    suppressPrimaryLinkVisit?(event: MouseEvent): void;
  }

  const props = withDefaults(
    defineProps<{
      view: ElementIndexView;
      contained?: boolean;
      footerActive?: boolean;
      /**
       * Whether selecting the whole page offers to select every result the
       * index lists, on every page.
       */
      offersSelectAll?: boolean;
      itemBehavior?: ElementIndexItemBehavior;
      showSites?: boolean;
      /** Whether rows can be selected. */
      selectable?: boolean;
      enableAdjustPageSize?: boolean;
      withBottomBorder?: boolean;
      /**
       * How the table view meets the index. `flush` (the default) lets the
       * table span the container's inline padding, padding each row's outer
       * cells to match; use `plain` when the container has none.
       */
      tableAppearance?: Exclude<TableAppearanceValue, 'raised'>;
      toolbarAsForm?: boolean;
      quickEdit?: ElementIndexQuickEdit;
      /** Shows how the index differs from its source, and offers to save it. */
      offersSaveView?: boolean;
      /**
       * Items for a menu beside the `primary-action` slot's button, which only
       * gets one when there are any.
       */
      menuActions?: ActionItems;
    }>(),
    {
      contained: false,
      selectable: true,
      footerActive: false,
      offersSelectAll: false,
      enableAdjustPageSize: false,
      withBottomBorder: true,
      tableAppearance: TableAppearance.Flush,
      toolbarAsForm: true,
      offersSaveView: false,
    }
  );
  const emit = defineEmits<{
    'site-change': [handle: string];
    dblclick: [event: MouseEvent];
    /** A new source was saved from the index's settings. */
    'sources-saved': [landing: SourcesLanding];
  }>();
  const {
    elementIndex,
    table,
    data,
    search,
    status,
    conditions,
    mode,
    thumbSize,
    thumbSizeOptions,
    cardThumbs,
    sortField,
    sortDirection,
    resetView,
    changes,
    resetAll,
    tableColumns,
    columnOptions,
    visibleViewModes,
    loading,
    pending,
    processing,
    filterContext,
    submit,
    reorder,
    structureView,
    toggleStructure,
    selection,
    selectAll,
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
  async function onThumbSizeChange(size: ThumbSize): Promise<void> {
    if (body.value) {
      await resizeThumbs(body.value, size);
    }

    thumbSize.value = size;
  }
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
  /**
   * How many placeholders stand in for the rows while they load: as many as
   * are on screen, so the page holds its height, or else a screenful.
   */
  function placeholderCount(screenful: number): number {
    return pending.value || loading.value ? data.value.length || screenful : 0;
  }
  /** Whether the rows have thumbnails, for the placeholders drawn before them. */
  const hasThumbs = computed(() => cardThumbs.value !== null);
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
  const rowsSelectable = computed(
    () => props.selectable && !isInlineEditing.value
  );
  /** Single-select lists get no select-all checkbox. */
  const multiSelect = computed(
    () => props.view.table.options.enableMultiRowSelection !== false
  );
  const paginationProps = computed(() => ({
    pageIndex: table.atoms.pagination.get().pageIndex,
    pageSize: table.atoms.pagination.get().pageSize,
    pageCount: table.getPageCount(),
    paginated: Boolean(
      table.options.manualPagination ||
      'paginatedRowModel' in table.options.features
    ),
    disabled: processing.value,
    onPageChange: table.setPageIndex,
    onPageInput: () => props.view.cancelPageLoad(),
  }));
  /**
   * The page controls alone, handed to the active footer so a selection can
   * keep moving between pages.
   */
  const FooterPageControls = () =>
    h(PaginationControls, {...paginationProps.value, controlsOnly: true});
  const showFooter = computed(
    () =>
      props.footerActive ||
      isInlineEditing.value ||
      props.enableAdjustPageSize ||
      pending.value ||
      (elementIndex.pagination?.total ?? 0) > 0 ||
      table.getPageCount() > 1
  );
  /**
   * What the banner over the results offers: every result, once the whole
   * page is selected, or just the page, once every result is.
   */
  const selectAllOffer = computed<'all' | 'page' | null>(() => {
    if (
      !props.offersSelectAll ||
      !multiSelect.value ||
      isInlineEditing.value ||
      table.getPageCount() <= 1
    ) {
      return null;
    }

    if (selectAll.active.value) {
      return selectAll.exceptIds.value.length === 0 ? 'page' : null;
    }

    return selection.selection.allSelected.value ? 'all' : null;
  });
  const itemBehavior = computed<ElementIndexItemBehavior>(() => ({
    ...props.itemBehavior,
    ...(isInlineEditing.value
      ? {onClick: undefined, onDblClick: undefined, onKeydown: undefined}
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
      liveMessage.value = t(
        '{total, number} {total, plural, =1{{item}} other{{items}}}',
        {
          total: elementIndex.pagination?.total ?? 0,
          item: elementIndex.elementLowerDisplayName,
          items: elementIndex.elementPluralLowerDisplayName,
        }
      );
  });
  watch(selectAll.count, (count) => {
    liveMessage.value = count
      ? t('{num, number} {num, plural, =1{{item}} other{{items}}} selected', {
          num: count,
          item: elementIndex.elementLowerDisplayName,
          items: elementIndex.elementPluralLowerDisplayName,
        })
      : t('Selection cleared');
  });

  function moveRow(id: string | number, move: StructureMove): void {
    void structure?.moveRow(id, move);
  }

  /** The table's View | Edit switch. Edit stays on until saved or left. */
  function setEditing(editing: boolean): void {
    if (editing === isInlineEditing.value) return;

    if (editing) {
      void startInlineEditing();
    } else {
      inlineEditor.cancel();
    }
  }

  // Rows reloaded while editing (a new filter, sort or page) come back with
  // their inputs, so editing carries on over them.
  watch(data, () => {
    if (inlineEditing?.active.value && !inlineEditor.saving.value) {
      void inlineEditor.resume();
    }
  });

  function hasUnsavedEdits(): boolean {
    return !inlineEditor.saving.value && inlineEditor.isDirty();
  }

  const removeNavigationGuard = router.on('before', (event) => {
    const visit = event.detail.visit;

    if (visit.prefetch || visit.method !== 'get' || !hasUnsavedEdits()) {
      return;
    }

    if (!window.confirm(t('Discard your unsaved changes to these rows?'))) {
      event.preventDefault();
      return;
    }

    inlineEditor.clear();
  });
  onBeforeUnmount(removeNavigationGuard);
  useEventListener(window, 'beforeunload', (event) => {
    if (hasUnsavedEdits()) event.preventDefault();
  });

  // Embedded indexes can render without an Inertia page.
  const page = usePage<{craft?: CraftData}>();
  const canEditSources = computed(
    () =>
      Boolean(page.props?.craft?.currentUser?.admin) &&
      Boolean(page.props?.craft?.allowAdminChanges)
  );
  /** Changes to how the index is shown, rather than what it shows. */
  const viewChanges = computed(() =>
    changes.value.filter((change) =>
      (VIEW_SETTING_KINDS as string[]).includes(change.kind)
    )
  );
  const viewChangedKinds = computed(() =>
    viewChanges.value.map((change) => change.kind as ViewSettingKind)
  );
  /** Changes to what the index shows, which built-in sources can't store. */
  const hasFilterChanges = computed(() =>
    changes.value.some((change) =>
      ['rule', 'status', 'search'].includes(change.kind)
    )
  );
  const updateBlockedReason = computed(() =>
    elementIndex.source?.type === 'native' && hasFilterChanges.value
      ? t(
          'Built-in sources can’t store filters. Remove them, or save as a new source.'
        )
      : null
  );
  /** The settings modal's mode while it's open. */
  const savingNew = shallowRef(false);

  /** What sets the index apart from its source, as the sources editor marks it. */
  const editedParts = computed(() => {
    const kinds: string[] = changes.value.map((change) => change.kind);
    const parts = ['sort', 'columns', 'viewMode'].filter((kind) =>
      kinds.includes(kind)
    );

    return kinds.some((kind) => kind === 'rule' || kind === 'status')
      ? ['filters', ...parts]
      : parts;
  });

  function saveToSource(): void {
    showNotice(
      t('Prototype: would save these changes to “{source}”.', {
        source: elementIndex.source?.label ?? '',
      })
    );
  }

  /** The current view, as a new source's settings. */
  const newSourceSettings = computed(() => ({
    ...(conditions.value ? {condition: conditions.value} : {}),
    defaultSort: {attr: sortField.value, dir: sortDirection.value},
    defaultViewMode: mode.value,
    tableAttributes: [...tableColumns.value],
  }));

  /** What the export modal exports while it's open. */
  const exporting = shallowRef<'all' | 'selected' | null>(null);
  const canExport = computed(() =>
    Boolean(exportElements && elementIndex.exporters?.length)
  );
  const moreActions = computed(() =>
    canExport.value
      ? [
          {
            label: t('Export…'),
            // Not mid-request, when the criteria it would send are changing.
            disabled: processing.value,
            onClick: () => (exporting.value = 'all'),
          },
        ]
      : []
  );

  /** The selection as the exporter takes it: IDs, or every result but some. */
  function selectedExportIds(): Array<string | number> {
    if (!selectAll.active.value) return [...selection.selectedIds.value];

    return selectAll.exceptIds.value.length
      ? ['not', ...selectAll.exceptIds.value]
      : [];
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
    // Handled already, by an item behavior such as opening an asset folder.
    if (isInlineEditing.value || event.defaultPrevented) {
      return;
    }

    if (props.quickEdit) {
      props.quickEdit.onDblClick(event);
    } else {
      emit('dblclick', event);
    }
  }

  /**
   * Thumbs carry none of the chip metadata quick edit reads off a row or card,
   * so they report the double-clicked element themselves, and it's opened from
   * the row's own edit URL.
   */
  function onThumbEdit(element: {id: string | number}): void {
    if (
      isInlineEditing.value ||
      !props.quickEdit?.openEditor ||
      elementIndex.trashed
    ) {
      return;
    }

    const row = data.value.find((candidate) => candidate.id === element.id);

    if (!row?.cpEditUrl) {
      return;
    }

    window.getSelection()?.removeAllRanges();
    props.quickEdit.openEditor(
      row.cpEditUrl,
      body.value?.querySelector<HTMLElement>(
        `[data-index-id="${CSS.escape(String(row.id))}"]`
      ) ?? null
    );
  }

  function onBodyKeydown(event: KeyboardEvent): void {
    inlineEditor.onKeydown(event);
  }
</script>

<template>
  <div class="element-index" :class="{'element-index--contained': contained}">
    <div class="element-index__header">
      <ElementIndexToolbar
        v-model:search="search"
        v-model:status="status"
        v-model:conditions="conditions"
        v-model:mode="mode"
        v-model:sort-field="sortField"
        v-model:sort-direction="sortDirection"
        v-model:table-columns="tableColumns"
        :thumb-size="thumbSize"
        @update:thumb-size="onThumbSizeChange"
        :processing="processing"
        :as-form="toolbarAsForm"
        :status-options="elementIndex.statusOptions"
        :view-modes="visibleViewModes"
        :column-options="columnOptions"
        :thumb-size-options="thumbSizeOptions"
        :sort-options="elementIndex.sortOptions"
        :view-changed-settings="viewChangedKinds"
        :sites="showSites ? elementIndex.sites : undefined"
        :site-handle="showSites ? activeSiteHandle : undefined"
        @submit="submit"
        @reorder="reorder"
        @use-default-view="resetView"
        @site-change="emit('site-change', $event)"
      >
        <template
          v-if="
            canEditInline ||
            isInlineEditing ||
            moreActions.length ||
            $slots['toolbar-actions'] ||
            $slots['toolbar-actions-after'] ||
            $slots['primary-action']
          "
          #actions
        >
          <div class="flex flex-wrap gap-md items-center">
            <craft-button-group
              v-if="canEditInline || isInlineEditing"
              name="tableMode"
              :aria-label="t('Table mode')"
              .value="isInlineEditing ? 'edit' : 'view'"
              @change="
                (event: CustomEvent) =>
                  setEditing(event.detail.value === 'edit')
              "
            >
              <craft-button
                type="button"
                value="view"
                :variant="ButtonVariant.Fill"
                .disabled="processing"
                >{{ t('View') }}</craft-button
              >
              <craft-button
                type="button"
                value="edit"
                :variant="ButtonVariant.Fill"
                .disabled="processing"
                >{{ t('Edit') }}</craft-button
              >
            </craft-button-group>
            <slot name="toolbar-actions" :element-index="elementIndex" />
            <ActionMenu
              v-if="moreActions.length"
              :actions="moreActions"
              :label="t('More actions')"
              :button-variant="ButtonVariant.Fill"
              :flush="false"
            />
            <slot name="toolbar-actions-after" :element-index="elementIndex" />
            <!-- The main action comes last, with a menu beside it for anything else. -->
            <craft-button-group
              v-if="$slots['primary-action'] && menuActions?.length"
            >
              <slot name="primary-action" :element-index="elementIndex" />
              <ActionMenu icon="chevron-down" :actions="menuActions">
                <template #invoker="{label}">
                  <craft-button
                    slot="invoker"
                    :variant="ButtonVariant.Primary"
                    type="button"
                    icon
                  >
                    <craft-icon name="chevron-down" :label="label"></craft-icon>
                  </craft-button>
                </template>
              </ActionMenu>
            </craft-button-group>
            <slot v-else name="primary-action" :element-index="elementIndex" />
          </div>
        </template>
        <template #search-options v-if="$slots['search-options']">
          <slot name="search-options" />
        </template>
      </ElementIndexToolbar>
      <!-- Saving as new adds a source in the sources editor, ready to name. -->
      <CustomizeSourcesModal
        v-if="offersSaveView && canEditSources && savingNew"
        :is-active="savingNew"
        :element-type="filterContext?.elementType ?? ''"
        :page="elementIndex.page"
        :new-source="{settings: newSourceSettings, edited: editedParts}"
        @close="savingNew = false"
        @saved="emit('sources-saved', $event)"
      />
      <ElementIndexFilterBar
        v-if="offersSaveView && !isInlineEditing"
        :condition="conditions"
        :summaries="elementIndex.currentConditionSummary ?? []"
        :disabled="processing"
        :edited="canEditSources && changes.length > 0"
        :updatable-source="canEditSources ? elementIndex.source?.label : null"
        :update-blocked-reason="updateBlockedReason"
        @update-condition="conditions = $event"
        @reset="resetAll"
        @update="saveToSource"
        @reset-view="resetView()"
        @save-new="savingNew = true"
        :view-changes="viewChanges"
        :sort-direction="sortDirection"
      >
        <!-- Each chip opens the menu its settings are in, right below it. -->
        <template #view-chip="{chip}">
          <ViewSettingsMenu
            :menu="chip.menu"
            :view-modes="visibleViewModes"
            :column-options="columnOptions"
            :sort-options="elementIndex.sortOptions"
            :thumb-size-options="thumbSizeOptions"
            :search="search"
            :changed="viewChangedKinds"
            :disabled="processing"
            v-model:mode="mode"
            v-model:sort-field="sortField"
            v-model:sort-direction="sortDirection"
            v-model:table-columns="tableColumns"
            :thumb-size="thumbSize"
            @update:thumb-size="onThumbSizeChange"
            @reorder="reorder"
            @use-defaults="resetView"
          >
            <template #invoker>
              <ViewChangeSegment
                slot="invoker"
                :label="chip.label"
                :icon="chip.icon"
                :parts="chip.parts"
                :disabled="processing"
              />
            </template>
          </ViewSettingsMenu>
        </template>
      </ElementIndexFilterBar>
      <ExportModal
        v-if="canExport"
        :is-active="exporting !== null"
        :exporters="elementIndex.exporters ?? []"
        :selected="exporting === 'selected'"
        :count="
          exporting === 'selected'
            ? selectAll.count.value
            : (elementIndex.pagination?.total ?? 0)
        "
        :source-label="elementIndex.source?.label"
        @close="exporting = null"
        @export="
          (format, type, limit) =>
            exportElements?.(
              format,
              type,
              exporting === 'selected' ? selectedExportIds() : [],
              limit
            )
        "
      />
    </div>
    <div class="element-index__navbar" v-if="$slots.navbar">
      <slot name="navbar" />
    </div>
    <div
      :class="[
        'element-index__body',
        {
          'element-index__body--flush':
            tableAppearance === TableAppearance.Flush &&
            mode !== 'cards' &&
            mode !== 'thumbs',
        },
      ]"
      :aria-busy="loading || pending ? 'true' : undefined"
    >
      <div
        ref="body"
        :class="{
          'element-index__grid': mode === 'cards' || mode === 'thumbs',
        }"
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
          :selectable="rowsSelectable"
          :select-all="multiSelect"
          :loading="loading"
          :placeholders="placeholderCount(8)"
          :placeholder-thumb="cardThumbs"
          :item-behavior="itemBehavior"
          :sortable="rowReorder?.enabled.value ?? false"
          :interactions-disabled="processing"
          :render-server-actions="!$slots['card-actions']"
          @reorder="(from, to) => rowReorder?.move(from, to)"
        >
          <template v-if="selectAllOffer" #header-after>
            <SelectAllBanner
              :offer="selectAllOffer"
              :page-count="data.length"
              :total="elementIndex.pagination.total"
              @select-all="selectAll.selectAll()"
              @select-page="selectAll.selectPage()"
            />
          </template>
          <template v-if="$slots['card-actions']" #actions="{element}">
            <slot name="card-actions" :element="element" />
          </template>
        </ElementCards>
        <ElementThumbs
          v-else-if="mode === 'thumbs'"
          :selection="selection.selection"
          :read-only="selection.readOnly.value"
          :data="data"
          :selectable="rowsSelectable"
          :select-all="multiSelect"
          :loading="loading"
          :item-behavior="itemBehavior"
          :interactions-disabled="processing"
          :thumb-size="thumbSize"
          :placeholders="placeholderCount(8)"
          @edit="onThumbEdit"
        >
          <template v-if="selectAllOffer" #header-after>
            <SelectAllBanner
              :offer="selectAllOffer"
              :page-count="data.length"
              :total="elementIndex.pagination.total"
              @select-all="selectAll.selectAll()"
              @select-page="selectAll.selectPage()"
            />
          </template>
        </ElementThumbs>
        <ElementTable
          v-else
          :table="table"
          :selection="selection"
          :selectable="rowsSelectable"
          :loading="loading"
          :placeholder-rows="placeholderCount(5)"
          :spacing="TableSpacing.Spacious"
          :item-behavior="itemBehavior"
          :with-bottom-border="withBottomBorder"
          :appearance="tableAppearance"
          :structure="
            !isInlineEditing &&
            !pending &&
            !loading &&
            elementIndex.structure != null &&
            mode === 'structure'
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
        >
          <template v-if="selectAllOffer" #header-after>
            <SelectAllBanner
              :offer="selectAllOffer"
              :page-count="data.length"
              :total="elementIndex.pagination.total"
              @select-all="selectAll.selectAll()"
              @select-page="selectAll.selectPage()"
            />
          </template>
          <template #placeholder-cell="{index, width}">
            <craft-chip
              v-if="index === 0 && hasThumbs"
              appearance="plain"
              show-thumb
              :thumb-size="thumbSize"
            >
              <PlaceholderShape
                slot="thumbnail"
                class="element-index__thumb-placeholder"
              />
              <PlaceholderShape
                class="element-index__label-placeholder"
                :style="{inlineSize: width}"
              />
            </craft-chip>
            <PlaceholderShape v-else :style="{maxInlineSize: width}" />
          </template>
        </ElementTable>
      </div>
    </div>
    <div class="element-index__footer" v-if="showFooter">
      <div v-if="isInlineEditing" class="flex gap-md">
        <craft-button
          type="button"
          :variant="ButtonVariant.Primary"
          .disabled="processing"
          :aria-keyshortcuts="commandShortcut('S')"
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
      <slot
        v-else-if="footerActive"
        name="footer"
        :pagination="FooterPageControls"
        :export-selection="
          canExport ? () => (exporting = 'selected') : undefined
        "
      />
      <PlaceholderShape
        v-else-if="pending"
        class="element-index__count-placeholder"
      />
      <PaginationControls
        v-else
        v-bind="paginationProps"
        :from="elementIndex.pagination.from"
        :to="elementIndex.pagination.to"
        :total="elementIndex.pagination.total"
        :enable-adjust-page-size="enableAdjustPageSize"
        :item-label="elementIndex.elementLowerDisplayName"
        :items-label="elementIndex.elementPluralLowerDisplayName"
        :page-size-label="
          t('{type} per page', {type: elementIndex.elementPluralDisplayName})
        "
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

  // The table pads its own outer cells instead; see `tableAppearance`.
  .element-index__body--flush {
    padding-inline: 0;
  }

  .element-index__header {
    margin-block-end: var(--c-spacing-md);
  }

  // Card and thumb grids leave their own spacing to where they're placed. A
  // grid's select-all header sits flush at the top, with the grid below it.
  .element-index__grid {
    padding-block-end: var(--c-spacing-lg);

    > :deep(:is(.card-grid, .thumbsview)) {
      margin-block-start: var(--c-spacing-lg);
    }

    // Like a flush table, the select-all header spans the container's padding
    // and pads its content back in by the same amount.
    > :deep(
      :is(
        .card-grid-header,
        .thumbsview-header,
        .card-grid-header-after,
        .thumbsview-header-after
      )
    ) {
      margin-inline: calc(var(--cp-container-padding) * -1);
      margin-block-end: 0;
      padding-block-start: var(--c-spacing-sm);
      padding-inline: var(--cp-container-padding);
    }

    > :deep(:is(.card-grid-header-after, .thumbsview-header-after)) {
      padding-block: var(--c-spacing-sm);
      border-block-end: 1px solid var(--c-color-neutral-border-quiet);
      background-color: var(--c-surface-sunken);
    }
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
    min-height: calc(var(--cp-footer-height) + 1px);
    background-color: var(--c-surface-default);
  }

  .element-index__thumb-placeholder {
    inline-size: var(--c-thumbnail-size);
    block-size: var(--c-thumbnail-size);
  }

  .element-index__label-placeholder {
    min-inline-size: 8rem;
  }

  .element-index__count-placeholder {
    inline-size: 8rem;
  }
</style>
