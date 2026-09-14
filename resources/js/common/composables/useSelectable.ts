import {computed, type MaybeRefOrGetter, ref, type Ref, toValue} from 'vue';
import {isInteractiveClick} from '@/common/utils/dom';

export type SelectableId = string | number;

/**
 * Where the selection actually lives.
 *
 * The default store keeps it in a local `Set`, which is what a plain list wants.
 * Consumers that already have somewhere authoritative to put it — a TanStack
 * table's row-selection state, say — pass their own so this composable stays the
 * selection *algorithm* (anchors, ranges, click gestures) without also trying to
 * own the state.
 */
export interface SelectableStore<Id extends SelectableId = SelectableId> {
  isSelected(id: Id): boolean;
  setSelected(id: Id, selected: boolean): void;
  selectedIds(): readonly Id[];
  clear(): void;
}

export interface SelectableOptions<Id extends SelectableId = SelectableId> {
  /**
   * The ids in display order. Ranges walk this, so it has to match what the
   * user sees — the sorted/paged order, not the underlying data order.
   */
  ids: MaybeRefOrGetter<readonly Id[]>;
  /** Whether selection is offered at all. */
  enabled?: MaybeRefOrGetter<boolean>;
  /** Whether selection is frozen — reads still work, writes are ignored. */
  readOnly?: MaybeRefOrGetter<boolean>;
  /** Per-item veto, for rows the caller won't let the user select. */
  canSelect?: (id: Id) => boolean;
  /**
   * What an unmodified click does: `replace` collapses the selection to the
   * clicked item (chips, single-select lists), `toggle` flips just that item and
   * leaves the rest alone (checkbox-driven lists).
   */
  click?: MaybeRefOrGetter<'replace' | 'toggle'>;
  /** Override where the selection is stored. Defaults to a local `Set`. */
  store?: SelectableStore<Id>;
}

function localStore<Id extends SelectableId>(): SelectableStore<Id> {
  const selected: Ref<Set<Id>> = ref(new Set()) as Ref<Set<Id>>;

  return {
    isSelected: (id) => selected.value.has(id),
    setSelected(id, isSelected) {
      // Replace the Set rather than mutate it: a `ref` only notifies on
      // assignment, so in-place `add`/`delete` would not re-render.
      const next = new Set(selected.value);

      if (isSelected) {
        next.add(id);
      } else {
        next.delete(id);
      }

      selected.value = next;
    },
    selectedIds: () => [...selected.value],
    clear() {
      selected.value = new Set();
    },
  };
}

/**
 * Anchor-based list selection: click, ctrl/cmd-click, and shift-click ranges,
 * for any ordered list of ids.
 *
 * The anchor is the last individually-toggled item; a shift-click applies to the
 * inclusive range between it and the clicked item, and leaves the anchor where it
 * was so the range can be re-dragged from the same origin.
 */
