import {ref} from 'vue';
import {describe, expect, it} from 'vite-plus/test';
import {useSelectable} from './useSelectable';

/** A click on the item body — no interactive control in the path. */
function clickEvent(init: MouseEventInit = {}): MouseEvent {
  const target = document.createElement('div');
  const event = new MouseEvent('click', init);

  Object.defineProperty(event, 'composedPath', {value: () => [target]});
  Object.defineProperty(event, 'currentTarget', {value: target});

  return event;
}

/** A click that started on a link inside the item. */
function linkClickEvent(init: MouseEventInit = {}): MouseEvent {
  const boundary = document.createElement('div');
  const link = document.createElement('a');
  link.href = '#';
  const event = new MouseEvent('click', init);

  Object.defineProperty(event, 'composedPath', {value: () => [link, boundary]});
  Object.defineProperty(event, 'currentTarget', {value: boundary});

  return event;
}

describe('useSelectable', () => {
  it('selects a single id and sets the anchor', () => {
    const selection = useSelectable({ids: [1, 2, 3]});

    selection.select(2, true);

    expect(selection.selectedIds.value).toEqual([2]);
    expect(selection.anchorIndex.value).toBe(1);
    expect(selection.hasSelection.value).toBe(true);
  });

  it('toggles an id off again', () => {
    const selection = useSelectable({ids: [1, 2, 3]});

    selection.toggle(2);
    selection.toggle(2);

    expect(selection.selectedIds.value).toEqual([]);
    expect(selection.hasSelection.value).toBe(false);
  });

  it('selects the inclusive range from the anchor, in either direction', () => {
    const selection = useSelectable({ids: [1, 2, 3, 4, 5]});

    selection.select(4, true);
    selection.selectRange(2, true);

    expect([...selection.selectedIds.value].sort((a, b) => a - b)).toEqual([
      2, 3, 4,
    ]);
    // The anchor survives a range select, so it can be re-dragged.
    expect(selection.anchorIndex.value).toBe(3);
  });

  it('deselects across a range', () => {
    const selection = useSelectable({ids: [1, 2, 3]});

    selection.selectAll(true);
    selection.select(1, true);
    selection.selectRange(3, false);

    expect(selection.selectedIds.value).toEqual([]);
  });

  it('treats a range with no anchor as just the clicked id', () => {
    const selection = useSelectable({ids: [1, 2, 3]});

    selection.selectRange(2, true);

    expect(selection.selectedIds.value).toEqual([2]);
  });

  it('extends the selection without dropping what is already selected', () => {
    const selection = useSelectable({ids: [1, 2, 3, 4]});

    selection.select(1, true);
    selection.extendTo(3);

    expect([...selection.selectedIds.value].sort((a, b) => a - b)).toEqual([
      1, 2, 3,
    ]);
  });

  it('selects and clears everything', () => {
    const selection = useSelectable({ids: [1, 2, 3]});

    selection.selectAll(true);
    expect(selection.selectedIds.value).toHaveLength(3);

    selection.clear();
    expect(selection.selectedIds.value).toEqual([]);
    expect(selection.anchorIndex.value).toBeNull();
  });

  it('prunes ids that have left the list', () => {
    const ids = ref<number[]>([1, 2, 3]);
    const selection = useSelectable({ids});

    selection.selectAll(true);
    ids.value = [1, 3];
    selection.prune();

    expect([...selection.selectedIds.value].sort((a, b) => a - b)).toEqual([
      1, 3,
    ]);
    expect(selection.anchorIndex.value).toBeNull();
  });

  it('prunes against a list that has not landed yet', () => {
    const ids = ref<number[]>([1, 2, 3]);
    const selection = useSelectable({ids});

    selection.selectAll(true);
    // `ids` still holds the outgoing item, as it does mid-removal.
    selection.prune([1, 3]);

    expect([...selection.selectedIds.value].sort((a, b) => a - b)).toEqual([
      1, 3,
    ]);
  });

  it('refuses writes when read-only', () => {
    const selection = useSelectable({ids: [1, 2, 3], readOnly: true});

    selection.select(1, true);
    selection.selectAll(true);
    selection.handleClick(2, clickEvent());

    expect(selection.selectedIds.value).toEqual([]);
  });

  it('respects a per-item veto', () => {
    const selection = useSelectable({
      ids: [1, 2, 3],
      canSelect: (id) => id !== 2,
    });

    selection.select(2, true);
    expect(selection.selectedIds.value).toEqual([]);

    selection.selectAll(true);
    expect([...selection.selectedIds.value].sort((a, b) => a - b)).toEqual([
      1, 3,
    ]);
  });

  describe('click gestures', () => {
    it('replaces the selection on a plain click in replace mode', () => {
      const selection = useSelectable({ids: [1, 2, 3], click: 'replace'});

      selection.handleClick(1, clickEvent());
      selection.handleClick(3, clickEvent());

      expect(selection.selectedIds.value).toEqual([3]);
    });

    it('toggles just the clicked id in toggle mode', () => {
      const selection = useSelectable({ids: [1, 2, 3], click: 'toggle'});

      selection.handleClick(1, clickEvent());
      selection.handleClick(3, clickEvent());

      expect([...selection.selectedIds.value].sort((a, b) => a - b)).toEqual([
        1, 3,
      ]);
    });

    it('adds to the selection on a ctrl/cmd click in replace mode', () => {
      const selection = useSelectable({ids: [1, 2, 3], click: 'replace'});

      selection.handleClick(1, clickEvent());
      selection.handleClick(3, clickEvent({metaKey: true}));

      expect([...selection.selectedIds.value].sort((a, b) => a - b)).toEqual([
        1, 3,
      ]);
    });

    it('selects the range on a shift click', () => {
      const selection = useSelectable({ids: [1, 2, 3, 4], click: 'replace'});

      selection.handleClick(1, clickEvent());
      selection.handleClick(3, clickEvent({shiftKey: true}));

      expect([...selection.selectedIds.value].sort((a, b) => a - b)).toEqual([
        1, 2, 3,
      ]);
    });

    it('ignores a click that landed on a control inside the item', () => {
      const selection = useSelectable({ids: [1, 2, 3]});

      selection.handleClick(1, linkClickEvent());

      expect(selection.selectedIds.value).toEqual([]);
    });

    it('does nothing when selection is disabled', () => {
      const selection = useSelectable({ids: [1, 2, 3], enabled: false});

      selection.handleClick(1, clickEvent());

      expect(selection.selectedIds.value).toEqual([]);
    });

    it('ignores a click for an id that is not in the list', () => {
      const selection = useSelectable<number>({ids: [1, 2, 3]});

      selection.handleClick(9, clickEvent());

      expect(selection.selectedIds.value).toEqual([]);
    });
  });

  describe('with an external store', () => {
    it('reads and writes through the supplied store', () => {
      const backing = ref<Set<number>>(new Set());
      const selection = useSelectable<number>({
        ids: [1, 2, 3],
        store: {
          isSelected: (id) => backing.value.has(id),
          setSelected(id, selected) {
            const next = new Set(backing.value);

            if (selected) {
              next.add(id);
            } else {
              next.delete(id);
            }

            backing.value = next;
          },
          selectedIds: () => [...backing.value],
          clear: () => (backing.value = new Set()),
        },
      });

      selection.select(1, true);
      selection.extendTo(3);

      expect([...backing.value].sort((a, b) => a - b)).toEqual([1, 2, 3]);
      expect([...selection.selectedIds.value].sort((a, b) => a - b)).toEqual([
        1, 2, 3,
      ]);
    });
  });
  describe('setChecked', () => {
    it('selects and deselects from a checkbox', () => {
      const selection = useSelectable({ids: [1, 2, 3]});

      selection.setChecked(2, true);
      expect(selection.selectedIds.value).toEqual([2]);

      selection.setChecked(2, false);
      expect(selection.selectedIds.value).toEqual([]);
    });

    // Lion re-fires `model-value-changed` when `.checked` is set programmatically.
    it('ignores a re-fire whose value already matches, leaving the anchor put', () => {
      const selection = useSelectable({ids: [1, 2, 3]});

      selection.setChecked(3, true);
      selection.setChecked(1, false); // already unselected → no-op

      expect(selection.selectedIds.value).toEqual([3]);
      expect(selection.anchorIndex.value).toBe(2);
    });

    it('applies the range from the anchor when shift was held', () => {
      const selection = useSelectable({ids: [1, 2, 3, 4]});

      selection.setChecked(1, true);
      selection.setChecked(3, true, {shiftKey: true});

      expect([...selection.selectedIds.value].sort((a, b) => a - b)).toEqual([
        1, 2, 3,
      ]);
      expect(selection.anchorIndex.value).toBe(0);
    });

    it('falls back to a plain change when shift is held with no anchor', () => {
      const selection = useSelectable({ids: [1, 2, 3]});

      selection.setChecked(2, true, {shiftKey: true});

      expect(selection.selectedIds.value).toEqual([2]);
    });

    it('refuses when read-only', () => {
      const selection = useSelectable({ids: [1, 2, 3], readOnly: true});

      selection.setChecked(2, true);

      expect(selection.selectedIds.value).toEqual([]);
    });
  });
  describe('select-all state', () => {
    it('reports all and some as the selection grows', () => {
      const selection = useSelectable({ids: [1, 2, 3]});

      expect(selection.allSelected.value).toBe(false);
      expect(selection.someSelected.value).toBe(false);

      selection.select(1, true);
      expect(selection.allSelected.value).toBe(false);
      expect(selection.someSelected.value).toBe(true);

      selection.selectAll(true);
      expect(selection.allSelected.value).toBe(true);
      expect(selection.someSelected.value).toBe(false);
    });

    it('is not all-selected when the list is empty', () => {
      const selection = useSelectable<number>({ids: []});

      expect(selection.allSelected.value).toBe(false);
    });

    // A vetoed item can never be selected, so it can't hold `allSelected` back.
    it('ignores vetoed items when deciding all-selected', () => {
      const selection = useSelectable({
        ids: [1, 2, 3],
        canSelect: (id) => id !== 2,
      });

      selection.selectAll(true);

      expect([...selection.selectedIds.value].sort((a, b) => a - b)).toEqual([
        1, 3,
      ]);
      expect(selection.allSelected.value).toBe(true);
    });

    it('ignores a toggleAll whose value already matches', () => {
      const selection = useSelectable({ids: [1, 2, 3]});

      selection.toggleAll(false); // already none selected → no-op
      expect(selection.selectedIds.value).toEqual([]);

      selection.toggleAll(true);
      expect(selection.selectedIds.value).toHaveLength(3);
    });

    it('refuses toggleAll when read-only', () => {
      const selection = useSelectable({ids: [1, 2, 3], readOnly: true});

      selection.toggleAll(true);

      expect(selection.selectedIds.value).toEqual([]);
    });
  });
});
