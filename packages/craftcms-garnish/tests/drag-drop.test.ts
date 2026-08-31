import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';

import {Drag} from '../src/drag/drag';
import {DragDrop} from '../src/drag/drag-drop';
import {globals} from '../src/globals';

// happy-dom notes (see doc 09 §7):
// - No layout: `getBoundingClientRect()` returns zeros and `offsetWidth/Height`
//   are 0. We mock `getOuterWidth/getOuterHeight` (utils/dom) for the geometry
//   that matters, and `getBoundingClientRect` where getOffset is read.
// - `element.animate` is `undefined`, so the return-to-source / fade WAAPI paths
//   take the no-WAAPI fallback and resolve synchronously — exactly the path we
//   can assert. The real tweens are playground-only.
// - The RAF-deferred hooks + the lag-follow loop are made synchronous by
//   stubbing the animation module's `requestAnimationFrame`. (Note: the lag loop
//   re-schedules itself every frame; with a sync stub that would recurse
//   forever, so tests that touch `startDragging` cancel the loop or never drive
//   a real pointer move — see `runOneFrame` below.)
vi.mock('../src/utils/animation', async (importOriginal) => {
  const actual = await importOriginal<object>();
  return {
    ...actual,
    // Default: defer to a manual queue so the self-rescheduling lag loop does
    // not infinitely recurse. Tests flush the queue explicitly when needed.
    requestAnimationFrame: (cb: FrameRequestCallback): number => {
      rafQueue.push(cb);
      return rafQueue.length;
    },
    cancelAnimationFrame: (handle: number): void => {
      // handles are 1-based indices into rafQueue
      if (handle > 0 && handle <= rafQueue.length) {
        rafQueue[handle - 1] = undefined;
      }
    },
  };
});

let rafQueue: Array<FrameRequestCallback | undefined> = [];

/** Run every currently-queued RAF callback exactly once (new ones are deferred). */
function flushRaf(): void {
  const current = rafQueue;
  rafQueue = [];
  for (const cb of current) {
    cb?.(0);
  }
}

/**
 * Private-method shape used to `vi.spyOn` Drag internals. `vi.spyOn(d as never,
 * ...)` typechecks for reads but not for `.mockImplementation`, so we cast
 * through this when we need to stub.
 */
interface DragPrivate {
  _createHelper: (index: number) => void;
  _updateHelperPos: () => void;
  _getHelperTarget: (i: number, real?: boolean) => {left: number; top: number};
}

function makeItem(cls = ''): HTMLElement {
  const el = document.createElement('div');
  if (cls) el.className = cls;
  document.body.appendChild(el);
  return el;
}

/** Stub an element's bounding rect so getOffset/hitTest read a known box. */
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
// Drag — settings + defaults
// ---------------------------------------------------------------------------

describe('Drag settings + defaults', () => {
  it('merges Drag.defaults with passed settings, preserving BaseDrag keys', () => {
    const d = new Drag(null, {singleHelper: true, minMouseDist: 7});
    expect(d.settings!.singleHelper).toBe(true);
    expect(d.settings!.minMouseDist).toBe(7);
    // Drag default survives.
    expect(d.settings!.hideDraggee).toBe(true);
    expect(d.settings!.helperBaseZindex).toBe(3000);
    // BaseDrag default survives.
    expect(d.settings!.ignoreHandleSelector).toBe(
      'input, textarea, button, select, .btn'
    );
  });

  it('supports the param-shift form: new Drag(settingsObj)', () => {
    const d = new Drag({helperOpacity: 0.5});
    expect(d.settings!.helperOpacity).toBe(0.5);
    expect(d.$items).toEqual([]);
  });

  it('exposes the documented Drag defaults', () => {
    expect(Drag.defaults.filter).toBeNull();
    expect(Drag.defaults.singleHelper).toBe(false);
    expect(Drag.defaults.helperLagBase).toBe(3);
    expect(Drag.defaults.helperLagIncrementDividend).toBe(1.5);
    expect(Drag.defaults.helperSpacingX).toBe(5);
    expect(Drag.defaults.helperSpacingY).toBe(5);
  });

  it('starts with empty draggee/helper state', () => {
    const d = new Drag();
    expect(d.$draggee).toEqual([]);
    expect(d.helpers).toEqual([]);
    expect(d.allowDragging()).toBe(true);
  });
});

