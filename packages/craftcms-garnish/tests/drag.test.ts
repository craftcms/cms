import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';

import {BaseDrag, getItemDragger} from '../src/drag/base-drag';
import {DragMove} from '../src/drag-move';
import {getScrollParent, isWindowScrollContainer} from '../src/utils/scroll';
import {X_AXIS, Y_AXIS} from '../src/constants';
import {globals, win} from '../src/globals';

// happy-dom notes (see doc 07 §7):
// - `PointerEvent` exists but `pageX/pageY/pointerId` are always 0 (no
//   geometry), so we dispatch a real PointerEvent and override those props.
// - There is no layout, so `getBoundingClientRect()` returns zeros — we mock it
//   where coordinate math depends on it.
// - The RAF-deferred hooks (`onDragStart`/`onDrag`/`onDragStop`) are driven by
//   stubbing the animation module's `requestAnimationFrame` to run synchronously
//   (below), so we can assert the emitted events without a real frame.
vi.mock('../src/utils/animation', async (importOriginal) => {
  const actual = await importOriginal<object>();
  return {
    ...actual,
    requestAnimationFrame: (cb: FrameRequestCallback): number => {
      cb(0);
      return 0;
    },
    cancelAnimationFrame: (): void => {},
  };
});

/** Dispatch a PointerEvent on `el` with page coords overridden (happy-dom zeros them). */
function firePointer(
  el: EventTarget,
  type: string,
  opts: {
    pageX?: number;
    pageY?: number;
    pointerId?: number;
    button?: number;
  } = {}
): PointerEvent {
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
  return ev;
}

function makeItem(): HTMLElement {
  const el = document.createElement('div');
  document.body.appendChild(el);
  return el;
}

/** Stub an element's bounding rect so getOffset returns a known top/left. */
function mockRect(el: Element, top: number, left: number): void {
  vi.spyOn(el, 'getBoundingClientRect').mockReturnValue({
    top,
    left,
    right: left,
    bottom: top,
    width: 0,
    height: 0,
    x: left,
    y: top,
    toJSON: () => ({}),
  } as DOMRect);
}

beforeEach(() => {
  document.body.innerHTML = '';
  document.body.className = '';
  globals.rtl = false;
  globals.activateEventsMuted = false;
});

afterEach(() => {
  vi.restoreAllMocks();
});

describe('BaseDrag settings + defaults', () => {
  it('merges defaults with passed settings', () => {
    const d = new BaseDrag(null, {axis: X_AXIS, minMouseDist: 5});
    expect(d.settings!.axis).toBe(X_AXIS);
    expect(d.settings!.minMouseDist).toBe(5);
    // Untouched defaults survive.
    expect(d.settings!.ignoreHandleSelector).toBe(
      'input, textarea, button, select, .btn'
    );
  });

  it('supports the param-shift form: new BaseDrag(settings)', () => {
    const d = new BaseDrag({axis: Y_AXIS});
    expect(d.settings!.axis).toBe(Y_AXIS);
    expect(d.$items).toEqual([]);
  });

  it('exposes the static tunables', () => {
    expect(BaseDrag.minMouseDist).toBe(1);
    expect(BaseDrag.windowScrollTargetSize).toBe(25);
  });

  it('starts with the documented initial state', () => {
    const d = new BaseDrag();
    expect(d.dragging).toBe(false);
    expect(d.$targetItem).toBeNull();
    expect(d.$items).toEqual([]);
    expect(d.mouseX).toBeNull();
    expect(d.scrollProperty).toBeNull();
  });
});

