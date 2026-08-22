import {usePage} from '@inertiajs/vue3';
import {computed, reactive, toValue, type MaybeRefOrGetter} from 'vue';
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
 *
 * `source` replaces the Inertia page as where the payload is read from, for
 * indexes that aren't a page at all. The keys and their reactivity are the
 * same either way, so the `useElementIndex*` composables can't tell which.
 */
export function useContentIndexData<
  Extra extends object = Record<never, never>,
>(extra?: Extra, source?: MaybeRefOrGetter<ContentIndexData>) {
  // Indexes that aren't an Inertia page — the element selector modal, which
  // fetches the same payload over XHR — pass their own source. `usePage()` is
  // only called when none is given, since it needs an Inertia app to read.
  const inertiaPage = source === undefined ? usePage<ContentIndexData>() : null;
  const props = (): ContentIndexData =>
    source === undefined ? inertiaPage!.props : toValue(source);

  const data = reactive({
    // Element type
    elementType: computed(() => props().elementType),
    elementDisplayName: computed(() => props().elementDisplayName),
    elementPluralDisplayName: computed(() => props().elementPluralDisplayName),
    canHaveDrafts: computed(() => props().canHaveDrafts),

    // Page chrome
    context: computed(() => props().context),
    title: computed(() => props().title),
    page: computed(() => props().page),
    selectedSubnavItem: computed(() => props().selectedSubnavItem),
    showSiteMenu: computed(() => props().showSiteMenu),
    showStatusMenu: computed(() => props().showStatusMenu),

    // Sources
    sources: computed(() => props().sources),
    source: computed(() => props().source),
    structure: computed(() => props().structure),

    // Filtering
    status: computed(() => props().status),
    statusOptions: computed(() => props().statusOptions),
    search: computed(() => props().search),
    currentCondition: computed(() => props().currentCondition),

    // View state
    viewState: computed(() => props().viewState),
    viewModes: computed(() => props().viewModes),
    tableColumns: computed(() => props().tableColumns),
    defaultTableColumns: computed(() => props().defaultTableColumns),
    sort: computed(() => props().sort),
    sortOptions: computed(() => props().sortOptions),

    // Results
    data: computed(() => props().data),
    actions: computed(() => props().actions),
    pagination: computed(() => props().pagination),

    // Merged into the literal rather than `Object.assign`ed onto the result:
    // every key above is a `computed()` ref, and assigning over one after
    // `reactive()` has wrapped it does not take — `extra` would silently lose
    // to the payload, which is the opposite of the documented precedence.
    ...((extra ?? {}) as Extra),
  });

  return data;
}
