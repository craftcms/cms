import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';

import {Modal} from '../src/modal';
import {UiLayerManager} from '../src/managers/ui-layer-manager';
import {setUiLayerManager} from '../src/managers/registry';
import {ESC_KEY} from '../src/constants';

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

describe('Modal draggable/resizable (PoC unsupported)', () => {
  it('throws a clear error when draggable is enabled', () => {
    const container = makeContainer();
    expect(
      () => new Modal(container, {autoShow: false, draggable: true})
    ).toThrow(/not yet supported/);
  });

  it('throws a clear error when resizable is enabled', () => {
    const container = makeContainer();
    expect(
      () => new Modal(container, {autoShow: false, resizable: true})
    ).toThrow(/not yet supported/);
  });
});