describe('BaseDrag item management + registry', () => {
  it('addItems registers items in $items and the WeakMap registry', () => {
    const a = makeItem();
    const b = makeItem();
    const d = new BaseDrag();
    d.addItems([a, b]);
    expect(d.$items).toEqual([a, b]);
    expect(getItemDragger(a)).toBe(d);
    expect(getItemDragger(b)).toBe(d);
  });

  it('addItems accepts a selector string', () => {
    const a = makeItem();
    a.className = 'draggable';
    const d = new BaseDrag('.draggable');
    expect(d.$items).toContain(a);
  });

  it('dedupes items already owned by the same dragger', () => {
    const a = makeItem();
    const d = new BaseDrag([a]);
    d.addItems([a]);
    expect(d.$items).toEqual([a]);
  });

  it('warns and steals an item from another dragger', () => {
    const a = makeItem();
    const d1 = new BaseDrag([a]);
    const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});
    const d2 = new BaseDrag();
    d2.addItems([a]);
    expect(warn).toHaveBeenCalledWith(
      'Element was added to more than one dragger'
    );
    expect(getItemDragger(a)).toBe(d2);
    expect(d1.$items).not.toContain(a);
    expect(d2.$items).toContain(a);
  });

  it('removeItems drops the item and its registry entry', () => {
    const a = makeItem();
    const b = makeItem();
    const d = new BaseDrag([a, b]);
    d.removeItems(a);
    expect(d.$items).toEqual([b]);
    expect(getItemDragger(a)).toBeUndefined();
  });

  it('removeAllItems clears everything', () => {
    const d = new BaseDrag([makeItem(), makeItem()]);
    d.removeAllItems();
    expect(d.$items).toEqual([]);
  });

  it('getPrevItem / getNextItem walk the $items array', () => {
    const a = makeItem();
    const b = makeItem();
    const c = makeItem();
    const d = new BaseDrag([a, b, c]);
    expect(d.getPrevItem(b)).toBe(a);
    expect(d.getNextItem(b)).toBe(c);
    expect(d.getPrevItem(a)).toBeNull();
    expect(d.getNextItem(c)).toBeNull();
    expect(d.getPrevItem(makeItem())).toBeNull();
  });

  it('destroy removes all items + clears the registry', () => {
    const a = makeItem();
    const d = new BaseDrag([a]);
    d.destroy();
    expect(d.$items).toEqual([]);
    expect(getItemDragger(a)).toBeUndefined();
  });
});

describe('BaseDrag handle resolution', () => {
  it('uses the item itself when handle is null', () => {
    const a = makeItem();
    const child = document.createElement('span');
    a.appendChild(child);
    const onBefore = vi.fn();
    const d = new BaseDrag([a], {onBeforeDragStart: onBefore, minMouseDist: 0});
    mockRect(a, 0, 0);
    // pointerdown on the item starts capture.
    firePointer(a, 'pointerdown', {pageX: 5, pageY: 5});
    expect(d.$targetItem).toBe(a);
  });

  it('resolves a string handle via querySelector within the item', () => {
    const a = makeItem();
    const handle = document.createElement('div');
    handle.className = 'handle';
    a.appendChild(handle);
    const d = new BaseDrag([a], {handle: '.handle'});
    mockRect(a, 0, 0);
    // pointerdown on the handle captures; pointerdown elsewhere on item does not.
    firePointer(handle, 'pointerdown', {pageX: 1, pageY: 1});
    expect(d.$targetItem).toBe(a);
  });

  it('resolves a function handle', () => {
    const a = makeItem();
    const handle = document.createElement('div');
    a.appendChild(handle);
    const d = new BaseDrag([a], {handle: () => handle});
    mockRect(a, 0, 0);
    firePointer(handle, 'pointerdown', {pageX: 1, pageY: 1});
    expect(d.$targetItem).toBe(a);
  });
});

describe('BaseDrag pointer-down gating + coordinate capture', () => {
  it('ignores secondary (button !== 0) clicks', () => {
    const a = makeItem();
    const d = new BaseDrag([a]);
    mockRect(a, 0, 0);
    firePointer(a, 'pointerdown', {pageX: 5, pageY: 5, button: 2});
    expect(d.$targetItem).toBeNull();
  });

  it('ignores pointerdown on an ignoreHandleSelector descendant', () => {
    const a = makeItem();
    const button = document.createElement('button');
    a.appendChild(button);
    const d = new BaseDrag([a]); // default ignoreHandleSelector includes `button`
    mockRect(a, 0, 0);
    firePointer(button, 'pointerdown', {pageX: 5, pageY: 5});
    expect(d.$targetItem).toBeNull();
  });

  it('captures the target, mousedown coords, and the grab offset', () => {
    const a = makeItem();
    mockRect(a, 30, 20); // top=30, left=20
    const d = new BaseDrag([a]);
    firePointer(a, 'pointerdown', {pageX: 50, pageY: 60});
    expect(d.$targetItem).toBe(a);
    expect(d.mousedownX).toBe(50);
    expect(d.mousedownY).toBe(60);
    expect(d.mouseOffsetX).toBe(50 - 20);
    expect(d.mouseOffsetY).toBe(60 - 30);
  });
});