export function useSelectable<Id extends SelectableId = SelectableId>(
  options: SelectableOptions<Id>
) {
  const store = options.store ?? localStore<Id>();
  const anchorIndex: Ref<number | null> = ref(null);

  const ids = computed<readonly Id[]>(() => toValue(options.ids));
  const enabled = computed(() => toValue(options.enabled ?? true));
  const readOnly = computed(() => toValue(options.readOnly ?? false));
  const click = computed(() => toValue(options.click ?? 'toggle'));

  const selectedIds = computed<readonly Id[]>(() => store.selectedIds());
  const hasSelection = computed(() => selectedIds.value.length > 0);

  /** Everything selectable is selected — the select-all checkbox's checked state. */
  const allSelected = computed(
    () =>
      ids.value.length > 0 &&
      ids.value.every((id) => !canSelectable(id) || store.isSelected(id))
  );
  /** Some but not all — the select-all checkbox's indeterminate state. */
  const someSelected = computed(
    () => !allSelected.value && ids.value.some((id) => store.isSelected(id))
  );

  /** Whether the item itself is selectable, ignoring whether writes are frozen. */
  function canSelectable(id: Id): boolean {
    return options.canSelect?.(id) ?? true;
  }

  /** Writes are refused when frozen or when the item itself is off-limits. */
  function canWrite(id?: Id): boolean {
    if (readOnly.value) return false;

    return id === undefined || canSelectable(id);
  }

  function isSelected(id: Id): boolean {
    return store.isSelected(id);
  }

  function indexOf(id: Id): number {
    return ids.value.indexOf(id);
  }

  function select(id: Id, selected: boolean): void {
    if (!canWrite(id)) return;

    store.setSelected(id, selected);
    anchorIndex.value = indexOf(id);
  }

  function toggle(id: Id): void {
    select(id, !isSelected(id));
  }

  /**
   * Applies `selected` across the inclusive range between the anchor and `id`,
   * keeping the anchor put. With no anchor yet, the range is just `id`.
   */
  function selectRange(id: Id, selected: boolean): void {
    if (!canWrite()) return;

    const index = indexOf(id);
    if (index === -1) return;

    const from = anchorIndex.value ?? index;
    const [start, end] = from <= index ? [from, index] : [index, from];

    for (let i = start; i <= end; i++) {
      const rangeId = ids.value[i];
      if (rangeId !== undefined && canWrite(rangeId)) {
        store.setSelected(rangeId, selected);
      }
    }
  }

  /** Grows the selection to `id` without dropping what is already selected. */
  function extendTo(id: Id): void {
    selectRange(id, true);
  }

  /**
   * A checkbox reporting its new state.
   *
   * Two things make this different from {@link select}. Lion's `craft-checkbox`
   * re-fires `model-value-changed` on programmatic `.checked` updates too, so a
   * value that already matches is ignored rather than pointlessly moving the
   * anchor. And modifier keys never reach that event, so `shiftKey` has to be
   * captured from the native click and handed in for range selection to work.
   */
  function setChecked(id: Id, checked: boolean, {shiftKey = false} = {}): void {
    if (!canWrite(id)) return;

    if (shiftKey && anchorIndex.value !== null) {
      // The anchor is preserved across a range select.
      selectRange(id, checked);
      return;
    }

    if (checked === isSelected(id)) return;
    select(id, checked);
  }

  /**
   * A select-all checkbox reporting a new state. Like {@link setChecked}, a
   * value that already matches is ignored, so Lion's programmatic re-fires
   * don't churn the whole list.
   */
  function toggleAll(checked: boolean): void {
    if (!canWrite() || checked === allSelected.value) return;

    selectAll(checked);
  }

  function selectAll(selected: boolean): void {
    if (!canWrite()) return;

    for (const id of ids.value) {
      if (canWrite(id)) store.setSelected(id, selected);
    }
  }

  function clear(): void {
    store.clear();
    anchorIndex.value = null;
  }

  /**
   * Drops ids that have left the list, so the selection can't go stale as items
   * are removed. The anchor goes with them — its index no longer means anything.
   *
   * Pass `available` to prune against a list that hasn't landed yet: a removal
   * that emits the new value and prunes in the same breath is working ahead of
   * the round-trip, so `ids` still holds the outgoing items.
   */
  function prune(available: readonly Id[] = ids.value): void {
    const kept = new Set(available);

    for (const id of selectedIds.value) {
      if (!kept.has(id)) store.setSelected(id, false);
    }

    anchorIndex.value = null;
  }

  /**
   * Turns a click on a list item into the matching selection change.
   *
   * Clicks that land on a control inside the item — a link, a button, an action
   * menu — belong to that control, so they're left alone. `boundary` is the
   * element the handler is bound to, and stops the walk outward.
   */
  function handleClick(
    id: Id,
    event: MouseEvent,
    boundary?: EventTarget | null
  ): void {
    if (!enabled.value || !canWrite(id)) return;
    if (isInteractiveClick(event, boundary ?? event.currentTarget)) return;
    if (indexOf(id) === -1) return;

    if (event.shiftKey && anchorIndex.value !== null) {
      selectRange(id, click.value === 'toggle' ? !isSelected(id) : true);
    } else if (event.metaKey || event.ctrlKey || click.value === 'toggle') {
      toggle(id);
    } else {
      store.clear();
      select(id, true);
    }

    // A shift-click extends the browser's text selection too; drop it so a range
    // select doesn't leave stray highlighted text behind.
    if (event.shiftKey) {
      window.getSelection?.()?.removeAllRanges();
    }
  }

  return {
    selectedIds,
    hasSelection,
    allSelected,
    someSelected,
    anchorIndex,
    enabled,
    readOnly,
    isSelected,
    canSelect: canSelectable,
    indexOf,
    select,
    toggle,
    selectRange,
    extendTo,
    setChecked,
    selectAll,
    toggleAll,
    clear,
    prune,
    handleClick,
  };
}

/** The object {@link useSelectable} returns — what a selectable list accepts. */
export type Selectable<Id extends SelectableId = SelectableId> = ReturnType<
  typeof useSelectable<Id>
>;
