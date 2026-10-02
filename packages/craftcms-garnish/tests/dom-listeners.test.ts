import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';

import {DomListenerRegistry} from '../src/dom-listeners';

function makeHost(disabled = false) {
  return {disabled};
}

describe('DomListenerRegistry', () => {
  let el: HTMLElement;

  beforeEach(() => {
    el = document.createElement('div');
    document.body.appendChild(el);
  });

  it('binds and fires a native event', () => {
    const reg = new DomListenerRegistry(makeHost());
    const fn = vi.fn();
    reg.add(el, 'click', fn);
    el.dispatchEvent(new MouseEvent('click'));
    expect(fn).toHaveBeenCalledOnce();
  });

  it('short-circuits while the host is disabled', () => {
    const host = makeHost(true);
    const reg = new DomListenerRegistry(host);
    const fn = vi.fn();
    reg.add(el, 'click', fn);
    el.dispatchEvent(new MouseEvent('click'));
    expect(fn).not.toHaveBeenCalled();
  });

  it('supports comma-split multiple events', () => {
    const reg = new DomListenerRegistry(makeHost());
    const fn = vi.fn();
    reg.add(el, 'click,mousedown', fn);
    el.dispatchEvent(new MouseEvent('click'));
    el.dispatchEvent(new MouseEvent('mousedown'));
    expect(fn).toHaveBeenCalledTimes(2);
  });

  it('removes by namespace only (namespaced remove)', () => {
    const reg = new DomListenerRegistry(makeHost());
    const a = vi.fn();
    const b = vi.fn();
    reg.add(el, 'click.ns1', a);
    reg.add(el, 'click.ns2', b);
    reg.remove(el, '.ns1');
    el.dispatchEvent(new MouseEvent('click'));
    expect(a).not.toHaveBeenCalled();
    expect(b).toHaveBeenCalledOnce();
  });

  it('removeAllOn removes every listener on an element', () => {
    const reg = new DomListenerRegistry(makeHost());
    const fn = vi.fn();
    reg.add(el, 'click', fn);
    reg.add(el, 'mousedown', fn);
    reg.removeAllOn(el);
    el.dispatchEvent(new MouseEvent('click'));
    el.dispatchEvent(new MouseEvent('mousedown'));
    expect(fn).not.toHaveBeenCalled();
  });

  it('delegation: only fires when the event originates within the selector', () => {
    el.innerHTML =
      '<button class="target">x</button><span class="other">y</span>';
    const reg = new DomListenerRegistry(makeHost());
    const fn = vi.fn();
    reg.add(el, 'click', fn, {delegate: '.target'});

    el.querySelector('.target')!.dispatchEvent(
      new MouseEvent('click', {bubbles: true})
    );
    expect(fn).toHaveBeenCalledOnce();

    el.querySelector('.other')!.dispatchEvent(
      new MouseEvent('click', {bubbles: true})
    );
    expect(fn).toHaveBeenCalledOnce(); // unchanged
  });

  it('removeAll tears down everything', () => {
    const reg = new DomListenerRegistry(makeHost());
    const fn = vi.fn();
    const el2 = document.createElement('div');
    document.body.appendChild(el2);
    reg.add(el, 'click', fn);
    reg.add(el2, 'click', fn);
    reg.removeAll();
    el.dispatchEvent(new MouseEvent('click'));
    el2.dispatchEvent(new MouseEvent('click'));
    expect(fn).not.toHaveBeenCalled();
  });
});