describe('BaseDrag drag start threshold + axis locking', () => {
  it('starts dragging once minMouseDist is exceeded and fires the hooks', () => {
    const a = makeItem();
    mockRect(a, 0, 0);
    const onStart = vi.fn();
    const onDrag = vi.fn();
    const d = new BaseDrag([a], {
      minMouseDist: 5,
      onDragStart: onStart,
      onDrag,
    });

    firePointer(a, 'pointerdown', {pageX: 0, pageY: 0});
    // Small move — below threshold, no drag.
    firePointer(document, 'pointermove', {pageX: 2, pageY: 0});
    expect(d.dragging).toBe(false);

    // Big move — crosses threshold.
    firePointer(document, 'pointermove', {pageX: 20, pageY: 0});
    expect(d.dragging).toBe(true);
    // RAF is stubbed synchronous, so the deferred hooks have fired.
    expect(onStart).toHaveBeenCalled();
    expect(onDrag).toHaveBeenCalled();
  });

  it('locks to the X axis (mouseY stays at mousedown)', () => {
    const a = makeItem();
    mockRect(a, 0, 0);
    const d = new BaseDrag([a], {axis: X_AXIS, minMouseDist: 0});
    firePointer(a, 'pointerdown', {pageX: 0, pageY: 0});
    firePointer(document, 'pointermove', {pageX: 40, pageY: 40});
    expect(d.mouseX).toBe(40);
    // mouseY was never reassigned past mousedown (axis-locked).
    expect(d.mouseY).toBe(0);
    expect(d.realMouseY).toBe(40); // realMouse is always tracked
  });

  it('locks to the Y axis (mouseX stays at mousedown)', () => {
    const a = makeItem();
    mockRect(a, 0, 0);
    const d = new BaseDrag([a], {axis: Y_AXIS, minMouseDist: 0});
    firePointer(a, 'pointerdown', {pageX: 0, pageY: 0});
    firePointer(document, 'pointermove', {pageX: 40, pageY: 40});
    expect(d.mouseY).toBe(40);
    expect(d.mouseX).toBe(0);
    expect(d.realMouseX).toBe(40);
  });

  it('computes mouseDistX/Y from mousedown', () => {
    const a = makeItem();
    mockRect(a, 0, 0);
    const d = new BaseDrag([a], {minMouseDist: 0});
    firePointer(a, 'pointerdown', {pageX: 10, pageY: 10});
    firePointer(document, 'pointermove', {pageX: 30, pageY: 25});
    expect(d.mouseDistX).toBe(20);
    expect(d.mouseDistY).toBe(15);
  });

  it('ignores pointermove from a different pointerId (multi-touch)', () => {
    const a = makeItem();
    mockRect(a, 0, 0);
    const d = new BaseDrag([a], {minMouseDist: 0});
    firePointer(a, 'pointerdown', {pageX: 0, pageY: 0, pointerId: 1});
    firePointer(document, 'pointermove', {pageX: 40, pageY: 40, pointerId: 2});
    // The second pointer is ignored — no coords updated.
    expect(d.mouseX).toBe(0);
    expect(d.dragging).toBe(false);
  });
});

describe('BaseDrag pointer-up / stop', () => {
  it('stops dragging and clears the target on pointerup', () => {
    const a = makeItem();
    mockRect(a, 0, 0);
    const onStop = vi.fn();
    const d = new BaseDrag([a], {minMouseDist: 0, onDragStop: onStop});
    firePointer(a, 'pointerdown', {pageX: 0, pageY: 0});
    firePointer(document, 'pointermove', {pageX: 20, pageY: 0});
    expect(d.dragging).toBe(true);

    firePointer(document, 'pointerup', {pageX: 20, pageY: 0});
    expect(d.dragging).toBe(false);
    expect(d.$targetItem).toBeNull();
    expect(onStop).toHaveBeenCalled();
  });

  it('treats pointercancel like pointerup', () => {
    const a = makeItem();
    mockRect(a, 0, 0);
    const d = new BaseDrag([a], {minMouseDist: 0});
    firePointer(a, 'pointerdown', {pageX: 0, pageY: 0});
    firePointer(document, 'pointermove', {pageX: 20, pageY: 0});
    firePointer(document, 'pointercancel', {pageX: 20, pageY: 0});
    expect(d.dragging).toBe(false);
    expect(d.$targetItem).toBeNull();
  });
});

