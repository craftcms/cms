import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';

import {HUD} from '../src/hud';
import {UiLayerManager} from '../src/managers/ui-layer-manager';
import {setUiLayerManager} from '../src/managers/registry';
import {ESC_KEY, TAB_KEY} from '../src/constants';
import {globals} from '../src/globals';

// happy-dom notes:
// - No layout: `getBoundingClientRect()` returns zeros, `offsetWidth/Height`
//   and `clientWidth/Height` are 0. We stub those where the positioning math
//   needs known values (`mockRect` / `setContentSize`).
// - `requestAnimationFrame` is mocked onto a manual queue so the deferred
//   `updateSizeAndPositionInternal` runs only when we `flushRaf()` (a synchronous
//   stub is fine for HUD, but the queue keeps the scheduling observable).
// - The focusable matcher reads layout boxes, so focusable elements get their
//   `getClientRects` stubbed to look visible (per the Modal/focusable tests).
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

/** Run every currently-queued RAF callback once. */
function flushRaf(): void {
  const current = rafQueue;
  rafQueue = [];
  for (const cb of current) {
    cb?.(0);
  }
}

let manager: UiLayerManager;

/** A visible, focusable trigger button. */
function makeTrigger(): HTMLButtonElement {
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.textContent = 'Open';
  makeVisible(btn);
  document.body.appendChild(btn);
  return btn;
}

/** Stub an element's layout boxes so the focusable matcher treats it as visible. */
function makeVisible(el: Element): void {
  vi.spyOn(el, 'getClientRects').mockReturnValue([
    {width: 10, height: 10},
  ] as unknown as DOMRectList);
}

/** Stub an element's bounding rect (positioning math). */
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
  manager = new UiLayerManager();
  setUiLayerManager(manager);
  HUD.instances = [];
  HUD.activeHUDs = {};
  rafQueue = [];
  document.body.innerHTML = '';
  document.body.className = '';
  globals.scrollContainer = window;
  globals.rtl = false;
});

afterEach(() => {
  vi.restoreAllMocks();
});

describe('HUD construction', () => {
  it('applies defaults merged with passed settings', () => {
    const hud = new HUD(makeTrigger(), {
      showOnInit: false,
      triggerSpacing: 25,
    });
    expect(hud.settings!.triggerSpacing).toBe(25);
    expect(hud.settings!.windowSpacing).toBe(10);
    expect(hud.settings!.hudClass).toBe('hud');
    expect(hud.settings!.withShade).toBe(true);
    expect(hud.settings!.orientations).toEqual([
      'bottom',
      'top',
      'right',
      'left',
    ]);
  });

  it('supports the param-shift form: new HUD(trigger, settings)', () => {
    const hud = new HUD(makeTrigger(), {
      showOnInit: false,
      hudClass: 'hud custom',
    });
    expect(hud.settings!.hudClass).toBe('hud custom');
    expect(hud.$main!.innerHTML).toBe('');
  });

  it('treats a third-arg settings object as settings (full signature)', () => {
    const hud = new HUD(makeTrigger(), '<p>body</p>', {showOnInit: false});
    expect(hud.settings!.showOnInit).toBe(false);
    expect(hud.$main!.querySelector('p')!.textContent).toBe('body');
  });

  it('builds the hud > tip / body > mainContainer > main tree', () => {
    const hud = new HUD(makeTrigger(), {showOnInit: false});
    expect(hud.$hud!.classList.contains('hud')).toBe(true);
    expect(hud.$tip!.parentElement).toBe(hud.$hud);
    expect(hud.$body!.tagName).toBe('FORM');
    expect(hud.$body!.parentElement).toBe(hud.$hud);
    // ($body is a <form>; happy-dom hands back distinct proxies through the form,
    // so assert the nesting via a selector from $hud rather than node identity.)
    expect(
      hud.$hud!.querySelector('form.body > .main-container > .main')
    ).not.toBeNull();
    expect(hud.$main!.classList.contains('main')).toBe(true);
  });

  it('creates a shade with the configured class when withShade', () => {
    const hud = new HUD(makeTrigger(), {
      showOnInit: false,
      shadeClass: 'custom-shade',
    });
    expect(hud.$shade).not.toBeNull();
    expect(hud.$shade!.classList.contains('custom-shade')).toBe(true);
  });

  it('omits the shade when withShade is false', () => {
    const hud = new HUD(makeTrigger(), {showOnInit: false, withShade: false});
    expect(hud.$shade).toBeNull();
  });

  it('sets aria-expanded=false on the trigger', () => {
    const trigger = makeTrigger();
    new HUD(trigger, {showOnInit: false});
    expect(trigger.getAttribute('aria-expanded')).toBe('false');
  });

  it('registers itself in the static instances list', () => {
    const hud = new HUD(makeTrigger(), {showOnInit: false});
    expect(HUD.instances).toContain(hud);
  });

  it('positions absolute by default (no fixed ancestor)', () => {
    const hud = new HUD(makeTrigger(), {showOnInit: false});
    expect(hud.$hud!.style.position).toBe('absolute');
    expect(hud.$fixedTriggerParent).toBeNull();
  });

  it('auto-shows when showOnInit is true (the default)', () => {
    const trigger = makeTrigger();
    const hud = new HUD(trigger);
    expect(hud.showing).toBe(true);
    expect(trigger.getAttribute('aria-expanded')).toBe('true');
  });
});

