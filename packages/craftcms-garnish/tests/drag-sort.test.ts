import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';

import {DragSort} from '../src/drag/drag-sort';
import {X_AXIS, Y_AXIS} from '../src/constants';
import {globals} from '../src/globals';

// happy-dom notes (see doc 11 §6):
// - No layout: `getBoundingClientRect()` returns zeros and `offsetWidth/Height`
//   are 0. We mock `getBoundingClientRect` where midpoint math matters.
// - `element.animate` is `undefined`, so `returnHelpersToDraggees` (called by
//   DragSort.onDragStop) takes the synchronous no-WAAPI fallback.
// - The RAF-deferred hooks + the lag-follow loop are deferred onto a manual
//   queue (a synchronous stub would recurse forever via the lag loop). Tests
//   flush the queue explicitly with `flushRaf()`.
vi.mock('../src/utils/animation', async (importOriginal) => {
  const actual = await importOriginal<object>();
  return {
    ...actual,
    requestAnimationFrame: (cb: FrameRequestCallback): number => {
      rafQueue.push(cb);
      return rafQueue.length;
    },
    cancelAnimationFrame: (handle: number): void => {
      if (handle > 0 && handle <= rafQueue.length) {
        rafQueue[handle - 1] = undefined;
      }
    },
  };
});

let rafQueue: Array<FrameRequestCallback | undefined> = [];

/** Run every currently-queued RAF callback once (newly-scheduled ones defer). */
function flushRaf(): void {
  const current = rafQueue;
  rafQueue = [];
  for (const cb of current) {
    cb?.(0);
  }
}

/**
 * Private-method shape used to `vi.spyOn`/read DragSort internals. `vi.spyOn(d
 * as never, ...)` typechecks for reads but not for `.mockImplementation`, so we
 * cast through this when stubbing.
 */
interface DragSortPrivate {
  _updateInsertion: () => void;
  _getClosestItem: () => HTMLElement | null;
  _precalculateMidpoints: () => void;
  _getDraggeeIndexes: () => number[];
  _moveDraggeeToItem: (item: HTMLElement) => void;
  _placeInsertionWithDraggee: () => void;
  _removeInsertion: () => void;
  _allMidpoints: Map<Element, unknown> | null;
}

function makeItem(cls = ''): HTMLElement {
  const el = document.createElement('div');
  if (cls) el.className = cls;
  document.body.appendChild(el);
  return el;
}

/** Dispatch a PointerEvent with page coords overridden (happy-dom zeros them). */
function firePointer(
  el: EventTarget,
  type: string,
  opts: {
    pageX?: number;
    pageY?: number;
    pointerId?: number;
    button?: number;
  } = {}
): void {
  const ev = new PointerEvent(type, {bubbles: true, cancelable: true} as never);
  Object.defineProperty(ev, 'pageX', {
    value: opts.pageX ?? 0,
    configurable: true,
  });
  Object.defineProperty(ev, 'pageY', {
    value: opts.pageY ?? 0,
    configurable: true,
  });
  Object.defineProperty(ev, 'pointerId', {
    value: opts.pointerId ?? 1,
    configurable: true,
  });
  Object.defineProperty(ev, 'button', {
    value: opts.button ?? 0,
    configurable: true,
  });
  el.dispatchEvent(ev);
}

/** Stub an element's bounding rect so midpoint/hit-test math reads a known box. */
function mockRect(
  el: Element,
  top: number,
  left: number,
  width = 0,
  height = 0
): void {
  vi.spyOn(el, 'getBoundingClientRect').mockReturnValue({
    top,
    left,
    right: left + width,
    bottom: top + height,
    width,
    height,
    x: left,
    y: top,
    toJSON: () => ({}),
  } as DOMRect);
}

beforeEach(() => {
  document.body.innerHTML = '';
  document.body.className = '';
  rafQueue = [];
  globals.rtl = false;
  globals.activateEventsMuted = false;
});

afterEach(() => {
  vi.restoreAllMocks();
});

// ---------------------------------------------------------------------------
// Settings + defaults
// ---------------------------------------------------------------------------