describe('BaseDrag event hooks', () => {
  it('onBeforeDragStart fires synchronously; trigger + callback', () => {
    const d = new BaseDrag(null, {});
    const onBefore = vi.fn();
    d.settings!.onBeforeDragStart = onBefore;
    const triggered = vi.fn();
    d.on('beforeDragStart', triggered);
    d.onBeforeDragStart();
    expect(triggered).toHaveBeenCalled();
    expect(onBefore).toHaveBeenCalled();
  });

  it('onDragStart / onDrag / onDragStop emit their events (RAF stubbed sync)', () => {
    const d = new BaseDrag();
    const start = vi.fn();
    const drag = vi.fn();
    const stop = vi.fn();
    d.on('dragStart', start);
    d.on('drag', drag);
    d.on('dragStop', stop);
    d.onDragStart();
    d.onDrag();
    d.onDragStop();
    expect(start).toHaveBeenCalled();
    expect(drag).toHaveBeenCalled();
    expect(stop).toHaveBeenCalled();
  });

  it('startDragging mutes activate events; stopDragging unmutes them', () => {
    const a = makeItem();
    mockRect(a, 0, 0);
    const d = new BaseDrag([a], {minMouseDist: 0});
    firePointer(a, 'pointerdown', {pageX: 0, pageY: 0});
    firePointer(document, 'pointermove', {pageX: 20, pageY: 0});
    expect(globals.activateEventsMuted).toBe(true);
    firePointer(document, 'pointerup', {pageX: 20, pageY: 0});
    // stopDragging schedules the unmute in a RAF (stubbed synchronous here).
    expect(globals.activateEventsMuted).toBe(false);
  });
});

describe('DragMove', () => {
  it('positions the target at the cursor minus the grab offset', () => {
    const a = makeItem();
    const d = new DragMove();
    d.$targetItem = a;
    d.mouseX = 100;
    d.mouseY = 80;
    d.mouseOffsetX = 10;
    d.mouseOffsetY = 5;
    d.onDrag();
    expect(a.style.left).toBe('90px');
    expect(a.style.top).toBe('75px');
  });

  it('still emits the drag event (Option A: calls super.onDrag)', () => {
    const a = makeItem();
    const d = new DragMove();
    d.$targetItem = a;
    d.mouseX = 0;
    d.mouseY = 0;
    d.mouseOffsetX = 0;
    d.mouseOffsetY = 0;
    const drag = vi.fn();
    d.on('drag', drag);
    d.onDrag();
    expect(drag).toHaveBeenCalled();
  });

  it('subtracts page scroll for a position:fixed target (no scroll-jump)', () => {
    // A fixed element's containing block is the viewport (anchored at the page
    // scroll offset), so a page-coordinate target must have the scroll
    // subtracted — otherwise the element jumps down by scrollY on the first drag
    // frame. Repro of the draggable-Modal "jumps down the page" bug.
    const a = makeItem();
    vi.spyOn(window, 'getComputedStyle').mockReturnValue({
      position: 'fixed',
    } as CSSStyleDeclaration);
    Object.defineProperty(window, 'scrollX', {value: 0, configurable: true});
    Object.defineProperty(window, 'scrollY', {value: 300, configurable: true});

    const d = new DragMove();
    d.$targetItem = a;
    d.mouseX = 100; // pageX
    d.mouseY = 380; // pageY = viewport 80 + scrollY 300
    d.mouseOffsetX = 10;
    d.mouseOffsetY = 5;
    d.onDrag();

    // left: 100 - 10 - 0 = 90; top: 380 - 5 - 300 = 75 (viewport-relative).
    expect(a.style.left).toBe('90px');
    expect(a.style.top).toBe('75px');

    Object.defineProperty(window, 'scrollX', {value: 0, configurable: true});
    Object.defineProperty(window, 'scrollY', {value: 0, configurable: true});
  });
});