describe('HUD updateBody', () => {
  it('extracts a header and footer and flags the hud', () => {
    const hud = new HUD(makeTrigger(), {showOnInit: false});
    hud.updateBody(
      '<div class="hud-header">H</div><p>mid</p><div class="hud-footer">F</div>'
    );
    expect(hud.$header).not.toBeNull();
    expect(hud.$footer).not.toBeNull();
    expect(hud.$hud!.classList.contains('has-header')).toBe(true);
    expect(hud.$hud!.classList.contains('has-footer')).toBe(true);
    // Header hoisted before the main container; footer after it.
    expect(hud.$header!.nextElementSibling).toBe(hud.$mainContainer);
    expect(hud.$footer!.previousElementSibling).toBe(hud.$mainContainer);
  });

  it('clears a previous header/footer when the body is replaced', () => {
    const hud = new HUD(makeTrigger(), {showOnInit: false});
    hud.updateBody('<div class="hud-header">H</div>');
    expect(hud.$hud!.classList.contains('has-header')).toBe(true);
    hud.updateBody('<p>plain</p>');
    expect(hud.$header).toBeNull();
    expect(hud.$hud!.classList.contains('has-header')).toBe(false);
  });

  it('appends node content', () => {
    const hud = new HUD(makeTrigger(), {showOnInit: false});
    const node = document.createElement('span');
    node.textContent = 'hi';
    hud.updateBody(node);
    expect(hud.$main!.contains(node)).toBe(true);
  });
});

describe('HUD show/hide', () => {
  it('show() toggles showing and fires show', () => {
    const hud = new HUD(makeTrigger(), {showOnInit: false});
    const onShow = vi.fn();
    hud.on('show', onShow);
    hud.show();
    expect(hud.showing).toBe(true);
    expect(onShow).toHaveBeenCalledOnce();
  });

  it('invokes the onShow settings callback (registered as a handler)', () => {
    const onShow = vi.fn();
    const hud = new HUD(makeTrigger(), {showOnInit: false, onShow});
    hud.show();
    expect(onShow).toHaveBeenCalledOnce();
  });

  it('sets aria-expanded=true on show and false on hide', () => {
    const trigger = makeTrigger();
    const hud = new HUD(trigger, {showOnInit: false});
    hud.show();
    expect(trigger.getAttribute('aria-expanded')).toBe('true');
    hud.hide();
    expect(trigger.getAttribute('aria-expanded')).toBe('false');
  });

  it('show() is a no-op when already showing', () => {
    const hud = new HUD(makeTrigger(), {showOnInit: false});
    hud.show();
    const onShow = vi.fn();
    hud.on('show', onShow);
    hud.show();
    expect(onShow).not.toHaveBeenCalled();
  });

  it('hide() toggles showing and fires hide', () => {
    const hud = new HUD(makeTrigger(), {showOnInit: true});
    const onHide = vi.fn();
    hud.on('hide', onHide);
    hud.hide();
    expect(hud.showing).toBe(false);
    expect(onHide).toHaveBeenCalledOnce();
  });

  it('hide() is a no-op when not showing', () => {
    const hud = new HUD(makeTrigger(), {showOnInit: false});
    const onHide = vi.fn();
    hud.on('hide', onHide);
    hud.hide();
    expect(onHide).not.toHaveBeenCalled();
  });

  it('toggle() flips between show and hide', () => {
    const hud = new HUD(makeTrigger(), {showOnInit: false});
    hud.toggle();
    expect(hud.showing).toBe(true);
    hud.toggle();
    expect(hud.showing).toBe(false);
  });

  it('tracks itself in activeHUDs while showing', () => {
    const hud = new HUD(makeTrigger(), {showOnInit: true});
    expect(Object.values(HUD.activeHUDs)).toContain(hud);
    hud.hide();
    expect(Object.values(HUD.activeHUDs)).not.toContain(hud);
  });

  it('closeOtherHUDs hides any other open HUD on show', () => {
    const a = new HUD(makeTrigger(), {showOnInit: true, closeOtherHUDs: true});
    expect(a.showing).toBe(true);
    const b = new HUD(makeTrigger(), {showOnInit: true, closeOtherHUDs: true});
    expect(b.showing).toBe(true);
    expect(a.showing).toBe(false);
  });

  it('hideContainer/showContainer toggle the container display', () => {
    const hud = new HUD(makeTrigger(), {showOnInit: false});
    hud.hideContainer();
    expect(hud.$hud!.style.display).toBe('none');
    hud.showContainer();
    expect(hud.$hud!.style.display).toBe('block');
  });
});