describe('DragSort settings + defaults', () => {
  it('exposes the documented DragSort defaults', () => {
    expect(DragSort.defaults.container).toBeNull();
    expect(DragSort.defaults.insertion).toBeNull();
    expect(DragSort.defaults.moveTargetItemToFront).toBe(false);
    expect(DragSort.defaults.magnetStrength).toBe(1);
    expect(DragSort.defaults.canInsertBefore(makeItem())).toBe(true);
    expect(DragSort.defaults.canInsertAfter(makeItem())).toBe(true);
  });

  it('merges DragSort.defaults over Drag/BaseDrag defaults', () => {
    const ds = new DragSort(null, {magnetStrength: 4, minMouseDist: 9});
    expect(ds.settings!.magnetStrength).toBe(4);
    expect(ds.settings!.minMouseDist).toBe(9);
    // Inherited Drag default.
    expect(ds.settings!.hideDraggee).toBe(true);
    // Inherited BaseDrag default.
    expect(ds.settings!.ignoreHandleSelector).toBe(
      'input, textarea, button, select, .btn'
    );
  });

  it('supports the param-shift form: new DragSort(settingsObj)', () => {
    const ds = new DragSort({moveTargetItemToFront: true});
    expect(ds.settings!.moveTargetItemToFront).toBe(true);
    expect(ds.$items).toEqual([]);
  });

  it('starts with empty insertion/sort state', () => {
    const ds = new DragSort();
    expect(ds.$insertion).toBeNull();
    expect(ds.insertionVisible).toBe(false);
    expect(ds.closestItem).toBeNull();
    expect(ds.oldDraggeeIndexes).toBeNull();
  });
});

// ---------------------------------------------------------------------------
// createInsertion
// ---------------------------------------------------------------------------

describe('DragSort.createInsertion', () => {
  it('returns null when no insertion is configured', () => {
    const ds = new DragSort();
    expect(ds.createInsertion()).toBeNull();
  });

  it('invokes a function insertion with the draggee set', () => {
    const el = document.createElement('li');
    const fn = vi.fn(() => el);
    const a = makeItem();
    const ds = new DragSort([a], {insertion: fn});
    ds.$draggee = [a];
    expect(ds.createInsertion()).toBe(el);
    expect(fn).toHaveBeenCalledWith([a]);
  });

  it('parses an HTML-string insertion into an element', () => {
    const ds = new DragSort(null, {insertion: '<li class="ins">x</li>'});
    const el = ds.createInsertion();
    expect(el).not.toBeNull();
    expect(el!.tagName).toBe('LI');
    expect(el!.classList.contains('ins')).toBe(true);
  });

  it('passes an element insertion through unchanged', () => {
    const el = document.createElement('div');
    const ds = new DragSort(null, {insertion: el});
    expect(ds.createInsertion()).toBe(el);
  });
});

// ---------------------------------------------------------------------------
// canInsertBefore / canInsertAfter
// ---------------------------------------------------------------------------

describe('DragSort.canInsertBefore / canInsertAfter', () => {
  it('delegate to the settings callbacks', () => {
    const item = makeItem();
    const before = vi.fn(() => false);
    const after = vi.fn(() => true);
    const ds = new DragSort(null, {
      canInsertBefore: before,
      canInsertAfter: after,
    });
    expect(ds.canInsertBefore(item)).toBe(false);
    expect(ds.canInsertAfter(item)).toBe(true);
    expect(before).toHaveBeenCalledWith(item);
    expect(after).toHaveBeenCalledWith(item);
  });
});

// ---------------------------------------------------------------------------
// _getDraggeeIndexes
// ---------------------------------------------------------------------------

describe('DragSort draggee indexes', () => {
  it('maps each draggee to its index within $items', () => {
    const a = makeItem();
    const b = makeItem();
    const c = makeItem();
    const ds = new DragSort([a, b, c]);
    ds.$draggee = [c, a];
    expect((ds as unknown as DragSortPrivate)._getDraggeeIndexes()).toEqual([
      2, 0,
    ]);
  });
});

// ---------------------------------------------------------------------------
// _getClosestItem — spatial hit-test math
// ---------------------------------------------------------------------------

