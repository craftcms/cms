import {computed, type MaybeRefOrGetter, toValue} from 'vue';
import {
  type SelectableId,
  useSelectable,
  type SelectableOptions,
} from '@/common/composables/useSelectable';

/**
 * The view modes a list of elements can be shown in — the same set
 * `BaseRelationField::supportedViewModes()` offers, and what the element index
 * switches between.
 */
export type ElementListViewMode =
  | 'list'
  | 'list-inline'
  | 'thumbs'
  | 'cards'
  | 'cards-grid';

export interface ElementListOptions<Id extends SelectableId = SelectableId> {
  /** The element ids, in display order. */
  ids: MaybeRefOrGetter<readonly Id[]>;
  viewMode: MaybeRefOrGetter<ElementListViewMode>;
  /** Whether the list offers selection at all. */
  selectable?: MaybeRefOrGetter<boolean>;
  readOnly?: MaybeRefOrGetter<boolean>;
  /** Passed through to {@link useSelectable}; see its `click` option. */
  click?: SelectableOptions<Id>['click'];
  canSelect?: SelectableOptions<Id>['canSelect'];
  /** Somewhere else to keep the selection — a table's row state, say. */
  store?: SelectableOptions<Id>['store'];
}

/**
 * Everything a list of elements needs regardless of how it's drawn: the active
 * view mode, the flags that pick a body for it, and a selection.
 *
 * This is the seam a relation field, the element index, or a third-party list
 * shares. Pair it with `ElementList` for the batteries-included version, or use
 * it directly and render whatever you like.
 */
export function useElementList<Id extends SelectableId = SelectableId>(
  options: ElementListOptions<Id>
) {
  const viewMode = computed(() => toValue(options.viewMode));
  const selectable = computed(() => toValue(options.selectable ?? true));
  const readOnly = computed(() => toValue(options.readOnly ?? false));

  /** Cards carry their own server-rendered markup; the grid just lays them out. */
  const isCards = computed(
    () => viewMode.value === 'cards' || viewMode.value === 'cards-grid'
  );
  const isCardGrid = computed(() => viewMode.value === 'cards-grid');
  const isThumbs = computed(() => viewMode.value === 'thumbs');
  /** Both list modes draw chips; only the arrangement differs. */
  const isList = computed(
    () => viewMode.value === 'list' || viewMode.value === 'list-inline'
  );
  const isInline = computed(() => viewMode.value === 'list-inline');

  const selection = useSelectable<Id>({
    ids: options.ids,
    enabled: selectable,
    readOnly,
    click: options.click,
    canSelect: options.canSelect,
    store: options.store,
  });

  return {
    viewMode,
    isCards,
    isCardGrid,
    isThumbs,
    isList,
    isInline,
    selectable,
    readOnly,
    selection,
  };
}

/** The object {@link useElementList} returns. */
export type ElementList<Id extends SelectableId = SelectableId> = ReturnType<
  typeof useElementList<Id>
>;