describe('HUD layer + Esc + shade click', () => {
  it('adds a UI layer on show and removes it on hide', () => {
    const hud = new HUD(makeTrigger(), {showOnInit: false});
    expect(manager.layer).toBe(0);
    hud.show();
    expect(manager.layer).toBe(1);
    hud.hide();
    expect(manager.layer).toBe(0);
  });

  it('closes on Escape when hideOnEsc is true', () => {
    const hud = new HUD(makeTrigger(), {showOnInit: true, hideOnEsc: true});
    const ev = new KeyboardEvent('keydown', {keyCode: ESC_KEY} as never);
    manager.triggerShortcut(ev);
    expect(hud.showing).toBe(false);
  });

  it('does not register an Esc shortcut when hideOnEsc is false', () => {
    const hud = new HUD(makeTrigger(), {showOnInit: true, hideOnEsc: false});
    const ev = new KeyboardEvent('keydown', {keyCode: ESC_KEY} as never);
    manager.triggerShortcut(ev);
    expect(hud.showing).toBe(true);
  });

  it('closes when the shade is clicked (hideOnShadeClick)', () => {
    const hud = new HUD(makeTrigger(), {
      showOnInit: true,
      hideOnShadeClick: true,
    });
    hud.$shade!.dispatchEvent(new MouseEvent('click', {bubbles: true}));
    expect(hud.showing).toBe(false);
  });
});

describe('HUD submit', () => {
  it('submit() fires the submit event', () => {
    const hud = new HUD(makeTrigger(), {showOnInit: false});
    const onSubmit = vi.fn();
    hud.on('submit', onSubmit);
    hud.submit();
    expect(onSubmit).toHaveBeenCalledOnce();
  });

  it('a body form submit is intercepted and re-fired as submit', () => {
    const hud = new HUD(makeTrigger(), {showOnInit: false});
    const onSubmit = vi.fn();
    hud.on('submit', onSubmit);
    const ev = new Event('submit', {bubbles: true, cancelable: true});
    hud.$body!.dispatchEvent(ev);
    expect(onSubmit).toHaveBeenCalledOnce();
    expect(ev.defaultPrevented).toBe(true);
  });

  it('invokes the onSubmit settings callback', () => {
    const onSubmit = vi.fn();
    const hud = new HUD(makeTrigger(), {showOnInit: false, onSubmit});
    hud.submit();
    expect(onSubmit).toHaveBeenCalledOnce();
  });
});

describe('HUD focus management', () => {
  it('Tab on the trigger while showing moves focus into the HUD', () => {
    const trigger = makeTrigger();
    const hud = new HUD(trigger, {showOnInit: false});
    const input = document.createElement('input');
    makeVisible(input);
    hud.updateBody(input);
    hud.show();

    const ev = new KeyboardEvent('keydown', {keyCode: TAB_KEY} as never);
    trigger.dispatchEvent(ev);
    expect(document.activeElement).toBe(input);
  });

  it('restores focus to the trigger on hide when focus was inside', () => {
    const trigger = makeTrigger();
    const hud = new HUD(trigger, {showOnInit: false});
    const input = document.createElement('input');
    makeVisible(input);
    hud.updateBody(input);
    hud.show();
    input.focus();
    expect(document.activeElement).toBe(input);
    hud.hide();
    expect(document.activeElement).toBe(trigger);
  });
});