describe('getScrollParent (axis-aware)', () => {
  it('returns window when no scrollable ancestor exists', () => {
    const a = makeItem();
    expect(getScrollParent(a)).toBe(win);
    expect(isWindowScrollContainer(getScrollParent(a))).toBe(true);
  });

  it('returns a vertically-scrollable ancestor for the y axis', () => {
    const container = document.createElement('div');
    const child = document.createElement('div');
    container.appendChild(child);
    document.body.appendChild(container);

    vi.spyOn(window, 'getComputedStyle').mockReturnValue({
      overflow: 'auto',
      overflowX: 'visible',
      overflowY: 'auto',
    } as CSSStyleDeclaration);
    Object.defineProperty(container, 'scrollHeight', {
      value: 500,
      configurable: true,
    });
    Object.defineProperty(container, 'clientHeight', {
      value: 100,
      configurable: true,
    });
    Object.defineProperty(container, 'scrollWidth', {
      value: 100,
      configurable: true,
    });
    Object.defineProperty(container, 'clientWidth', {
      value: 100,
      configurable: true,
    });

    expect(getScrollParent(child, Y_AXIS)).toBe(container);
  });

  it('skips a container not scrollable on the requested axis', () => {
    const container = document.createElement('div');
    const child = document.createElement('div');
    container.appendChild(child);
    document.body.appendChild(container);

    // overflow auto, but only vertically scrollable.
    vi.spyOn(window, 'getComputedStyle').mockReturnValue({
      overflow: 'auto',
      overflowX: 'auto',
      overflowY: 'auto',
    } as CSSStyleDeclaration);
    Object.defineProperty(container, 'scrollHeight', {
      value: 500,
      configurable: true,
    });
    Object.defineProperty(container, 'clientHeight', {
      value: 100,
      configurable: true,
    });
    Object.defineProperty(container, 'scrollWidth', {
      value: 100,
      configurable: true,
    });
    Object.defineProperty(container, 'clientWidth', {
      value: 100,
      configurable: true,
    });

    // Asking for X-axis scrollability: container isn't horizontally scrollable,
    // and there is no other scrollable ancestor → window.
    expect(getScrollParent(child, X_AXIS)).toBe(win);
  });

  it('normalizes <body>/<html> to the window', () => {
    const a = makeItem(); // parented to <body>
    // <body> has no overflow scroll; getScrollParent climbs to body → window.
    expect(getScrollParent(a)).toBe(win);
  });
});

describe('BaseDrag auto-scroll decision (window edges)', () => {
  // Drive `_computeEdgeScroll` (private) via `drag(true)` after setting up state.
  // We assert the public scroll* fields it populates.
  function setupDraggingOnWindow(): BaseDrag {
    const a = makeItem();
    mockRect(a, 0, 0);
    const d = new BaseDrag([a], {minMouseDist: 0});
    firePointer(a, 'pointerdown', {pageX: 0, pageY: 0});
    // Now dragging; container resolves to window (no scrollable ancestor).
    return d;
  }

  it('claims a downward scroll when the pointer nears the bottom edge', () => {
    const d = setupDraggingOnWindow();
    // Window: innerHeight, scrollY known from happy-dom (0). Edge band = 25px.
    const innerHeight = win.innerHeight;
    // Put mouseY past (innerHeight - 25) so it triggers a down-scroll.
    firePointer(document, 'pointermove', {
      pageX: 0,
      pageY: innerHeight - 5,
    });
    expect(d.scrollProperty).toBe('scrollTop');
    expect(d.scrollAxis).toBe('Y');
    expect(d.scrollDist).toBeGreaterThan(0);
  });

  it('does not claim a scroll when the pointer is mid-viewport', () => {
    const d = setupDraggingOnWindow();
    firePointer(document, 'pointermove', {
      pageX: Math.round(win.innerWidth / 2),
      pageY: Math.round(win.innerHeight / 2),
    });
    expect(d.scrollProperty).toBeNull();
  });
});
