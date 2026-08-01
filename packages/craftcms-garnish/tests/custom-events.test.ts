import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';

import {installActivate} from '../src/custom-events';
import {globals} from '../src/globals';

describe('installActivate', () => {
  let el: HTMLButtonElement;

  beforeEach(() => {
    globals.activateEventsMuted = false;
    el = document.createElement('button');
    document.body.appendChild(el);
  });

  afterEach(() => {
    el.remove();
  });

  it('dispatches an `activate` event on click', () => {
    const dispose = installActivate(el);
    const fn = vi.fn();
    el.addEventListener('activate', fn);
    el.dispatchEvent(new MouseEvent('click', {bubbles: true}));
    expect(fn).toHaveBeenCalledOnce();
    dispose();
  });

  it('dispatches `activate` on Space/Enter keydown', () => {
    const dispose = installActivate(el);
    const fn = vi.fn();
    el.addEventListener('activate', fn);
    el.dispatchEvent(
      new KeyboardEvent('keydown', {keyCode: 32, bubbles: true} as never)
    );
    expect(fn).toHaveBeenCalledOnce();
    dispose();
  });

  it('does not dispatch `activate` when disabled (class)', () => {
    el.classList.add('disabled');
    const dispose = installActivate(el);
    const fn = vi.fn();
    el.addEventListener('activate', fn);
    el.dispatchEvent(new MouseEvent('click', {bubbles: true}));
    expect(fn).not.toHaveBeenCalled();
    dispose();
  });

  it('does not dispatch `activate` when activate events are muted', () => {
    const dispose = installActivate(el);
    const fn = vi.fn();
    el.addEventListener('activate', fn);
    globals.activateEventsMuted = true;
    el.dispatchEvent(new MouseEvent('click', {bubbles: true}));
    expect(fn).not.toHaveBeenCalled();
    dispose();
  });

  it('sets tabindex="0" on a non-disabled element', () => {
    const dispose = installActivate(el);
    expect(el.getAttribute('tabindex')).toBe('0');
    dispose();
  });

  it('ref-counts: a single dispose does not tear down a doubly-installed element', () => {
    const dispose1 = installActivate(el);
    const dispose2 = installActivate(el);
    const fn = vi.fn();
    el.addEventListener('activate', fn);

    dispose1();
    el.dispatchEvent(new MouseEvent('click', {bubbles: true}));
    expect(fn).toHaveBeenCalledOnce();

    dispose2();
    el.dispatchEvent(new MouseEvent('click', {bubbles: true}));
    expect(fn).toHaveBeenCalledOnce(); // no further activations
  });
});
