import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {HoverIntentGroup, type HoverIntentMember} from './hover-intent.js';

/**
 * Members are identified by their element, since that's how the group tells
 * nesting from adjacency. A detached tree is enough for `contains()`.
 */
function member(element: Element): HoverIntentMember & {open: boolean} {
  const state = {
    element,
    open: false,
    setOpen(open: boolean) {
      state.open = open;
    },
  };

  return state;
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

let group: HoverIntentGroup;

beforeEach(() => {
  vi.useFakeTimers();
  document.body.innerHTML = '';
  group = new HoverIntentGroup({
    warmUpDelay: 100,
    closeDelay: 50,
    coolDownDelay: 200,
  });
});

afterEach(() => {
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
