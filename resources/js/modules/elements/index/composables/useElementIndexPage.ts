import {router, usePage} from '@inertiajs/vue3';
import {runAction} from '@craftcms/ui/actions.mjs';
import {onMounted, onScopeDispose} from 'vue';
import exportIndex from '@/actions/CraftCms/Cms/Http/Controllers/Elements/ElementIndex/ExportElementIndexController';
import {useContentIndexData} from './useContentIndexData';
import {useElementIndex} from './useElementIndex';
import {useElementIndexLoading} from './useElementIndexLoading';
import {useElementStructureMoves} from './useElementStructureMoves';
import {
  createIndexVisitor,
  type ElementIndexRoute,
  type IndexQueryParams,
} from './useElementIndexVisits';
import type {ElementIndexModel} from '../types/model';
import {saveInlineElements} from '../save-inline-elements';
import type {ElementIndexExportFormat} from '../types/exporters';

interface UseElementIndexPageOptions {
  route: ElementIndexRoute;
  pinnedColumn?: {key: string; label: string};
  filterParams?: () => IndexQueryParams;
}

export function useElementIndexPage(
  options: UseElementIndexPageOptions
): ElementIndexModel {
  const elementIndex = useContentIndexData();
  const page = usePage<{readOnly: boolean}>();
  const {loading} = useElementIndexLoading();
  const visitor = createIndexVisitor(options.route);

  function refresh(): Promise<boolean> {
    return new Promise((resolve) => {
      let applied = false;
      router.reload({
        only: ['data', 'pagination', 'badgeCounts'],
        onSuccess: () => {
          applied = true;
        },
        onFinish: () => resolve(applied),
      });
    });
  }

  async function loadEditable(): Promise<void> {
    await new Promise<void>((resolve) => {
      visitor.merge(
        {editable: true},
        {only: ['data'], replace: true, onFinish: () => resolve()}
      );
    });
  }

  async function saveInline(body: URLSearchParams) {
    const result = await saveInlineElements(body, {
      elementType: elementIndex.elementType,
      siteId: elementIndex.siteId,
      context: elementIndex.context,
      source: elementIndex.source?.key,
    });

    if (result && (!result.errors || Object.keys(result.errors).length === 0)) {
      await refresh();
    }

    return result;
  }

  async function exportElements(
    format: ElementIndexExportFormat,
    type: string,
    selectedIds: ReadonlyArray<string | number>,
    limit?: number
  ): Promise<void> {
    await runAction({
      type: 'download',
      method: 'POST',
      url: exportIndex.url(),
      body: {
        elementType: elementIndex.elementType,
        context: elementIndex.context,
        source: elementIndex.source?.key,
        baseCriteria: {
          status: null,
          drafts: elementIndex.canHaveDrafts ? null : false,
          draftOf: false,
          savedDraftsOnly: true,
          siteId: elementIndex.siteId,
        },
        criteria: {
          search: elementIndex.search ?? '',
          status: elementIndex.status || null,
          ...(elementIndex.drafts ? {drafts: true} : {}),
          ...(elementIndex.trashed ? {trashed: true} : {}),
          ...(selectedIds.length ? {id: [...selectedIds]} : {}),
          ...(limit ? {limit} : {}),
        },
        condition: elementIndex.currentCondition,
        sort: elementIndex.sort,
        type,
        format,
      },
    });
  }

  const index = useElementIndex({
    elementIndex,
    visitor,
    loading,
    readOnly: () => page.props.readOnly ?? false,
    pinnedColumn: options.pinnedColumn,
    filterParams: options.filterParams,
    structure: true,
    inlineEditing: {load: loadEditable, save: saveInline},
    exportElements,
    refresh,
  });
  index.model.view.structure = useElementStructureMoves(index.model);
  onMounted(index.restore);
  onScopeDispose(
    router.on('before', ({detail: {visit}}) => {
      if (!visit.prefetch && !visit.async) index.cancelPendingSearch();
    })
  );

  return index.model;
}