describe('DragSort._getClosestItem', () => {
  /** A 4-item vertical list (each 20px tall) with `b` as the dragged item. */
  function verticalList(settings = {}): {
    ds: DragSort;
    items: HTMLElement[];
  } {
    const [a, b, c, d] = [makeItem(), makeItem(), makeItem(), makeItem()];
    // midpoints y: a=10, b=30, c=50, d=70 (top + height/2, height 20); x all 10.
    mockRect(a, 0, 0, 20, 20);
    mockRect(b, 20, 0, 20, 20);
    mockRect(c, 40, 0, 20, 20);
    mockRect(d, 60, 0, 20, 20);
    const ds = new DragSort([a, b, c, d], settings);
    ds.$draggee = [b];
    (ds as unknown as DragSortPrivate)._precalculateMidpoints();
    return {ds, items: [a, b, c, d]};
  }

  it('returns the nearest insertable item (and ignores the draggee itself)', () => {
    const {ds, items} = verticalList();
    const [, , c] = items;
    ds.draggeeVirtualMidpointX = 10;
    ds.draggeeVirtualMidpointY = 55; // closest to c (mid 50)
    expect((ds as unknown as DragSortPrivate)._getClosestItem()).toBe(c);
  });

  it('finds an item above the draggee when the cursor moves up', () => {
    const {ds, items} = verticalList();
    const [a] = items;
    ds.draggeeVirtualMidpointX = 10;
    ds.draggeeVirtualMidpointY = 8; // closest to a (mid 10)
    expect((ds as unknown as DragSortPrivate)._getClosestItem()).toBe(a);
  });

  it('returns null when no item is insertable (only the draggee is closest)', () => {
    const {ds} = verticalList({
      canInsertBefore: () => false,
      canInsertAfter: () => false,
    });
    ds.draggeeVirtualMidpointX = 10;
    ds.draggeeVirtualMidpointY = 55;
    // Every non-draggee item is gated out, so the seed (draggee) wins → null.
    expect((ds as unknown as DragSortPrivate)._getClosestItem()).toBeNull();
  });

  it('uses only the Y distance under axis: y', () => {
    const {ds, items} = verticalList({axis: Y_AXIS});
    const [, , c] = items;
    // A wildly different X must not matter when axis-locked to Y.
    ds.draggeeVirtualMidpointX = 9999;
    ds.draggeeVirtualMidpointY = 52;
    expect((ds as unknown as DragSortPrivate)._getClosestItem()).toBe(c);
  });
});

// ---------------------------------------------------------------------------
// Insertion placement + DOM reorder
// ---------------------------------------------------------------------------

describe('DragSort insertion placement', () => {
  it('_placeInsertionWithDraggee inserts before the first draggee and marks visible', () => {
    const a = makeItem();
    const ds = new DragSort([a]);
    ds.$draggee = [a];
    ds.$insertion = document.createElement('div');
    (ds as unknown as DragSortPrivate)._placeInsertionWithDraggee();
    expect(ds.insertionVisible).toBe(true);
    expect(a.previousSibling).toBe(ds.$insertion);
  });

  it('_removeInsertion pulls the placeholder out and clears the flag', () => {
    const a = makeItem();
    const ds = new DragSort([a]);
    ds.$draggee = [a];
    ds.$insertion = document.createElement('div');
    (ds as unknown as DragSortPrivate)._placeInsertionWithDraggee();
    (ds as unknown as DragSortPrivate)._removeInsertion();
    expect(ds.insertionVisible).toBe(false);
    expect(ds.$insertion.parentNode).toBeNull();
  });

  it('_moveDraggeeToItem inserts the draggee after a later sibling (going down)', () => {
    const container = document.createElement('div');
    document.body.appendChild(container);
    const a = document.createElement('div');
    const b = document.createElement('div');
    const c = document.createElement('div');
    container.append(a, b, c);
    const ds = new DragSort([a, b, c]);
    ds.$draggee = [a];
    // a (index 0) → after c (index 2): goingDown branch.
    (ds as unknown as DragSortPrivate)._moveDraggeeToItem(c);
    expect(Array.from(container.children)).toEqual([b, c, a]);
  });

  it('_moveDraggeeToItem inserts the draggee before an earlier sibling (going up)', () => {
    const container = document.createElement('div');
    document.body.appendChild(container);
    const a = document.createElement('div');
    const b = document.createElement('div');
    const c = document.createElement('div');
    container.append(a, b, c);
    const ds = new DragSort([a, b, c]);
    ds.$draggee = [c];
    // c (index 2) → before a (index 0): goingUp branch.
    (ds as unknown as DragSortPrivate)._moveDraggeeToItem(a);
    expect(Array.from(container.children)).toEqual([c, a, b]);
  });
});

// ---------------------------------------------------------------------------
// onDragStart
// ---------------------------------------------------------------------------