// ---------------------------------------------------------------------------
// Drag — findDraggee
// ---------------------------------------------------------------------------

describe('Drag.findDraggee', () => {
  it('returns [$targetItem] when filter is null', () => {
    const a = makeItem();
    const d = new Drag([a]);
    d.$targetItem = a;
    expect(d.findDraggee()).toEqual([a]);
  });

  it('returns [] when filter is null and no target', () => {
    const d = new Drag();
    expect(d.findDraggee()).toEqual([]);
  });

  it('calls a function filter and coerces the result', () => {
    const a = makeItem();
    const b = makeItem();
    const d = new Drag([a], {filter: () => [a, b]});
    d.$targetItem = a;
    expect(d.findDraggee()).toEqual([a, b]);
  });

  it('treats a string filter as a selector over $items', () => {
    const a = makeItem('sel');
    const b = makeItem();
    const c = makeItem('sel');
    const d = new Drag([a, b, c], {filter: '.sel'});
    expect(d.findDraggee()).toEqual([a, c]);
  });
});

// ---------------------------------------------------------------------------
// Drag — setDraggee + helper creation
// ---------------------------------------------------------------------------

describe('Drag.setDraggee', () => {
  function setup(settings = {}): {d: Drag; a: HTMLElement; b: HTMLElement} {
    const a = makeItem();
    const b = makeItem();
    const d = new Drag([a, b], settings);
    d.$targetItem = a;
    d.draggeeDisplay = 'block';
    vi.spyOn(d as unknown as DragPrivate, '_createHelper').mockImplementation(
      () => {}
    );
    return {d, a, b};
  }

  it('forces the target to index 0 and records its position', () => {
    const {d, a, b} = setup();
    d.setDraggee([b, a]);
    expect(d.$draggee[0]).toBe(a);
    expect(d.$draggee).toEqual([a, b]);
    // The target was at index 1 in the input set ([b, a]).
    expect(d.targetItemPositionInDraggee).toBe(1);
  });

  it('adds the target to the draggee set if missing', () => {
    const {d, a, b} = setup();
    d.setDraggee([b]);
    expect(d.$draggee).toEqual([a, b]);
  });

  it('creates one helper per draggee by default', () => {
    const {d, a, b} = setup();
    const spy = vi.spyOn(d as unknown as DragPrivate, '_createHelper');
    d.setDraggee([a, b]);
    expect(spy).toHaveBeenCalledTimes(2);
  });

  it('creates a single helper when singleHelper is set', () => {
    const {d, a, b} = setup({singleHelper: true});
    const spy = vi.spyOn(d as unknown as DragPrivate, '_createHelper');
    d.setDraggee([a, b]);
    expect(spy).toHaveBeenCalledTimes(1);
    expect(spy).toHaveBeenCalledWith(0);
  });

  it('hideDraggee → visibility:hidden on every draggee', () => {
    const {d, a, b} = setup({hideDraggee: true});
    d.setDraggee([a, b]);
    expect(a.style.visibility).toBe('hidden');
    expect(b.style.visibility).toBe('hidden');
  });

  it('removeDraggee → display:none on every draggee', () => {
    const {d, a, b} = setup({
      hideDraggee: false,
      removeDraggee: true,
    });
    d.setDraggee([a, b]);
    expect(a.style.display).toBe('none');
    expect(b.style.display).toBe('none');
  });

  it('collapseDraggees → target hidden via visibility, others via display', () => {
    const {d, a, b} = setup({
      hideDraggee: false,
      collapseDraggees: true,
    });
    d.setDraggee([a, b]);
    expect(a.style.visibility).toBe('hidden');
    expect(b.style.display).toBe('none');
  });
});

