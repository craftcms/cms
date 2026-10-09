import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';

import {Modal} from '../src/modal';
import {BaseDrag} from '../src/drag/base-drag';
import {DragMove} from '../src/drag-move';
import {UiLayerManager} from '../src/managers/ui-layer-manager';
import {setUiLayerManager} from '../src/managers/registry';
import {ESC_KEY} from '../src/constants';
import {globals} from '../src/globals';

// happy-dom does not implement `element.animate`, so Modal's `_fade` takes its
// immediate-complete fallback path — fade-in/out resolve synchronously here,
// which keeps these assertions deterministic. (Real browsers still animate.)

let manager: UiLayerManager;

function makeContainer(): HTMLElement {
  const el = document.createElement('div');
  el.className = 'modal';
  // Give it something focusable so setFocusWithin has a target.
  const input = document.createElement('input');
  input.type = 'text';
  // happy-dom has no layout; stub getClientRects so the focusable matcher
  // treats the input as visible (per core impl note #7).
  vi.spyOn(input, 'getClientRects').mockReturnValue([
    {width: 10, height: 10},
  ] as unknown as DOMRectList);
  el.appendChild(input);
  document.body.appendChild(el);
  return el;
}

beforeEach(() => {
  // Fresh UI layer manager per test; register it so Modal can find it.
  manager = new UiLayerManager();
  setUiLayerManager(manager);
  Modal.instances = [];
  Modal.visibleModal = null;
  document.body.innerHTML = '';
  document.body.className = '';
});

afterEach(() => {
  vi.restoreAllMocks();
});

describe('Modal construction', () => {
  it('applies defaults merged with passed settings', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: false, minGutter: 25});
    expect(modal.settings!.minGutter).toBe(25);
    expect(modal.settings!.hideOnEsc).toBe(true);
    expect(modal.settings!.shadeClass).toBe('modal-shade');
    expect(modal.settings!.draggable).toBe(false);
  });

  it('supports the param-shift form: new Modal(settings)', () => {
    const modal = new Modal({autoShow: false, closeOtherModals: true});
    expect(modal.$container).toBeNull();
    expect(modal.settings!.closeOtherModals).toBe(true);
    expect(modal.settings!.autoShow).toBe(false);
  });

  it('registers itself in the static instances list', () => {
    const modal = new Modal({autoShow: false});
    expect(Modal.instances).toContain(modal);
  });

  it('creates a shade element with the configured class', () => {
    const modal = new Modal({autoShow: false, shadeClass: 'custom-shade'});
    expect(modal.$shade).not.toBeNull();
    expect(modal.$shade!.classList.contains('custom-shade')).toBe(true);
  });

  it('auto-shows when autoShow is true and a container is given', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: true});
    expect(modal.visible).toBe(true);
    expect(Modal.visibleModal).toBe(modal);
  });

  it('does not auto-show with autoShow:false', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: false});
    expect(modal.visible).toBe(false);
  });
});

describe('Modal ARIA + focus', () => {
  it('applies dialog/aria-modal attributes to the container', () => {
    const container = makeContainer();
    new Modal(container, {autoShow: false});
    expect(container.getAttribute('role')).toBe('dialog');
    expect(container.getAttribute('aria-modal')).toBe('true');
  });

  it('adds a live region inside the container', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: false});
    expect(container.contains(modal.$liveRegion)).toBe(true);
    expect(modal.$liveRegion.getAttribute('role')).toBe('status');
  });

  it('moves focus inside the container on show', () => {
    const container = makeContainer();
    const input = container.querySelector('input')!;
    const modal = new Modal(container, {autoShow: false});
    modal.show();
    expect(document.activeElement).toBe(input);
  });
});

