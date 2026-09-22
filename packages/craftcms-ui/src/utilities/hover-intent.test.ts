import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {HoverIntentGroup, type HoverIntentMember} from './hover-intent.js';

/**
 * Members are identified by their element, since that's how the group tells
 * nesting from adjacency. A detached tree is enough for `contains()`.
 */
function member(
  element: Element,
  overlay?: DOMRect
): HoverIntentMember & {open: boolean} {
  const state = {
    element,
    open: false,
    setOpen(open: boolean) {
      state.open = open;
    },
    ...(overlay ? {overlayRect: () => overlay} : {}),
  };

  return state;
}

function box(
  left: number,
  right: number,
  top: number,
  bottom: number
): DOMRect {
  return {
    left,
    right,
    top,
    bottom,
    x: left,
    y: top,
    width: right - left,
    height: bottom - top,
  } as DOMRect;
}

/**
 * The group only learns where the pointer is from a real event, so a test that
 * never moves it gets no safe area at all.
 */
function movePointer(x: number, y: number) {
  document.dispatchEvent(
    new MouseEvent('pointermove', {clientX: x, clientY: y, bubbles: true})
  );
}

function tree() {
  const parent = document.createElement('div');
  const child = document.createElement('div');
  const sibling = document.createElement('div');

  parent.append(child);
  document.body.append(parent, sibling);

  return {
    parent: member(parent),
    child: member(child),
    sibling: member(sibling),
  };
}

/** A trigger whose overlay sits to its right, plus the row between the two. */
function aimedTree() {
  const trigger = document.createElement('div');
  const neighbour = document.createElement('div');

  document.body.append(trigger, neighbour);

  return {
    trigger: member(trigger, box(100, 200, 0, 100)),
    neighbour: member(neighbour),
  };
}

let group: HoverIntentGroup;

beforeEach(() => {
  vi.useFakeTimers();
  document.body.innerHTML = '';
  group = new HoverIntentGroup({
    warmUpDelay: 100,
    closeDelay: 50,
    coolDownDelay: 200,
    graceDelay: 300,
  });
});

afterEach(() => {
  group.reset();
  vi.useRealTimers();
});

describe('warm-up', () => {
  it('holds the first open until the warm-up elapses', () => {
    const {parent} = tree();

    group.requestOpen(parent);
    expect(parent.open).toBe(false);

    vi.advanceTimersByTime(99);
    expect(parent.open).toBe(false);

    vi.advanceTimersByTime(1);
    expect(parent.open).toBe(true);
    expect(group.warm).toBe(true);
  });

  it('opens instantly once the group is warm', () => {
    const {parent, sibling} = tree();

    group.requestOpen(parent);
    vi.advanceTimersByTime(100);

    group.requestOpen(sibling);
    expect(sibling.open).toBe(true);
  });

  it('lets focus skip the warm-up', () => {
    const {parent} = tree();

    group.requestOpen(parent, {immediate: true});

    expect(parent.open).toBe(true);
  });

  it('abandons a warm-up the pointer walked away from', () => {
    const {parent} = tree();

    group.requestOpen(parent);
    group.requestClose(parent);
    vi.advanceTimersByTime(500);

    expect(parent.open).toBe(false);
    expect(group.warm).toBe(false);
  });
});

describe('cool-down', () => {
  it('goes cold once everything has been shut for long enough', () => {
    const {parent} = tree();

    group.requestOpen(parent, {immediate: true});
    group.requestClose(parent);
    vi.advanceTimersByTime(50);
    expect(parent.open).toBe(false);
    // Still warm: the cool-down runs from the close, not the leave.
    expect(group.warm).toBe(true);

    vi.advanceTimersByTime(200);
    expect(group.warm).toBe(false);
  });

  it('stays warm while something is still open', () => {
    const {parent, child} = tree();

    group.requestOpen(parent, {immediate: true});
    // Nested, so the parent survives the handover and stays open.
    group.requestOpen(child);
    group.requestClose(child);
    vi.advanceTimersByTime(1000);

    expect(child.open).toBe(false);
    expect(parent.open).toBe(true);
    expect(group.warm).toBe(true);
  });
});

