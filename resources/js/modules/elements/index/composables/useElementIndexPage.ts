import {router, usePage} from '@inertiajs/vue3';
import {onMounted, onScopeDispose} from 'vue';
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
  const index = useElementIndex({
    elementIndex,
    visitor: createIndexVisitor(options.route),
    loading,
    readOnly: () => page.props.readOnly ?? false,
    pinnedColumn: options.pinnedColumn,
    filterParams: options.filterParams,
    structure: true,
    refresh: () =>
      new Promise((resolve) => {
        let applied = false;
        router.reload({
          only: ['data', 'pagination', 'badgeCounts'],
          onSuccess: () => {
            applied = true;
          },
          onFinish: () => resolve(applied),
        });
      }),
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
