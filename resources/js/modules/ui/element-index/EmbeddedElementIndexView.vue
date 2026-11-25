<script setup lang="ts">
  import {isHttpError, t} from '@craftcms/ui';
  import ElementBulkActionsBar from '@/modules/elements/index/components/ElementBulkActionsBar.vue';
  import ElementIndex from '@/modules/elements/index/components/ElementIndex.vue';
  import {normalizeSort} from '@/modules/elements/index/composables/useElementIndexSort';
  import {useElementRowActions} from '@/modules/elements/index/composables/useElementRowActions';
  import type {InlineEditingSaveResult} from '@/modules/elements/index/composables/useInlineEditing';
  import {exportElementIndex} from '@/modules/elements/index/export-element-index';
  import {saveInlineElements} from '@/modules/elements/index/save-inline-elements';
  import type {ElementIndexExportFormat} from '@/modules/elements/index/types/exporters';
  import {showError} from '@/modules/messages/messages';
  import {
    appendPayloadAssets,
    type NestedContentIndexData,
  } from '../nested-elements/nested-elements';
  import {useEmbeddedIndexElements} from './useEmbeddedIndexElements';

  type EmbeddedIndexData = NestedContentIndexData & {readOnly?: boolean};

  /**
   * Lists an embedded index's elements from its first payload, loading later
   * pages, sorts and searches from the `embeddedIndex` context, and exports
   * them. Unless it's read-only, its elements can be selected for element
   * actions and edited inline.
   */
  const props = defineProps<{
    elementType: string;
    initial: EmbeddedIndexData;
    /** What scopes every request: the index's token, or its criteria. */
    params: Record<string, unknown>;
    storageKey: string;
    readOnly?: boolean;
  }>();

  const emit = defineEmits<{error: [cause: unknown]}>();

  const {elementType, initial, params} = props;
  const editable = !props.readOnly && !initial.readOnly;
  const scopeParams = {
    elementType,
    context: 'embeddedIndex',
    source: '__IMP__',
    ...params,
  };

  // The screen's `?site=` doesn't reach the requests for later pages, so they
  // name the site the first page listed until the site menu changes it.
  const initialSite = initial.sites.find(
    (site) => site.id === initial.siteId
  )?.handle;

  void appendPayloadAssets(initial);

  /** The params that find the elements the index currently lists, for actions, inline edits and exports. */
  function listedParams(): Record<string, unknown> {
    const {page: _page, ...query} = model.query.value;

    return {...query, ...scopeParams};
  }

  /**
   * The params element actions find the listed elements with. The status menu's
   * value goes under `criteria`, apart from action settings such as Set Status's `status`.
   */
  function actionParams(): Record<string, unknown> {
    const {status, ...listed} = listedParams();

    return {...listed, criteria: {status: status || null}};
  }

  async function exportElements(
    format: ElementIndexExportFormat = 'csv',
    type?: string,
    selectedIds: ReadonlyArray<string | number> = [],
    limit?: number
  ): Promise<void> {
    if (!type) {
      return;
    }

    try {
      await exportElementIndex(
        {
          ...listedParams(),
          sort: normalizeSort(model.query.value.sort ?? initialSort),
        },
        format,
        type,
        selectedIds,
        limit
      );
    } catch (cause) {
      const message = isHttpError<{message?: string}>(cause)
        ? cause.response?.data?.message
        : null;
      showError(message || t('Could not export elements.'));
    }
  }

  function reload(): Promise<void> {
    return model.load();
  }

  /** Saves inline edits, then reloads the rows so they show the saved values. */
  async function saveInline(
    body: URLSearchParams
  ): Promise<InlineEditingSaveResult | false> {
    const result = await saveInlineElements(body, {
      ...listedParams(),
      siteId: model.view.elementIndex.siteId,
    });

    if (result && (!result.errors || Object.keys(result.errors).length === 0)) {
      await reload();
    }

    return result;
  }

  const {model, initialSort} = useEmbeddedIndexElements<EmbeddedIndexData>({
    initial,
    initialQuery: initialSite ? {site: initialSite} : {},
    storageKey: props.storageKey,
    readOnly: !editable,
    multiSelect: editable,
    filterContext: (payload) => ({
      fieldLayouts: payload.fieldLayouts,
      extraParams: params,
    }),
    requestParams: () => scopeParams,
    showHeaderColumn: initial.viewState.showHeaderColumn,
    saveInline: editable ? saveInline : undefined,
    exportElements,
    onError: (cause) => emit('error', cause),
  });

  const {selection, elementIndex} = model.view;

  const {quickEdit, editElement, viewElement} = useElementRowActions({
    rows: () => elementIndex.data,
    refresh: () => {
      void reload();
    },
  });

  function onActionPerformed(): void {
    selection.clearSelection();
    void reload();
  }

  model.restore();
</script>

<template>
  <section class="embedded-element-index" :aria-busy="model.view.loading.value">
    <ElementIndex
      :view="model.view"
      :toolbar-as-form="false"
      show-sites
      @site-change="model.changeSite"
      table-appearance="plain"
      :quick-edit="editable ? quickEdit : undefined"
      :footer-active="editable && selection.hasSelection.value"
    >
      <template v-if="editable" #footer>
        <ElementBulkActionsBar
          :selected-ids="selection.selectedIds.value"
          :selected-elements="selection.selectedRows.value"
          :actions="elementIndex.actions ?? []"
          :element-type="elementType"
          source="__IMP__"
          context="embeddedIndex"
          :params="actionParams()"
          :site-id="elementIndex.siteId"
          @edit="editElement"
          @view="viewElement"
          @performed="onActionPerformed"
          @clear="selection.clearSelection()"
        />
      </template>
    </ElementIndex>
  </section>
</template>

<style scoped>
  .embedded-element-index {
    --cp-container-padding: 0;
    container: cp-content-view / inline-size;
    min-width: 0;
    max-width: 100%;
  }
</style>
