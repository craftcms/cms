<script setup lang="ts">
  import {actionClient, appendBodyHtml, appendHeadHtml} from '@craftcms/ui';
  import {getElements} from '@/actions/CraftCms/Cms/Http/Controllers/Elements/ElementIndex/ElementIndexController';
  import ElementIndex from '@/modules/elements/index/components/ElementIndex.vue';
  import {useDetachedElementIndex} from '@/modules/elements/index/composables/useDetachedElementIndex';
  import type {IndexQueryParams} from '@/modules/elements/index/composables/useElementIndexVisits';
  import type {NestedContentIndexData} from '../nested-elements/nested-elements';

  /**
   * Lists an embedded index's elements from its first payload, loading later
   * pages, sorts and searches from the `embeddedIndex` context.
   */
  const props = defineProps<{
    elementType: string;
    initial: NestedContentIndexData;
    /** What scopes every request: the index's token, or its criteria. */
    params: Record<string, unknown>;
    storageKey: string;
  }>();

  const emit = defineEmits<{error: [cause: unknown]}>();

  const {elementType, initial, params} = props;
  const renderedMode = initial.viewState.mode;
  const renderedColumns = initial.defaultTableColumns.filter(
    (column) => column !== 'title'
  );

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

  const model = useDetachedElementIndex<NestedContentIndexData>({
    initial,
    initialQuery: initialSite ? {site: initialSite} : {},
    storageKey: props.storageKey,
    readOnly: true,
    multiSelect: false,
    filterContext: (payload) => ({
      fieldLayouts: payload.fieldLayouts,
      extraParams: params,
    }),
    pinnedColumn: {key: 'title', label: initial.elementDisplayName},
    data: (rows, mode, elementIndex) =>
      elementIndex.viewState.mode === mode ? rows : [],
    fetch: async (query) => {
      const {data} = await actionClient.post<NestedContentIndexData>(
        getElements.url(),
        {
          elementType,
          context: 'embeddedIndex',
          source: '__IMP__',
          viewMode: renderedMode,
          columns: renderedColumns,
          ...query,
          ...params,
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

  model.restore();
</script>

<template>
  <section class="embedded-element-index" :aria-busy="model.view.loading.value">
    <ElementIndex
      :view="model.view"
      :toolbar-as-form="false"
      table-appearance="plain"
    />
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