describe('Drag.appendDraggee', () => {
  it('appends new draggees and creates helpers for the new indices', () => {
    const a = makeItem();
    const b = makeItem();
    const c = makeItem();
    const d = new Drag([a, b, c]);
    d.$targetItem = a;
    d.draggeeDisplay = 'block';
    vi.spyOn(d as unknown as DragPrivate, '_createHelper').mockImplementation(
      () => {}
    );
    d.$draggee = [a];
    const spy = vi.spyOn(d as unknown as DragPrivate, '_createHelper');
    d.appendDraggee([b, c]);
    expect(d.$draggee).toEqual([a, b, c]);
    expect(spy).toHaveBeenCalledTimes(2);
    expect(spy).toHaveBeenCalledWith(1);
    expect(spy).toHaveBeenCalledWith(2);
  });

  it('early-returns on an empty new set', () => {
    const a = makeItem();
    const d = new Drag([a]);
    d.$draggee = [a];
    const spy = vi.spyOn(d as unknown as DragPrivate, '_createHelper');
    d.appendDraggee([]);
    expect(spy).not.toHaveBeenCalled();
    expect(d.$draggee).toEqual([a]);
  });
});

// ---------------------------------------------------------------------------
// Drag — _createHelper (clone shape)
// ---------------------------------------------------------------------------

describe('Drag._createHelper', () => {
  function setup(settings = {}): {d: Drag; a: HTMLElement} {
    const a = makeItem();
    const d = new Drag([a], settings);
    d.$targetItem = a;
    d.$draggee = [a];
    d.draggeeDisplay = 'block';
    d.mouseX = 100;
    d.mouseY = 50;
    d.mouseOffsetX = 10;
    d.mouseOffsetY = 5;
    // happy-dom returns 0 for offsetWidth/Height; the helper just needs *some*
    // sizing call to succeed. getOuterWidth/Height read offsetWidth/Height (0).
    return {d, a};
  }

  function callCreate(d: Drag, index: number): void {
    (d as unknown as {_createHelper: (i: number) => void})._createHelper(index);
  }

  it('clones the draggee, adds .draghelper, and appends to body', () => {
    const {d, a} = setup();
    a.appendChild(document.createElement('span'));
    callCreate(d, 0);
    expect(d.helpers).toHaveLength(1);
    const helper = d.helpers[0]!;
    expect(helper.classList.contains('draghelper')).toBe(true);
    expect(helper.parentElement).toBe(document.body);
    // Deep clone preserved the child.
    expect(helper.querySelector('span')).not.toBeNull();
  });

  it('blanks every [name] attribute on the clone', () => {
    const {d, a} = setup();
    const input = document.createElement('input');
    input.setAttribute('name', 'foo');
    a.appendChild(input);
    callCreate(d, 0);
    const helperInput = d.helpers[0]!.querySelector('input')!;
    expect(helperInput.getAttribute('name')).toBe('');
  });

  it('sets absolute position, border-box sizing, z-index, and display', () => {
    const {d, a} = setup();
    callCreate(d, 0);
    const helper = d.helpers[0]!;
    expect(helper.style.position).toBe('absolute');
    expect(helper.style.boxSizing).toBe('border-box');
    expect(helper.style.display).toBe('block');
    expect(helper.style.pointerEvents).toBe('none');
    // zIndex = base(3000) + draggeeLength(1) - index(0) = 3001
    expect(helper.style.zIndex).toBe('3001');
    // real=true target: mouseX - mouseOffsetX = 90, mouseY - mouseOffsetY = 45
    expect(helper.style.left).toBe('90px');
    expect(helper.style.top).toBe('45px');
    void a;
  });

  it('applies helperOpacity when not 1', () => {
    const {d} = setup({helperOpacity: 0.4});
    callCreate(d, 0);
    expect(d.helpers[0]!.style.opacity).toBe('0.4');
  });

  it('invokes a function helper wrapper', () => {
    const wrapper = document.createElement('div');
    wrapper.className = 'wrap';
    const helperFn = vi.fn((clone: HTMLElement) => {
      wrapper.appendChild(clone);
      return wrapper;
    });
    const {d} = setup({helper: helperFn});
    callCreate(d, 0);
    expect(helperFn).toHaveBeenCalled();
    expect(d.helpers[0]).toBe(wrapper);
    expect(wrapper.parentElement).toBe(document.body);
  });

  it('wraps the clone into an element helper', () => {
    const wrapper = document.createElement('div');
    wrapper.className = 'wrap';
    document.body.appendChild(wrapper);
    const {d} = setup({helper: wrapper});
    callCreate(d, 0);
    expect(d.helpers[0]).toBe(wrapper);
    expect(wrapper.querySelector('.draghelper')).not.toBeNull();
  });

  it('copies input values when copyDraggeeInputValuesToHelper is set', () => {
    const {d, a} = setup({copyDraggeeInputValuesToHelper: true});
    const input = document.createElement('input');
    input.value = 'hello';
    a.appendChild(input);
    callCreate(d, 0);
    // The clone's [name] is blanked but the value is copied across.
    const helperInput = d.helpers[0]!.querySelector('input')!;
    expect(helperInput.value).toBe('hello');
  });
});