describe('Modal show/hide', () => {
  it('show() toggles visible and fires the show event', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: false});
    const onShow = vi.fn();
    modal.on('show', onShow);
    modal.show();
    expect(modal.visible).toBe(true);
    expect(onShow).toHaveBeenCalledOnce();
    expect(document.body.classList.contains('no-scroll')).toBe(true);
  });

  it('fires fadeIn after the fade completes', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: false});
    const onFadeIn = vi.fn();
    modal.on('fadeIn', onFadeIn);
    modal.show();
    expect(onFadeIn).toHaveBeenCalledOnce();
  });

  it('calls the onShow settings callback', () => {
    const container = makeContainer();
    const onShow = vi.fn();
    const modal = new Modal(container, {autoShow: false, onShow});
    modal.show();
    expect(onShow).toHaveBeenCalledOnce();
  });

  it('hide() toggles visible and fires the hide event', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: true});
    const onHide = vi.fn();
    modal.on('hide', onHide);
    modal.hide();
    expect(modal.visible).toBe(false);
    expect(onHide).toHaveBeenCalledOnce();
    expect(Modal.visibleModal).toBeNull();
    expect(document.body.classList.contains('no-scroll')).toBe(false);
  });

  it('hide() is a no-op when not visible', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: false});
    const onHide = vi.fn();
    modal.on('hide', onHide);
    modal.hide();
    expect(onHide).not.toHaveBeenCalled();
  });

  it('show() registers a layer and hide() removes it', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: false});
    expect(manager.layer).toBe(0);
    modal.show();
    expect(manager.layer).toBe(1);
    modal.hide();
    expect(manager.layer).toBe(0);
  });

  it('closeOtherModals hides the currently visible modal', () => {
    const a = new Modal(makeContainer(), {autoShow: true});
    const b = new Modal(makeContainer(), {
      autoShow: false,
      closeOtherModals: true,
    });
    expect(a.visible).toBe(true);
    b.show();
    expect(a.visible).toBe(false);
    expect(b.visible).toBe(true);
  });

  it('quickShow leaves the modal visible with full opacity', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: false});
    modal.quickShow();
    expect(modal.visible).toBe(true);
    expect(container.style.opacity).toBe('1');
  });

  it('quickHide leaves the modal hidden', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: true});
    modal.quickHide();
    expect(modal.visible).toBe(false);
    expect(container.style.display).toBe('none');
  });
});

describe('Modal ESC + shade-click closing', () => {
  it('closes on ESC when hideOnEsc is true', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: true, hideOnEsc: true});
    const onEscape = vi.fn();
    modal.on('escape', onEscape);

    const ev = new KeyboardEvent('keydown', {keyCode: ESC_KEY} as never);
    manager.triggerShortcut(ev);

    expect(onEscape).toHaveBeenCalledOnce();
    expect(modal.visible).toBe(false);
  });

  it('does not register an ESC shortcut when hideOnEsc is false', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: true, hideOnEsc: false});

    const ev = new KeyboardEvent('keydown', {keyCode: ESC_KEY} as never);
    manager.triggerShortcut(ev);

    expect(modal.visible).toBe(true);
  });

  it('closes when the shade is clicked (hideOnShadeClick)', () => {
    const container = makeContainer();
    const modal = new Modal(container, {
      autoShow: true,
      hideOnShadeClick: true,
    });
    modal.$shade!.dispatchEvent(new MouseEvent('click', {bubbles: true}));
    expect(modal.visible).toBe(false);
  });

  it('does not close on shade click when hideOnShadeClick is false', () => {
    const container = makeContainer();
    const modal = new Modal(container, {
      autoShow: true,
      hideOnShadeClick: false,
    });
    modal.$shade!.dispatchEvent(new MouseEvent('click', {bubbles: true}));
    expect(modal.visible).toBe(true);
  });
});

describe('Modal getWidth/getHeight/updateSizeAndPosition', () => {
  it('getWidth/getHeight throw when no container is set', () => {
    const modal = new Modal({autoShow: false});
    expect(() => modal.getWidth()).toThrow();
    expect(() => modal.getHeight()).toThrow();
  });

  it('updateSizeAndPosition is a no-op without a container', () => {
    const modal = new Modal({autoShow: false});
    expect(() => modal.updateSizeAndPosition()).not.toThrow();
  });

  it('updateSizeAndPosition centers the container and fires its event', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: false});
    const onUpdate = vi.fn();
    modal.on('updateSizeAndPosition', onUpdate);
    modal.updateSizeAndPosition();
    expect(onUpdate).toHaveBeenCalled();
    expect(container.style.left).not.toBe('');
    expect(container.style.top).not.toBe('');
  });
});

