import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';

import {EscManager} from '../src/managers/esc-manager';
import {UiLayerManager} from '../src/managers/ui-layer-manager';

describe('EscManager', () => {
  it('escapeLatest pops the most recently registered handler', () => {
    const mgr = new EscManager();
    const first = vi.fn();
    const second = vi.fn();
    const objA = {};
    const objB = {};
    mgr.register(objA, first);
    mgr.register(objB, second);

    mgr.escapeLatest(new KeyboardEvent('keyup'));
    expect(second).toHaveBeenCalledOnce();
    expect(first).not.toHaveBeenCalled();

    mgr.escapeLatest(new KeyboardEvent('keyup'));
    expect(first).toHaveBeenCalledOnce();
  });

  it('unregister removes a handler by object', () => {
    const mgr = new EscManager();
    const fn = vi.fn();
    const obj = {};
    mgr.register(obj, fn);
    mgr.unregister(obj);
    mgr.escapeLatest(new KeyboardEvent('keyup'));
    expect(fn).not.toHaveBeenCalled();
  });

  it('triggers `escape` on the registered object when possible', () => {
    const mgr = new EscManager();
    const trigger = vi.fn();
    const obj = {trigger, handle: vi.fn()};
    mgr.register(obj, 'handle');
    mgr.escapeLatest(new KeyboardEvent('keyup'));
    expect(obj.handle).toHaveBeenCalledOnce();
    expect(trigger).toHaveBeenCalledWith('escape');
  });
});

describe('UiLayerManager', () => {
  let mgr: UiLayerManager;

  beforeEach(() => {
    mgr = new UiLayerManager();
  });

  it('starts with a single base layer', () => {
    expect(mgr.layer).toBe(0);
    expect(mgr.currentLayer.$container).toBe(document.body);
  });

  it('addLayer pushes a new layer and detects modals', () => {
    const container = document.createElement('div');
    container.setAttribute('aria-modal', 'true');
    mgr.addLayer(container);
    expect(mgr.layer).toBe(1);
    expect(mgr.currentLayer.isModal).toBe(true);
    expect(mgr.highestModalLayer?.$container).toBe(container);
  });

  it('addLayer accepts an options-only call (param shift)', () => {
    mgr.addLayer({bubble: true});
    expect(mgr.currentLayer.$container).toBeNull();
    expect(mgr.currentLayer.options.bubble).toBe(true);
  });

  it('removeLayer pops the top layer', () => {
    mgr.addLayer(document.createElement('div'));
    mgr.removeLayer();
    expect(mgr.layer).toBe(0);
  });

  it('throws when removing the base layer', () => {
    expect(() => mgr.removeLayer()).toThrow();
  });

  it('triggerShortcut fires a registered shortcut callback', () => {
    const cb = vi.fn();
    mgr.registerShortcut(27, cb);
    const ev = new KeyboardEvent('keydown', {keyCode: 27} as never);
    vi.spyOn(ev, 'preventDefault');
    mgr.triggerShortcut(ev);
    expect(cb).toHaveBeenCalledOnce();
    expect(ev.preventDefault).toHaveBeenCalled();
  });
});