// ---------------------------------------------------------------------------
// Drag — helper geometry
// ---------------------------------------------------------------------------

describe('Drag.getHelperTargetX/Y + _getHelperTarget', () => {
  function setup(settings = {}): Drag {
    const d = new Drag(null, settings);
    d.mouseX = 200;
    d.mouseY = 120;
    d.mouseOffsetX = 30;
    d.mouseOffsetY = 20;
    return d;
  }

  it('subtracts the grab offset by default', () => {
    const d = setup();
    expect(d.getHelperTargetX()).toBe(170);
    expect(d.getHelperTargetY()).toBe(100);
  });

  it('snaps to the cursor when moveHelperToCursor is set (real=false)', () => {
    const d = setup({moveHelperToCursor: true});
    expect(d.getHelperTargetX()).toBe(200);
    expect(d.getHelperTargetY()).toBe(120);
  });

  it('ignores moveHelperToCursor when real=true', () => {
    const d = setup({moveHelperToCursor: true});
    expect(d.getHelperTargetX(true)).toBe(170);
    expect(d.getHelperTargetY(true)).toBe(100);
  });

  it('applies per-index spacing in _getHelperTarget', () => {
    const d = setup({helperSpacingX: 5, helperSpacingY: 8});
    const pos = (
      d as unknown as {
        _getHelperTarget: (
          i: number,
          r?: boolean
        ) => {left: number; top: number};
      }
    )._getHelperTarget(2, true);
    // 170 + 5*2 = 180 ; 100 + 8*2 = 116
    expect(pos).toEqual({left: 180, top: 116});
  });
});

// ---------------------------------------------------------------------------
// Drag — lag-follow loop
// ---------------------------------------------------------------------------

describe('Drag._updateHelperPos', () => {
  it('eases each helper toward its target and recomputes targets on move', () => {
    const helperEl = makeItem();
    const d = new Drag(null, {helperLagBase: 2});
    d.helpers = [helperEl];
    d.helperPositions = [{left: 0, top: 0}];
    d.helperTargets = [{left: 0, top: 0}];
    d.helperLagIncrement = 0;
    d.mouseX = 100;
    d.mouseY = 40;
    d.mouseOffsetX = 0;
    d.mouseOffsetY = 0;
    d.lastMouseX = null;
    d.lastMouseY = null;

    (d as unknown as {_updateHelperPos: () => void})._updateHelperPos();

    // Mouse moved → target recomputed to (100, 40). Eased by 1/lag (lag=2):
    // 0 + (100-0)/2 = 50 ; 0 + (40-0)/2 = 20
    expect(d.helperPositions[0]).toEqual({left: 50, top: 20});
    expect(helperEl.style.left).toBe('50px');
    expect(helperEl.style.top).toBe('20px');
    expect(d.lastMouseX).toBe(100);
  });
});

// ---------------------------------------------------------------------------
// Drag — startDragging / stopDragging ordering
// ---------------------------------------------------------------------------