describe('Modal destroy', () => {
  it('removes the instance, container, and shade and fires destroy', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: false});
    const onDestroy = vi.fn();
    modal.on('destroy', onDestroy);

    modal.destroy();

    expect(onDestroy).toHaveBeenCalledOnce();
    expect(Modal.instances).not.toContain(modal);
    expect(document.body.contains(container)).toBe(false);
    expect(document.body.contains(modal.$shade!)).toBe(false);
  });

  it('clears visibleModal if the destroyed modal was visible', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: true});
    expect(Modal.visibleModal).toBe(modal);
    modal.destroy();
    expect(Modal.visibleModal).toBeNull();
  });
});

describe('Modal draggable/resizable', () => {
  it('constructs without throwing and creates a DragMove when draggable', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: false, draggable: true});
    expect(modal.dragger).toBeInstanceOf(DragMove);
    // The container itself is the default handle (registered with the dragger).
    expect(modal.dragger!.$items).toContain(container);
  });

  it('uses the dragHandleSelector as the DragMove handle when given', () => {
    const container = makeContainer();
    const handle = document.createElement('div');
    handle.className = 'drag-handle';
    container.appendChild(handle);
    const modal = new Modal(container, {
      autoShow: false,
      draggable: true,
      dragHandleSelector: '.drag-handle',
    });
    // The item is still the container; only the handle differs (drag from it).
    expect(modal.dragger).toBeInstanceOf(DragMove);
    expect(modal.dragger!.$items).toContain(container);
  });

  it('constructs without throwing and creates a BaseDrag + resize handle when resizable', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: false, resizable: true});
    expect(modal.resizeDragger).toBeInstanceOf(BaseDrag);
    const handle = container.querySelector('.resizehandle');
    expect(handle).not.toBeNull();
    expect(modal.resizeDragger!.$items).toContain(handle);
  });

  it('does not create draggers by default', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: false});
    expect(modal.dragger).toBeNull();
    expect(modal.resizeDragger).toBeNull();
  });

  it('_handleResize grows width/height symmetrically (ltr) from the start size', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: false, resizable: true});
    document.body.classList.remove('rtl');
    globals.rtl = false;

    vi.spyOn(modal, 'getWidth').mockReturnValue(400);
    vi.spyOn(modal, 'getHeight').mockReturnValue(300);
    const update = vi
      .spyOn(modal, 'updateSizeAndPosition')
      .mockImplementation(() => {});

    // Drive the resize handlers directly with a fake dragger delta.
    (modal as unknown as {_handleResizeStart(): void})._handleResizeStart();
    modal.resizeDragger!.mouseDistX = 10;
    modal.resizeDragger!.mouseDistY = 5;
    (modal as unknown as {_handleResize(): void})._handleResize();

    expect(modal.desiredWidth).toBe(400 + 10 * 2);
    expect(modal.desiredHeight).toBe(300 + 5 * 2);
    expect(update).toHaveBeenCalled();
  });

  it('_handleResize mirrors the horizontal direction in rtl', () => {
    const container = makeContainer();
    const modal = new Modal(container, {autoShow: false, resizable: true});
    globals.rtl = true;

    vi.spyOn(modal, 'getWidth').mockReturnValue(400);
    vi.spyOn(modal, 'getHeight').mockReturnValue(300);
    vi.spyOn(modal, 'updateSizeAndPosition').mockImplementation(() => {});

    (modal as unknown as {_handleResizeStart(): void})._handleResizeStart();
    modal.resizeDragger!.mouseDistX = 10;
    modal.resizeDragger!.mouseDistY = 5;
    (modal as unknown as {_handleResize(): void})._handleResize();

    expect(modal.desiredWidth).toBe(400 - 10 * 2);
    expect(modal.desiredHeight).toBe(300 + 5 * 2);

    globals.rtl = false;
  });

  it('tears down both draggers on destroy', () => {
    const container = makeContainer();
    const modal = new Modal(container, {
      autoShow: false,
      draggable: true,
      resizable: true,
    });
    const dragDestroy = vi.spyOn(modal.dragger!, 'destroy');
    const resizeDestroy = vi.spyOn(modal.resizeDragger!, 'destroy');
    modal.destroy();
    expect(dragDestroy).toHaveBeenCalledOnce();
    expect(resizeDestroy).toHaveBeenCalledOnce();
  });
});