describe('HUD updateSizeAndPosition (mocked layout)', () => {
  /** Build a HUD with a mocked trigger rect + body content box. */
  function buildPositioned(
    triggerTop: number,
    triggerLeft: number,
    triggerW: number,
    triggerH: number,
    bodyW: number,
    bodyH: number,
    settings: Partial<ConstructorParameters<typeof HUD>[1] & object> = {}
  ): HUD {
    const trigger = makeTrigger();
    mockRect(trigger, triggerTop, triggerLeft, triggerW, triggerH);
    // outerWidth/Height read offsetWidth/Height — stub those too.
    Object.defineProperty(trigger, 'offsetWidth', {
      value: triggerW,
      configurable: true,
    });
    Object.defineProperty(trigger, 'offsetHeight', {
      value: triggerH,
      configurable: true,
    });
    const hud = new HUD(trigger, {showOnInit: false, ...settings});
    // The body's content box (jQuery `.width()/.height()` → rect minus 0 pad/border).
    mockRect(hud.$body!, 0, 0, bodyW, bodyH);
    hud.windowWidth = 1000;
    hud.windowHeight = 800;
    return hud;
  }

  it('picks "bottom" when there is room below the trigger', () => {
    const hud = buildPositioned(100, 450, 50, 20, 200, 200);
    hud.updateSizeAndPositionInternal();
    expect(hud.orientation).toBe('bottom');
    // tip points back up at the trigger.
    expect(hud.$tip!.classList.contains('tip-top')).toBe(true);
  });

  it('falls through to "right" when top/bottom are too short for a tall body', () => {
    // Trigger near the vertical center, against the left edge; body taller than
    // top/bottom clearance but narrower than the right clearance.
    const hud = buildPositioned(400, 0, 50, 20, 200, 700);
    hud.updateSizeAndPositionInternal();
    expect(hud.orientation).toBe('right');
    expect(hud.$tip!.classList.contains('tip-left')).toBe(true);
  });

  it('respects the orientations preference order', () => {
    const hud = buildPositioned(400, 0, 50, 20, 200, 200, {
      orientations: ['right', 'bottom', 'left'],
    });
    hud.updateSizeAndPositionInternal();
    expect(hud.orientation).toBe('right');
  });

  it('sets the hud left/top and fires updateSizeAndPosition', () => {
    const hud = buildPositioned(100, 450, 50, 20, 200, 200);
    const onUpdate = vi.fn();
    hud.on('updateSizeAndPosition', onUpdate);
    hud.updateSizeAndPositionInternal();
    expect(onUpdate).toHaveBeenCalledOnce();
    expect(hud.$hud!.style.left).not.toBe('');
    expect(hud.$hud!.style.top).not.toBe('');
  });

  it('updateSizeAndPosition(true) schedules a deferred reposition', () => {
    const hud = buildPositioned(100, 450, 50, 20, 200, 200);
    const internal = vi.spyOn(hud, 'updateSizeAndPositionInternal');
    hud.updateSizeAndPosition(true);
    expect(internal).not.toHaveBeenCalled(); // deferred to RAF
    flushRaf();
    expect(internal).toHaveBeenCalledOnce();
  });
});

describe('HUD updateRecords', () => {
  it('reports a change on first measurement and stability afterward', () => {
    const hud = new HUD(makeTrigger(), {showOnInit: false});
    expect(hud.updateRecords()).toBe(true);
    expect(hud.updateRecords()).toBe(false);
  });
});

describe('HUD destroy', () => {
  it('removes the hud + shade, drops from instances/activeHUDs, fires destroy', () => {
    const trigger = makeTrigger();
    const hud = new HUD(trigger, {showOnInit: true});
    const onDestroy = vi.fn();
    hud.on('destroy', onDestroy);
    const shade = hud.$shade!;
    const container = hud.$hud!;

    hud.destroy();

    expect(onDestroy).toHaveBeenCalledOnce();
    expect(HUD.instances).not.toContain(hud);
    expect(Object.values(HUD.activeHUDs)).not.toContain(hud);
    expect(document.body.contains(container)).toBe(false);
    expect(document.body.contains(shade)).toBe(false);
  });
});