describe('Drag.startDragging / stopDragging', () => {
  it('fires onBeforeDragStart first, snapshots, builds otherItems, mutes events', () => {
    const a = makeItem();
    const b = makeItem();
    const order: string[] = [];
    const d = new Drag([a, b], {
      onBeforeDragStart: () => order.push('before'),
    });
    d.$targetItem = a;
    // setDraggee is the snapshot step; record ordering relative to onBefore.
    vi.spyOn(d, 'setDraggee').mockImplementation((set) => {
      order.push('setDraggee');
      d.$draggee = set;
    });
    // Avoid driving the self-rescheduling lag loop; we only assert wiring.
    vi.spyOn(
      d as unknown as DragPrivate,
      '_updateHelperPos'
    ).mockImplementation(() => {});

    d.startDragging();

    expect(order).toEqual(['before', 'setDraggee']);
    expect(d.dragging).toBe(true);
    expect(globals.activateEventsMuted).toBe(true);
    // otherItems = $items not in $draggee. $draggee = [a].
    expect(d.otherItems).toEqual([b]);
    expect(d.totalOtherItems).toBe(1);
    // Lag loop was scheduled.
    expect(d.updateHelperPosFrame).not.toBeNull();
  });

  it('cancels the lag-follow RAF in stopDragging', () => {
    const a = makeItem();
    const d = new Drag([a]);
    d.$targetItem = a;
    vi.spyOn(
      d as unknown as DragPrivate,
      '_updateHelperPos'
    ).mockImplementation(() => {});
    d.startDragging();
    expect(d.updateHelperPosFrame).not.toBeNull();
    d.stopDragging();
    expect(d.updateHelperPosFrame).toBeNull();
    expect(d.dragging).toBe(false);
  });
});

// ---------------------------------------------------------------------------
// Drag — drag() virtual midpoint
// ---------------------------------------------------------------------------

describe('Drag.drag', () => {
  it('computes the draggee virtual midpoint from fresh coords', () => {
    const a = makeItem();
    const d = new Drag([a]);
    d.mouseX = 100;
    d.mouseY = 60;
    d.mouseOffsetX = 20;
    d.mouseOffsetY = 10;
    d.targetItemWidth = 40;
    d.targetItemHeight = 20;
    // super.drag walks the auto-scroll path; stub onDrag to avoid RAF noise.
    vi.spyOn(d, 'onDrag').mockImplementation(() => {});
    d.drag(false);
    // mouseX - mouseOffsetX + width/2 = 100 - 20 + 20 = 100
    expect(d.draggeeVirtualMidpointX).toBe(100);
    // mouseY - mouseOffsetY + height/2 = 60 - 10 + 10 = 60
    expect(d.draggeeVirtualMidpointY).toBe(60);
  });
});

// ---------------------------------------------------------------------------
// Drag — returnHelpersToDraggees / fadeOutHelpers (no-WAAPI sync path)
// ---------------------------------------------------------------------------

describe('Drag.returnHelpersToDraggees', () => {
  it('restores draggees, removes helpers, toggles the flag, fires the hook', () => {
    const a = makeItem();
    const helper = makeItem();
    mockRect(a, 12, 8); // getOffset(a) → {top:12, left:8}
    const d = new Drag([a], {hideDraggee: true});
    d.$draggee = [a];
    d.helpers = [helper];
    d.draggeeDisplay = 'block';
    const onReturn = vi.fn();
    d.settings!.onReturnHelpersToDraggees = onReturn;

    d.returnHelpersToDraggees();

    // No element.animate in happy-dom → finalize() ran synchronously.
    expect(helper.parentElement).toBeNull(); // removed
    expect(d.helpers).toEqual([]);
    // _show() restores the stashed inline display (none was stashed here, so it
    // clears to ''), and visibility is cleared — matching jQuery .show().css().
    expect(a.style.visibility).toBe('');
    expect(d.allowDragging()).toBe(true); // flag toggled back off

    // onReturnHelpersToDraggees is RAF-deferred — flush it.
    flushRaf();
    expect(onReturn).toHaveBeenCalledTimes(1);
  });

  it('short-circuits to _showDraggee when there are no helpers', () => {
    const a = makeItem();
    const d = new Drag([a]);
    d.$draggee = [a];
    d.helpers = [];
    d.draggeeDisplay = 'block';
    d.returnHelpersToDraggees();
    expect(d.allowDragging()).toBe(true);
  });

  it('sets _returningHelpersToDraggees true mid-flight (gates allowDragging)', () => {
    // Force a pending state by stubbing prefersReducedMotion false AND providing
    // a helper with a fake animate that never finishes.
    const a = makeItem();
    const helper = makeItem();
    mockRect(a, 0, 0);
    helper.animate = vi.fn(() => {
      const listeners: Record<string, () => void> = {};
      return {
        addEventListener: (type: string, cb: () => void) => {
          listeners[type] = cb;
        },
        cancel: () => {},
      } as unknown as Animation;
    }) as never;
    const d = new Drag([a]);
    d.$draggee = [a];
    d.helpers = [helper];
    d.draggeeDisplay = 'block';
    d.returnHelpersToDraggees();
    // Animation never fired finish → still returning → dragging blocked.
    expect(d.allowDragging()).toBe(false);
  });
});

