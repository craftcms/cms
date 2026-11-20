<script setup lang="ts">
  /**
   * A step run's items, as the preview lists a step's, by result and a page at a time,
   * linked to the elements they imported into. Failed ones list their problems, with a
   * button to see the item's data.
   */
  import {actionClient, isHttpError, t} from '@craftcms/ui';
  import {
    itemData as itemDataAction,
    items as itemsAction,
  } from '@actions/Import/ImportRunsController';
  import {computed, shallowRef, watch} from 'vue';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import ItemPreviewModal from '@/modules/import/preview/ItemPreviewModal.vue';
  import ItemResultsTable from '@/modules/import/preview/ItemResultsTable.vue';
  import type {
    ItemAction,
    PreviewItem,
  } from '@/modules/import/preview/plan-preview';
  import {showError} from '@/modules/messages/messages';
  import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';
  import {openSlideoutWith} from '@/common/slideouts';
  import RunItemData from './RunItemData.vue';
  import type {PreviewResult} from '@/modules/import/preview/plan-preview';
  import type {RunItem, RunItems} from './run-state';

  const props = defineProps<{
    runId: string;
    stepUid: string;
    /** The first page of all the items, kept up to date while the run goes on. */
    initial: RunItems;
    /** The step as the plan has it now, to preview failed items against, if it can be. */
    previewStep: ImportStep | null;
    /** The failed items the preview would now skip, by number, once it's known; the others it's asked about when opened. */
    skippedInPreview: number[] | null;
  }>();

  const page = shallowRef(0);
  const filter = shallowRef<PreviewResult | 'all'>(props.initial.result);
  const shown = shallowRef<RunItems>(props.initial);
  const loading = shallowRef(false);
  const previewed = shallowRef<RunItem | null>(null);
  const previewing = shallowRef<number | null>(null);
  let request = 0;

  async function load(): Promise<void> {
    const current = ++request;
    loading.value = true;

    try {
      const {data} = await actionClient.get<RunItems>(
        itemsAction.url({runId: props.runId}),
        {
          params: {
            step: props.stepUid,
            page: page.value,
            result: filter.value === 'all' ? undefined : filter.value,
          },
        }
      );

      if (current === request) {
        shown.value = data;
      }
    } finally {
      if (current === request) {
        loading.value = false;
      }
    }
  }

  // the page first shown comes with the run, and keeps coming while it goes on
  watch(
    () => props.initial,
    (initial) => {
      if (page.value === 0 && initial.result === filter.value) {
        shown.value = initial;
      } else {
        void load();
      }
    }
  );

  watch(filter, (value) => {
    page.value = 0;

    if (value === props.initial.result) {
      shown.value = props.initial;
    } else {
      void load();
    }
  });

  function showPage(index: number): void {
    page.value = index;
    void load();
  }

  /** What kept an item from importing, with where each problem is. */
  function problems(item: RunItem): string[] {
    if (!item.failure) {
      return [];
    }

    return item.failure.problems.length
      ? item.failure.problems.map(({path, message}) =>
          path.length ? `${path.join(' › ')}: ${message}` : message
        )
      : [item.failure.message];
  }

  const rows = computed(() =>
    shown.value.items.map((item) => ({...item, problems: problems(item)}))
  );

  /** Whether the preview can show an item: any it knows the number of, but a failed one it would skip. */
  function previewable(item: RunItem): boolean {
    if (props.previewStep === null || !item.item) {
      return false;
    }

    return (
      !item.failure ||
      (props.skippedInPreview !== null &&
        !props.skippedInPreview.includes(item.item))
    );
  }

  /** Each item's data, rendered as a JSON file's preview is, by row id, once it's been fetched. */
  const dataHtml = new Map<string, string>();
  /** The item whose data is being fetched, which shows on its button until it opens. */
  const fetchingData = shallowRef<string | null>(null);

  /** Shows a failed item's data in a slideout, with room for a long one, once it's fetched. */
  async function showData(item: RunItem): Promise<void> {
    const name = item.title || t('Item {number, number}', {number: item.item});
    let html = dataHtml.get(item.id);

    if (html === undefined) {
      fetchingData.value = item.id;

      try {
        // the highlighter too, so the data doesn't show before it's highlighted
        const [{data}] = await Promise.all([
          actionClient.get<{previewHtml: string | null}>(
            itemDataAction.url({runId: props.runId}),
            {params: {step: props.stepUid, item: item.item}}
          ),
          import('@/common/utils/highlight-code'),
        ]);
        html = data.previewHtml ?? '';
        dataHtml.set(item.id, html);
      } catch (error) {
        showError(
          isHttpError(error) && error.message
            ? error.message
            : t('The item’s data couldn’t be loaded.')
        );

        return;
      } finally {
        fetchingData.value = null;
      }
    }

    // SAFETY: The slideout host renders this Vue SFC exactly like its Inertia
    // page components; it does not require an Inertia page module.
    openSlideoutWith(RunItemData as InertiaPageComponent, {
      html,
      title: t('Data for {item}', {item: name}),
    });
  }

  function actions(item: RunItem): ItemAction[] {
    if (!item.item) {
      return [];
    }

    const name = item.title || t('Untitled');

    return [
      // only a failed item's data was kept
      ...(item.failure
        ? [
            {
              icon: 'brackets-curly',
              tooltip: t('View data'),
              label: t('View data for {item}', {item: name}),
              onClick: () => void showData(item),
              loading: fetchingData.value === item.id,
            },
          ]
        : []),
      ...(previewable(item)
        ? [
            {
              icon: 'eye',
              tooltip: t('Open in preview'),
              label: t('Open {item} in preview', {item: name}),
              onClick: () => {
                previewed.value = item;
                previewing.value = 0;
              },
            },
          ]
        : []),
    ];
  }

  const previewItems = computed<PreviewItem[]>(() =>
    previewed.value?.item
      ? [
          {
            index: previewed.value.item - 1,
            result: previewed.value.result,
            title: previewed.value.title,
            match: previewed.value.match,
            errors: [],
          },
        ]
      : []
  );
</script>

<template>
  <div>
    <ItemResultsTable
      flush
      v-model:filter="filter"
      :can-filter="(result) => shown.results.includes(result)"
      :items="rows"
      :row-id="(item) => item.id"
      :match-columns="shown.matchColumns"
      :counts="shown.counts"
      :total="shown.total"
      :matching="shown.matching"
      :page="shown.page"
      :page-size="shown.pageSize"
      :loading="loading"
      :placeholder-rows="Math.min(shown.pageSize, shown.matching || 1)"
      :title-url="(item) => item.url"
      :actions="actions"
      :action-count="2"
      @page="showPage"
    >
    </ItemResultsTable>
    <ItemPreviewModal
      v-if="previewStep"
      v-model="previewing"
      :step="previewStep"
      :items="previewItems"
    />
  </div>
</template>
