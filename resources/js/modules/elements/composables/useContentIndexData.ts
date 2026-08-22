import {usePage} from '@inertiajs/vue3';
import {computed, reactive} from 'vue';
import type {PaginationData, SortItem} from '@/common/types';
import type {ConditionConfig} from '@/modules/elements/composables/useConditionBuilder';
import type {BulkActionItem} from '@/modules/elements/types/actions';
import type {Source, SourceItem} from '@/modules/elements/types/sources';
import type {IndexQueryParams} from '@/modules/elements/composables/useElementIndexVisits';
import type {
  SortOption,
  ViewMode,
  ViewState,
} from '@/modules/elements/types/view-state';

type GeneratedProps = CraftCms.Cms.Http.ViewModels.ContentIndexViewModel;

export interface ElementIndexRow extends IndexQueryParams {
  id: string | number;
  isFolder?: boolean;
  folderUrl?: string;
  folderId?: string | number;
  canMoveTo?: boolean;
}

/**
 * The `ContentIndexViewModel` payload, with the element-index domain types
 * layered over the keys the PHP type generator can only express loosely
 * (`Array<any>`). The generated type stays the source of truth for which keys
 * exist; this narrows what they contain.
 */
export type ContentIndexData = Omit<
  GeneratedProps,
  | 'source'
  | 'sources'
  | 'currentCondition'
  | 'viewState'
  | 'viewModes'
  | 'sortOptions'
  | 'sort'
  | 'data'
  | 'actions'
  | 'pagination'
> & {
  source: SourceItem | null;
  sources: Source[];
  currentCondition: ConditionConfig | null;
  viewState: Partial<ViewState>;
  viewModes: ViewMode[];
  sortOptions: SortOption[];
  sort: SortItem[];
  data: ElementIndexRow[];
  actions: BulkActionItem[] | null;
  pagination: PaginationData;
};

/**
 * Typed, reactive access to the `ContentIndexViewModel` payload shared by
 * every element index screen (entries, assets, …).
 *
 * Returns a reactive object shaped like the payload, so it can be handed
 * directly to the `useElementIndex*` composables in place of a props object,
 * while staying in sync across partial Inertia visits (sorting, filtering,
 * pagination). Read keys off the returned object (or `toRef()` them) —
 * destructuring would snapshot the current values.
 *
 * `extra` merges page-supplied data (refs unwrap reactively) into the same
 * object — e.g. route-param props or local state an embedded index owns.
 * Extra keys win over payload keys, so a caller can also deliberately
 * override a payload value.
 */
export function useContentIndexData<
  Extra extends object = Record<never, never>,
>(extra?: Extra) {
  const inertiaPage = usePage<ContentIndexData>();

  const data = reactive({
    // Element type
    elementType: computed(() => inertiaPage.props.elementType),
    elementDisplayName: computed(() => inertiaPage.props.elementDisplayName),
    elementPluralDisplayName: computed(
      () => inertiaPage.props.elementPluralDisplayName
    ),
    canHaveDrafts: computed(() => inertiaPage.props.canHaveDrafts),

    // Page chrome
    context: computed(() => inertiaPage.props.context),
    title: computed(() => inertiaPage.props.title),
    page: computed(() => inertiaPage.props.page),
    selectedSubnavItem: computed(() => inertiaPage.props.selectedSubnavItem),
    showSiteMenu: computed(() => inertiaPage.props.showSiteMenu),
    showStatusMenu: computed(() => inertiaPage.props.showStatusMenu),

    // Sources
    sources: computed(() => inertiaPage.props.sources),
    source: computed(() => inertiaPage.props.source),
    structure: computed(() => inertiaPage.props.structure),

    // Filtering
    status: computed(() => inertiaPage.props.status),
    statusOptions: computed(() => inertiaPage.props.statusOptions),
    search: computed(() => inertiaPage.props.search),
    currentCondition: computed(() => inertiaPage.props.currentCondition),

    // View state
    viewState: computed(() => inertiaPage.props.viewState),
    viewModes: computed(() => inertiaPage.props.viewModes),
    tableColumns: computed(() => inertiaPage.props.tableColumns),
    defaultTableColumns: computed(() => inertiaPage.props.defaultTableColumns),
    sort: computed(() => inertiaPage.props.sort),
    sortOptions: computed(() => inertiaPage.props.sortOptions),

    // Results
    data: computed(() => inertiaPage.props.data),
    actions: computed(() => inertiaPage.props.actions),
    pagination: computed(() => inertiaPage.props.pagination),
  });

  return Object.assign(data, extra);
}