describe('Drag.fadeOutHelpers', () => {
  it('removes helpers immediately on the no-WAAPI path', () => {
    const h1 = makeItem();
    const h2 = makeItem();
    const d = new Drag();
    d.helpers = [h1, h2];
    d.fadeOutHelpers();
    expect(h1.parentElement).toBeNull();
    expect(h2.parentElement).toBeNull();
  });
});

// ---------------------------------------------------------------------------
// Drag — allowDragging + destroy
// ---------------------------------------------------------------------------

describe('Drag.allowDragging + destroy', () => {
  it('blocks dragging while returning helpers', () => {
    const d = new Drag();
    expect(d.allowDragging()).toBe(true);
    (
      d as unknown as {_returningHelpersToDraggees: boolean}
    )._returningHelpersToDraggees = true;
    expect(d.allowDragging()).toBe(false);
  });

  it('destroy cancels the lag RAF, cancels return anims, and removes helpers', () => {
    const helper = makeItem();
    const a = makeItem();
    const d = new Drag([a]);
    d.helpers = [helper];
    d.updateHelperPosFrame = 5;
    const cancel = vi.fn();
    (
      d as unknown as {_returnAnimations: Array<{cancel: () => void}>}
    )._returnAnimations = [{cancel}];

    d.destroy();

    expect(cancel).toHaveBeenCalledTimes(1);
    expect(helper.parentElement).toBeNull();
    expect(d.helpers).toEqual([]);
    expect(d.updateHelperPosFrame).toBeNull();
    expect(d.$items).toEqual([]); // super.destroy ran
  });
});

// ---------------------------------------------------------------------------
// DragDrop — settings + updateDropTargets
// ---------------------------------------------------------------------------

describe('DragDrop settings + defaults', () => {
  it('merges DragDrop.defaults over Drag/BaseDrag defaults', () => {
    const dd = new DragDrop({activeDropTargetClass: 'over'});
    expect(dd.settings!.activeDropTargetClass).toBe('over');
    expect(dd.settings!.dropTargets).toBeNull();
    // Inherited Drag default.
    expect(dd.settings!.hideDraggee).toBe(true);
    // Inherited BaseDrag default.
    expect(dd.settings!.ignoreHandleSelector).toBe(
      'input, textarea, button, select, .btn'
    );
  });

  it('exposes the documented DragDrop defaults', () => {
    expect(DragDrop.defaults.dropTargets).toBeNull();
    expect(DragDrop.defaults.activeDropTargetClass).toBe('active');
  });
});

describe('DragDrop.updateDropTargets', () => {
  it('resolves an element list', () => {
    const t1 = makeItem();
    const t2 = makeItem();
    const dd = new DragDrop({dropTargets: [t1, t2]});
    dd.updateDropTargets();
    expect(dd.$dropTargets).toEqual([t1, t2]);
  });

  it('resolves a selector string', () => {
    const t1 = makeItem('target');
    const t2 = makeItem('target');
    const dd = new DragDrop({dropTargets: '.target'});
    dd.updateDropTargets();
    expect(dd.$dropTargets).toEqual([t1, t2]);
  });

  it('resolves a resolver function', () => {
    const t1 = makeItem();
    const dd = new DragDrop({dropTargets: () => [t1]});
    dd.updateDropTargets();
    expect(dd.$dropTargets).toEqual([t1]);
  });

  it('collapses an empty result to null', () => {
    const dd = new DragDrop({dropTargets: () => []});
    dd.updateDropTargets();
    expect(dd.$dropTargets).toBeNull();
  });

  it('leaves $dropTargets null when dropTargets is null', () => {
    const dd = new DragDrop({dropTargets: null});
    dd.updateDropTargets();
    expect(dd.$dropTargets).toBeNull();
  });
});

// ---------------------------------------------------------------------------
// DragDrop — onDrag hit-detection
// ---------------------------------------------------------------------------

