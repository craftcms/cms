import {router} from '@inertiajs/vue3';
import {onMounted, onScopeDispose} from 'vue';
import {
  elementIndexQueryParams,
  useContentIndexData,
} from './useContentIndexData';
import {useElementIndex} from './useElementIndex';
import {useElementIndexLoading} from './useElementIndexLoading';
import {useElementStructureMoves} from './useElementStructureMoves';
import {
  createIndexVisitor,
  type ElementIndexRoute,
  type IndexQueryParams,
} from './useElementIndexVisits';
import type {ElementIndexModel, ExportElementIndex} from '../types/model';
import {exportElementIndex} from '../export-element-index';
import {saveInlineElements} from '../save-inline-elements';

interface UseElementIndexPageOptions {
  route: ElementIndexRoute;
  pinnedColumn?: {key: string; label: string};
  filterParams?: () => IndexQueryParams;
}

export function useElementIndexPage(
  options: UseElementIndexPageOptions
): ElementIndexModel {
  const elementIndex = useContentIndexData();
  const {loading} = useElementIndexLoading();
  const visitor = createIndexVisitor(options.route);

  function refresh(refreshOptions?: {silent?: boolean}): Promise<boolean> {
    return new Promise((resolve) => {
      let applied = false;
      router.reload({
        showProgress: !refreshOptions?.silent,
        only: [
          'data',
          'pagination',
          'navSourceBadges',
          'sourceNavItems',
          // A Craft 5 index screen (`cp/ElementIndex`) sends its source nav
          // and badge counts as `subnav`.
          'subnav',
        ],
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

  const exportElements: ExportElementIndex = (...args) =>
    exportElementIndex(
      {...elementIndexQueryParams(elementIndex), sort: elementIndex.sort},
      ...args
    );

  const index = useElementIndex({
    elementIndex,
    visitor,
    loading,
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
