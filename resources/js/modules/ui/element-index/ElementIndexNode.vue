<script setup lang="ts">
  import {actionClient, appendBodyHtml, appendHeadHtml, t} from '@craftcms/ui';
  import {shallowRef} from 'vue';
  import {getElements} from '@/actions/CraftCms/Cms/Http/Controllers/Elements/ElementIndex/ElementIndexController';
  import ElementIndex from '@/modules/elements/index/components/ElementIndex.vue';
  import {useDetachedElementIndex} from '@/modules/elements/index/composables/useDetachedElementIndex';
  import type {IndexQueryParams} from '@/modules/elements/index/composables/useElementIndexVisits';
  import type {NestedContentIndexData} from '../nested-elements/nested-elements';
  import type {UiNodePayload} from '../types';

  type ElementIndexNodeProps = {
    token: string;
    elementType: string;
    storageKey?: string | null;
    initial: NestedContentIndexData;
  };

  const props = defineProps<{
    node: UiNodePayload<ElementIndexNodeProps>;
  }>();

  const {token, elementType, storageKey, initial} = props.node.props;
  const error = shallowRef('');
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

  async function appendPayloadAssets(
    payload: NestedContentIndexData
  ): Promise<void> {
    await appendHeadHtml(payload.headHtml ?? '');
    await appendBodyHtml(payload.bodyHtml ?? '');
  }

  void appendPayloadAssets(initial);

  const model = useDetachedElementIndex<NestedContentIndexData>({
    initial,
    storageKey: `embeddedindex.${storageKey ?? elementType}`,
    readOnly: true,
    multiSelect: false,
    filterContext: (payload) => ({
      fieldLayouts: payload.fieldLayouts,
      extraParams: {embeddedIndexToken: token},
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
          embeddedIndexToken: token,
          viewMode: renderedMode,
          columns: renderedColumns,
          ...query,
          sort: normalizedSort(query.sort ?? initialSort),
          showInGrid: (query.viewMode ?? renderedMode) === 'cards',
        }
      );

      return data;
    },
    onLoaded: async (payload) => {
      error.value = '';
      await appendPayloadAssets(payload);
    },
    onError: () => {
      error.value = t('Could not load elements.');
    },
  });

  model.restore();
</script>

<template>
  <section class="embedded-element-index" :aria-busy="model.view.loading.value">
    <div v-if="error" role="alert">{{ error }}</div>
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