describe('DragDrop.onDrag hit-detection', () => {
  function setup(): {
    dd: DragDrop;
    t1: HTMLElement;
    t2: HTMLElement;
  } {
    const t1 = makeItem();
    const t2 = makeItem();
    // t1 box: 0..10 x 0..10 (page coords). t2 box: 100..110 x 100..110.
    mockRect(t1, 0, 0, 10, 10);
    mockRect(t2, 100, 100, 10, 10);
    const dd = new DragDrop();
    dd.$dropTargets = [t1, t2];
    // Avoid the RAF-deferred super.onDrag emitting noise.
    return {dd, t1, t2};
  }

  it('adds the active class and fires onDropTargetChange on enter', () => {
    const onChange = vi.fn();
    const {dd, t1} = setup();
    dd.settings!.onDropTargetChange = onChange;
    dd.mouseX = 5;
    dd.mouseY = 5;
    dd.onDrag();
    expect(dd.$activeDropTarget).toBe(t1);
    expect(t1.classList.contains('active')).toBe(true);
    expect(onChange).toHaveBeenCalledTimes(1);
    expect(onChange).toHaveBeenCalledWith(t1);
  });

  it('does not re-fire onDropTargetChange when the target is unchanged', () => {
    const onChange = vi.fn();
    const {dd} = setup();
    dd.settings!.onDropTargetChange = onChange;
    dd.mouseX = 5;
    dd.mouseY = 5;
    dd.onDrag();
    dd.onDrag();
    expect(onChange).toHaveBeenCalledTimes(1);
  });

  it('swaps classes and fires on a target→target change', () => {
    const onChange = vi.fn();
    const {dd, t1, t2} = setup();
    dd.settings!.onDropTargetChange = onChange;
    dd.mouseX = 5;
    dd.mouseY = 5;
    dd.onDrag();
    dd.mouseX = 105;
    dd.mouseY = 105;
    dd.onDrag();
    expect(t1.classList.contains('active')).toBe(false);
    expect(t2.classList.contains('active')).toBe(true);
    expect(dd.$activeDropTarget).toBe(t2);
    expect(onChange).toHaveBeenCalledTimes(2);
    expect(onChange).toHaveBeenLastCalledWith(t2);
  });

  it('fires with null on a target→none change and removes the class', () => {
    const onChange = vi.fn();
    const {dd, t1} = setup();
    dd.settings!.onDropTargetChange = onChange;
    dd.mouseX = 5;
    dd.mouseY = 5;
    dd.onDrag();
    dd.mouseX = 500;
    dd.mouseY = 500;
    dd.onDrag();
    expect(t1.classList.contains('active')).toBe(false);
    expect(dd.$activeDropTarget).toBeNull();
    expect(onChange).toHaveBeenLastCalledWith(null);
  });

  it('first match wins for overlapping targets', () => {
    const {dd, t1, t2} = setup();
    // Make both targets contain the cursor; t1 comes first in the list.
    mockRect(t1, 0, 0, 1000, 1000);
    mockRect(t2, 0, 0, 1000, 1000);
    dd.mouseX = 50;
    dd.mouseY = 50;
    dd.onDrag();
    expect(dd.$activeDropTarget).toBe(t1);
    void t2;
  });

  it('does nothing when there are no drop targets', () => {
    const dd = new DragDrop();
    dd.$dropTargets = null;
    const onChange = vi.fn();
    dd.settings!.onDropTargetChange = onChange;
    dd.mouseX = 5;
    dd.mouseY = 5;
    expect(() => dd.onDrag()).not.toThrow();
    expect(onChange).not.toHaveBeenCalled();
  });
});

describe('DragDrop.onDragStart / onDragStop', () => {
  it('onDragStart resolves drop targets and resets the active target', () => {
    const t1 = makeItem();
    const dd = new DragDrop({dropTargets: [t1]});
    dd.$activeDropTarget = t1;
    dd.onDragStart();
    expect(dd.$dropTargets).toEqual([t1]);
    expect(dd.$activeDropTarget).toBeNull();
  });

  it('onDragStop strips the active class when a target is active', () => {
    const t1 = makeItem();
    t1.classList.add('active');
    const dd = new DragDrop();
    dd.$dropTargets = [t1];
    dd.$activeDropTarget = t1;
    dd.onDragStop();
    expect(t1.classList.contains('active')).toBe(false);
  });
});
