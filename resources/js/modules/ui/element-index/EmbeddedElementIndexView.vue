<script setup lang="ts">
  import {
    actionClient,
    appendBodyHtml,
    appendHeadHtml,
    isHttpError,
    t,
  } from '@craftcms/ui';
  import {runAction} from '@craftcms/ui/actions.mjs';
  import {getElements} from '@/actions/CraftCms/Cms/Http/Controllers/Elements/ElementIndex/ElementIndexController';
  import exportIndex from '@/actions/CraftCms/Cms/Http/Controllers/Elements/ElementIndex/ExportElementIndexController';
  import {useElementQuickEdit} from '@/modules/elements/composables/useElementQuickEdit';
  import ElementBulkActionsBar from '@/modules/elements/index/components/ElementBulkActionsBar.vue';
  import ElementIndex from '@/modules/elements/index/components/ElementIndex.vue';
  import {useDetachedElementIndex} from '@/modules/elements/index/composables/useDetachedElementIndex';
  import type {IndexQueryParams} from '@/modules/elements/index/composables/useElementIndexVisits';
  import type {InlineEditingSaveResult} from '@/modules/elements/index/composables/useInlineEditing';
  import {saveInlineElements} from '@/modules/elements/index/save-inline-elements';
  import type {ElementIndexExportFormat} from '@/modules/elements/index/types/exporters';
  import type {BulkActionEventDetail} from '@/modules/elements/types/actions';
  import {showError} from '@/modules/messages/messages';
  import type {NestedContentIndexData} from '../nested-elements/nested-elements';

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
  const renderedMode = initial.viewState.mode;
  const renderedColumns = initial.defaultTableColumns.filter(
    (column) => column !== 'title'
  );
  const scopeParams = {
    elementType,
    context: 'embeddedIndex',
    source: '__IMP__',
    ...params,
  };

  function normalizedSort(value: unknown): IndexQueryParams['sort'] {
    const items = Array.isArray(value)
      ? value
      : value instanceof Object
        ? Object.values(value)
        : [];

    return items.filter(
      (item): item is {field: string; direction: 'asc' | 'desc'} =>
        item instanceof Object &&
        typeof item.field === 'string' &&
        (item.direction === 'asc' || item.direction === 'desc') &&
        item.field !== 'score'
    );
  }

  /** Flattens params into the bracketed keys a form body needs for nested values. */
  function formParams(
    values: Record<string, unknown>,
    prefix = ''
  ): Record<string, unknown> {
    return Object.fromEntries(
      Object.entries(values).flatMap(([key, value]) => {
        const name = prefix ? `${prefix}[${key}]` : key;

        return value instanceof Object
          ? Object.entries(formParams(value as Record<string, unknown>, name))
          : [[name, value]];
      })
    );
  }

  const initialSort = normalizedSort(initial.sort);
  // The screen's `?site=` doesn't reach the requests for later pages, so they
  // name the site the first page listed until the site menu changes it.
  const initialSite = initial.sites.find(
    (site) => site.id === initial.siteId
  )?.handle;

  async function appendPayloadAssets(
    payload: NestedContentIndexData
  ): Promise<void> {
    await appendHeadHtml(payload.headHtml ?? '');
    await appendBodyHtml(payload.bodyHtml ?? '');
  }

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
      await runAction({
        type: 'download',
        method: 'POST',
        url: exportIndex.url(),
        body: {
          ...listedParams(),
          criteria: {
            ...(selectedIds.length ? {id: [...selectedIds]} : {}),
            ...(limit ? {limit} : {}),
          },
          sort: normalizedSort(model.query.value.sort ?? initialSort),
          type,
          format,
        },
      });
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

  let loadingInlineInputs = false;

  /** Reloads the rows with their inline inputs, to start editing inline. */
  async function loadInlineInputs(): Promise<void> {
    loadingInlineInputs = true;

    try {
      await model.load();
    } finally {
      loadingInlineInputs = false;
    }
  }

  /** Saves inline edits, then reloads the rows so they show the saved values. */
  async function saveInline(
    body: URLSearchParams
  ): Promise<InlineEditingSaveResult | false> {
    const result = await saveInlineElements(
      body,
      formParams({...listedParams(), siteId: model.view.elementIndex.siteId})
    );

    if (result && (!result.errors || Object.keys(result.errors).length === 0)) {
      await reload();
    }

    return result;
  }

  const model = useDetachedElementIndex<EmbeddedIndexData>({
    initial,
    initialQuery: initialSite ? {site: initialSite} : {},
    storageKey: props.storageKey,
    readOnly: !editable,
    multiSelect: editable,
    filterContext: (payload) => ({
      fieldLayouts: payload.fieldLayouts,
      extraParams: params,
    }),
    inlineEditing: editable
      ? {load: loadInlineInputs, save: saveInline}
      : undefined,
    exportElements,
    pinnedColumn: {key: 'title', label: initial.elementDisplayName},
    data: (rows, mode, elementIndex) =>
      elementIndex.viewState.mode === mode ? rows : [],
    fetch: async (query) => {
      const {data} = await actionClient.post<EmbeddedIndexData>(
        getElements.url(),
        {
          viewMode: renderedMode,
          columns: renderedColumns,
          ...query,
          ...scopeParams,
          ...(loadingInlineInputs ? {editable: true} : {}),
          sort: normalizedSort(query.sort ?? initialSort),
          showInGrid: (query.viewMode ?? renderedMode) === 'cards',
        }
      );

      return data;
    },
    onLoaded: async (payload) => {
      await appendPayloadAssets(payload);
    },
    onError: (cause) => emit('error', cause),
  });

  const {selection, elementIndex} = model.view;

  // Double-click an element to edit it in a slideout.
  const quickEdit = useElementQuickEdit({
    refreshResults: () => {
      void reload();
    },
  });

  function actionRow(detail: BulkActionEventDetail) {
    if (detail.elementIds.length !== 1) {
      return null;
    }

    const id = String(detail.elementIds[0]);

    return elementIndex.data.find((row) => String(row.id) === id) ?? null;
  }

  function editElement(detail: BulkActionEventDetail): void {
    const url = actionRow(detail)?.cpEditUrl;

    if (url) {
      quickEdit.openEditor(url, detail.trigger);
    }
  }

  function viewElement(detail: BulkActionEventDetail): void {
    const url = actionRow(detail)?.viewUrl;

    if (url) {
      window.open(url, '_blank', 'noopener');
    }
  }

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