describe('DragSort.onDragStart', () => {
  it('records old draggee indexes and precalculates midpoints', () => {
    const a = makeItem();
    const b = makeItem();
    const ds = new DragSort([a, b]);
    ds.$draggee = [b];
    ds.$targetItem = b;
    ds.onDragStart();
    expect(ds.oldDraggeeIndexes).toEqual([1]);
    expect((ds as unknown as DragSortPrivate)._allMidpoints).not.toBeNull();
    expect(ds.closestItem).toBeNull();
  });

  it('honors moveTargetItemToFront by reordering the draggee in the DOM', () => {
    const container = document.createElement('div');
    document.body.appendChild(container);
    const a = document.createElement('div');
    const b = document.createElement('div');
    const c = document.createElement('div');
    container.append(a, b, c);
    const ds = new DragSort([a, b, c], {moveTargetItemToFront: true});
    // Target c is the first draggee but sits after b in $items → move it front.
    ds.$draggee = [c, b];
    ds.$targetItem = c;
    ds.onDragStart();
    // c moved before b: DOM order a, c, b.
    expect(Array.from(container.children)).toEqual([a, c, b]);
  });

  it('creates and places the insertion when configured', () => {
    const a = makeItem();
    const ds = new DragSort([a], {insertion: '<div class="ins"></div>'});
    ds.$draggee = [a];
    ds.$targetItem = a;
    ds.onDragStart();
    expect(ds.$insertion).not.toBeNull();
    expect(ds.insertionVisible).toBe(true);
    expect(a.previousElementSibling).toBe(ds.$insertion);
  });
});

// ---------------------------------------------------------------------------
// onDrag
// ---------------------------------------------------------------------------

describe('DragSort.onDrag', () => {
  it('updates the insertion when the closest item changes', () => {
    const a = makeItem();
    const b = makeItem();
    const ds = new DragSort([a, b]);
    ds.$draggee = [a];
    ds.draggeeVirtualMidpointX = 0;
    ds.draggeeVirtualMidpointY = 0;
    vi.spyOn(ds, 'onDrag'); // keep, but stub the internals below
    vi.spyOn(
      ds as unknown as DragSortPrivate,
      '_getClosestItem'
    ).mockReturnValue(b);
    const update = vi
      .spyOn(ds as unknown as DragSortPrivate, '_updateInsertion')
      .mockImplementation(() => {});
    ds.closestItem = null;
    ds.onDrag();
    expect(ds.closestItem).toBe(b);
    expect(update).toHaveBeenCalledTimes(1);
  });

  it('does not update the insertion when the closest item is unchanged', () => {
    const a = makeItem();
    const b = makeItem();
    const ds = new DragSort([a, b]);
    ds.$draggee = [a];
    vi.spyOn(
      ds as unknown as DragSortPrivate,
      '_getClosestItem'
    ).mockReturnValue(b);
    const update = vi
      .spyOn(ds as unknown as DragSortPrivate, '_updateInsertion')
      .mockImplementation(() => {});
    ds.closestItem = b; // already on b
    ds.onDrag();
    expect(update).not.toHaveBeenCalled();
  });

  it('clears the closest item + insertion when the cursor leaves the container', () => {
    const a = makeItem();
    const ds = new DragSort([a]);
    ds.$draggee = [a];
    const container = makeItem();
    mockRect(container, 0, 0, 10, 10); // box 0..10
    ds.$heightedContainer = container;
    ds.$insertion = document.createElement('div');
    (ds as unknown as DragSortPrivate)._placeInsertionWithDraggee();
    ds.closestItem = a;
    ds.mouseX = 500; // far outside the container box
    ds.mouseY = 500;
    ds.onDrag();
    expect(ds.closestItem).toBeNull();
    expect(ds.insertionVisible).toBe(false);
  });
});

// ---------------------------------------------------------------------------
// onDragStop + sortChange
// ---------------------------------------------------------------------------