describe('handover', () => {
  it('closes a sibling immediately rather than after its close delay', () => {
    const {parent, sibling} = tree();

    group.requestOpen(parent, {immediate: true});
    group.requestClose(parent);
    group.requestOpen(sibling);

    expect(parent.open).toBe(false);
    expect(sibling.open).toBe(true);
  });

  it('spares an ancestor of the thing being opened', () => {
    const {parent, child} = tree();

    group.requestOpen(parent, {immediate: true});
    group.requestOpen(child);

    // The child's overlay is reached *through* the parent's, so closing the
    // parent would pull it out from under the pointer.
    expect(parent.open).toBe(true);
    expect(child.open).toBe(true);
  });

  it('closes a descendant when the pointer returns to its ancestor', () => {
    const {parent, child} = tree();

    group.requestOpen(parent, {immediate: true});
    group.requestOpen(child);
    group.requestOpen(parent);

    // Back out to the parent: the child is no longer on the path.
    expect(child.open).toBe(false);
    expect(parent.open).toBe(true);
  });
});

describe('bookkeeping', () => {
  it('cancels a pending close when the pointer comes back', () => {
    const {parent} = tree();

    group.requestOpen(parent, {immediate: true});
    group.requestClose(parent);
    group.requestOpen(parent);
    vi.advanceTimersByTime(500);

    expect(parent.open).toBe(true);
  });

  it('follows an overlay that closed itself', () => {
    const {parent} = tree();

    group.requestOpen(parent, {immediate: true});
    // Escape, or a click outside: the overlay is gone without the group
    // having asked, so a later close must not fire against a stale state.
    group.notifyClosed(parent);
    vi.advanceTimersByTime(200);

    expect(group.warm).toBe(false);
  });

  it('drops a removed member and its timers', () => {
    const {parent} = tree();

    group.requestOpen(parent);
    group.remove(parent);
    vi.advanceTimersByTime(500);

    expect(parent.open).toBe(false);
  });
});

describe('safe area', () => {
  it('holds a neighbour hovered on the way to an open overlay', () => {
    const {trigger, neighbour} = aimedTree();

    group.requestOpen(trigger, {immediate: true});
    movePointer(10, 10);
    group.requestClose(trigger);

    movePointer(60, 40);
    group.requestOpen(neighbour);
    vi.advanceTimersByTime(200);

    expect(neighbour.open).toBe(false);
    expect(trigger.open).toBe(true);
  });

  it('lets the neighbour through once the pointer leaves the triangle', () => {
    const {trigger, neighbour} = aimedTree();

    group.requestOpen(trigger, {immediate: true});
    movePointer(10, 10);
    group.requestClose(trigger);
    movePointer(60, 40);
    group.requestOpen(neighbour);

    movePointer(20, 300);

    expect(neighbour.open).toBe(true);
    expect(trigger.open).toBe(false);
  });

  it('opens the neighbour anyway once the grace runs out', () => {
    const {trigger, neighbour} = aimedTree();

    group.requestOpen(trigger, {immediate: true});
    movePointer(10, 10);
    group.requestClose(trigger);
    movePointer(60, 40);
    group.requestOpen(neighbour);
    vi.advanceTimersByTime(300);

    expect(trigger.open).toBe(false);
    expect(neighbour.open).toBe(true);
  });

  it('hands over at once when the pointer is nowhere near the triangle', () => {
    const {trigger, neighbour} = aimedTree();

    group.requestOpen(trigger, {immediate: true});
    movePointer(10, 10);
    group.requestClose(trigger);
    movePointer(10, 400);
    group.requestOpen(neighbour);

    expect(trigger.open).toBe(false);
    expect(neighbour.open).toBe(true);
  });

  it('gives no grace to an overlay with nothing to measure', () => {
    const {parent, sibling} = tree();

    group.requestOpen(parent, {immediate: true});
    movePointer(10, 10);
    group.requestClose(parent);
    movePointer(60, 40);
    group.requestOpen(sibling);

    expect(parent.open).toBe(false);
    expect(sibling.open).toBe(true);
  });

  it('drops a held hover the pointer moved on from', () => {
    const {trigger, neighbour} = aimedTree();

    group.requestOpen(trigger, {immediate: true});
    movePointer(10, 10);
    group.requestClose(trigger);
    movePointer(60, 40);
    group.requestOpen(neighbour);
    group.requestClose(neighbour);

    movePointer(20, 300);
    vi.advanceTimersByTime(500);

    expect(neighbour.open).toBe(false);
    expect(trigger.open).toBe(false);
  });

  it('spares the trigger the pointer doubles back into', () => {
    const {trigger, neighbour} = aimedTree();

    group.requestOpen(trigger, {immediate: true});
    movePointer(10, 10);
    group.requestClose(trigger);
    movePointer(60, 40);
    group.requestOpen(neighbour);

    group.requestClose(neighbour);
    group.requestOpen(trigger);
    vi.advanceTimersByTime(500);

    expect(trigger.open).toBe(true);
    expect(neighbour.open).toBe(false);
  });
});
