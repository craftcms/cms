import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';

import {Base} from '../src/base';
import {garnishClassBus} from '../src/globals';

class TestBase extends Base {}

describe('Base settings', () => {
  it('shallow-merges with precedence base < defaults < settings', () => {
    const obj = new TestBase();
    obj.setSettings({a: 1, b: 2}, {a: 0, c: 3});
    expect(obj.settings).toEqual({a: 1, b: 2, c: 3});
  });

  it('subsequent setSettings layers over existing (existing is lowest)', () => {
    const obj = new TestBase();
    obj.setSettings({a: 1});
    obj.setSettings({b: 2});
    expect(obj.settings).toEqual({a: 1, b: 2});
  });

  it('does NOT deep-merge nested objects (replaces wholesale)', () => {
    const obj = new TestBase();
    obj.setSettings({nested: {x: 1, y: 2}});
    obj.setSettings({nested: {z: 3}});
    expect(obj.settings!.nested).toEqual({z: 3});
  });

  it('skips null/undefined args (Object.assign semantics)', () => {
    const obj = new TestBase();
    obj.setSettings(undefined, {a: 1});
    expect(obj.settings).toEqual({a: 1});
  });
});

describe('Base pub/sub', () => {
  it('on/trigger works at the instance level', () => {
    const obj = new TestBase();
    const fn = vi.fn();
    obj.on('foo', fn);
    obj.trigger('foo');
    expect(fn).toHaveBeenCalledOnce();
  });

  it('trigger also dispatches class-level handlers (instanceof)', () => {
    garnishClassBus.clear();
    const fn = vi.fn();
    garnishClassBus.on(Base, 'foo', fn);
    const obj = new TestBase();
    obj.trigger('foo');
    expect(fn).toHaveBeenCalledOnce();
    expect((fn.mock.calls[0]![0] as {target: unknown}).target).toBe(obj);
    garnishClassBus.clear();
  });
});

describe('Base DOM listeners + disabled gate', () => {
  let el: HTMLButtonElement;
  beforeEach(() => {
    el = document.createElement('button');
    document.body.appendChild(el);
  });

  it('addListener binds and invokes with the host as `this`', () => {
    const obj = new TestBase();
    let receivedThis: unknown;
    obj.addListener(el, 'click', function (this: unknown) {
      receivedThis = this;
    });
    el.dispatchEvent(new MouseEvent('click'));
    expect(receivedThis).toBe(obj);
  });

  it('does not invoke handlers while disabled', () => {
    const obj = new TestBase();
    const fn = vi.fn();
    obj.addListener(el, 'click', fn);
    obj.disable();
    el.dispatchEvent(new MouseEvent('click'));
    expect(fn).not.toHaveBeenCalled();
    obj.enable();
    el.dispatchEvent(new MouseEvent('click'));
    expect(fn).toHaveBeenCalledOnce();
  });

  it('supports a method-name string handler', () => {
    class WithMethod extends Base {
      called = false;
      handleClick(): void {
        this.called = true;
      }
    }
    const obj = new WithMethod();
    obj.addListener(el, 'click', 'handleClick');
    el.dispatchEvent(new MouseEvent('click'));
    expect(obj.called).toBe(true);
  });

  it('removeListener removes a specific event', () => {
    const obj = new TestBase();
    const fn = vi.fn();
    obj.addListener(el, 'click', fn);
    obj.removeListener(el, 'click');
    el.dispatchEvent(new MouseEvent('click'));
    expect(fn).not.toHaveBeenCalled();
  });

  it('destroy triggers destroy + removes all listeners', () => {
    const obj = new TestBase();
    const clickFn = vi.fn();
    const destroyFn = vi.fn();
    obj.addListener(el, 'click', clickFn);
    obj.on('destroy', destroyFn);
    obj.destroy();
    expect(destroyFn).toHaveBeenCalledOnce();
    el.dispatchEvent(new MouseEvent('click'));
    expect(clickFn).not.toHaveBeenCalled();
  });

  it('bails silently when no elements resolve', () => {
    const obj = new TestBase();
    expect(() => obj.addListener(null, 'click', () => {})).not.toThrow();
  });

  // A native `InputEvent` carries its own read-only `data` property (the inserted
  // text, or `null`). A programmatic `new InputEvent('input', {inputType: 'insertText'})`
  // — exactly what `BaseInputGenerator.updateTarget` dispatches — has `data === null`,
  // which used to slip past the `=== undefined` guard, leaving the bound registration
  // data unset so a handler reading `ev.data.type` crashed.
  it('surfaces bound `data` when the native event has its own null `data` (InputEvent)', () => {
    const obj = new TestBase();
    let received: any;
    obj.addListener(el, 'input', {type: 'singleline'}, (ev: any) => {
      received = ev;
    });
    el.dispatchEvent(new InputEvent('input', {inputType: 'insertText'}));
    expect(received.data).toEqual({type: 'singleline'});
  });

  it('bound `data` wins over a non-null native InputEvent `data`', () => {
    const obj = new TestBase();
    let received: any;
    obj.addListener(el, 'input', {type: 'singleline'}, (ev: any) => {
      received = ev;
    });
    el.dispatchEvent(
      new InputEvent('input', {data: 'a', inputType: 'insertText'})
    );
    expect(received.data).toEqual({type: 'singleline'});
  });

  it('defaults `data` to {} when none is bound and the event has none', () => {
    const obj = new TestBase();
    let received: any;
    obj.addListener(el, 'click', (ev: any) => {
      received = ev;
    });
    el.dispatchEvent(new MouseEvent('click'));
    expect(received.data).toEqual({});
  });
});