describe('DragSort.onDragStop', () => {
  function setup(): {ds: DragSort; a: HTMLElement; b: HTMLElement} {
    const a = makeItem();
    const b = makeItem();
    const ds = new DragSort([a, b]);
    ds.$draggee = [b];
    ds.$targetItem = b;
    ds.targetItemPositionInDraggee = 0;
    ds.draggeeDisplay = 'block';
    ds.helpers = [];
    return {ds, a, b};
  }

  it('returns helpers and removes the insertion', () => {
    const {ds} = setup();
    const ret = vi.spyOn(ds, 'returnHelpersToDraggees');
    const remove = vi.spyOn(
      ds as unknown as DragSortPrivate,
      '_removeInsertion'
    );
    ds.oldDraggeeIndexes = [1];
    ds.onDragStop();
    expect(remove).toHaveBeenCalled();
    expect(ret).toHaveBeenCalled();
  });

  it('fires sortChange when the draggee order changed', () => {
    const {ds} = setup();
    const onSort = vi.fn();
    ds.settings!.onSortChange = onSort;
    const triggered = vi.fn();
    ds.on('sortChange', triggered);
    // Pretend the draggee used to be at index 0; it is now at index 1.
    ds.oldDraggeeIndexes = [0];
    ds.onDragStop();
    flushRaf();
    expect(triggered).toHaveBeenCalledTimes(1);
    expect(onSort).toHaveBeenCalledTimes(1);
  });

  it('does not fire sortChange when the order is unchanged', () => {
    const {ds} = setup();
    const triggered = vi.fn();
    ds.on('sortChange', triggered);
    // b is at index 1 both before and after.
    ds.oldDraggeeIndexes = [1];
    ds.onDragStop();
    flushRaf();
    expect(triggered).not.toHaveBeenCalled();
  });
});

// ---------------------------------------------------------------------------
// Event hooks
// ---------------------------------------------------------------------------

describe('DragSort event hooks', () => {
  it('onInsertionPointChange emits the event + runs the callback (RAF-deferred)', () => {
    const cb = vi.fn();
    const ds = new DragSort(null, {onInsertionPointChange: cb});
    const triggered = vi.fn();
    ds.on('insertionPointChange', triggered);
    ds.onInsertionPointChange();
    expect(triggered).not.toHaveBeenCalled(); // deferred
    flushRaf();
    expect(triggered).toHaveBeenCalledTimes(1);
    expect(cb).toHaveBeenCalledTimes(1);
  });

  it('onSortChange emits the event + runs the callback (RAF-deferred)', () => {
    const cb = vi.fn();
    const ds = new DragSort(null, {onSortChange: cb});
    const triggered = vi.fn();
    ds.on('sortChange', triggered);
    ds.onSortChange();
    flushRaf();
    expect(triggered).toHaveBeenCalledTimes(1);
    expect(cb).toHaveBeenCalledTimes(1);
  });
});

// ---------------------------------------------------------------------------
// getHelperTargetX/Y — magnetStrength
// ---------------------------------------------------------------------------

describe('DragSort helper-target magnetStrength', () => {
  it('tracks the cursor exactly at magnetStrength 1 (delegates to Drag)', () => {
    const a = makeItem();
    const ds = new DragSort([a], {magnetStrength: 1});
    ds.$draggee = [a];
    ds.mouseX = 200;
    ds.mouseY = 120;
    ds.mouseOffsetX = 30;
    ds.mouseOffsetY = 20;
    expect(ds.getHelperTargetX()).toBe(170);
    expect(ds.getHelperTargetY()).toBe(100);
  });

  it('rubber-bands toward the draggee home at magnetStrength > 1', () => {
    const a = makeItem();
    mockRect(a, 100, 50); // getOffset(a) → {top:100, left:50}
    const ds = new DragSort([a], {magnetStrength: 2});
    ds.$draggee = [a];
    ds.mouseX = 250;
    ds.mouseY = 300;
    ds.mouseOffsetX = 10;
    ds.mouseOffsetY = 10;
    // X: 50 + (250 - 10 - 50) / 2 = 50 + 95 = 145
    expect(ds.getHelperTargetX()).toBe(145);
    // Y: 100 + (300 - 10 - 100) / 2 = 100 + 95 = 195
    expect(ds.getHelperTargetY()).toBe(195);
  });
});

// ---------------------------------------------------------------------------
// Event wiring via synthetic PointerEvents
// ---------------------------------------------------------------------------

describe('DragSort pointer wiring', () => {
  it('starts a drag and emits dragStart after a past-threshold pointer move', () => {
    const a = makeItem();
    const b = makeItem();
    const c = makeItem();
    const ds = new DragSort([a, b, c]);
    const started = vi.fn();
    ds.on('dragStart', started);

    firePointer(a, 'pointerdown', {pageX: 0, pageY: 0, pointerId: 1});
    firePointer(document, 'pointermove', {pageX: 0, pageY: 10, pointerId: 1});
    flushRaf();

    expect(ds.dragging).toBe(true);
    expect(started).toHaveBeenCalled();
    expect(ds.$draggee[0]).toBe(a);

    firePointer(document, 'pointerup', {pointerId: 1});
    void X_AXIS;
  });
});
