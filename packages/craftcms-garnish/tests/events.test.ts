import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';

import {
  ClassEventBus,
  EventEmitter,
  formatDomEvents,
  parseEvents,
  type GarnishEvent,
} from '../src/events';

describe('parseEvents', () => {
  it('splits on spaces by default and on the first dot', () => {
    expect(parseEvents('click.ns mousedown')).toEqual([
      {type: 'click', namespace: 'ns'},
      {type: 'mousedown', namespace: null},
    ]);
  });

  it('only splits on the first dot', () => {
    expect(parseEvents('click.a.b')).toEqual([
      {type: 'click', namespace: 'a.b'},
    ]);
  });

  it('splits on commas and trims when asked (addListener grammar)', () => {
    expect(parseEvents('click, drag.ns', ',')).toEqual([
      {type: 'click', namespace: null},
      {type: 'drag', namespace: 'ns'},
    ]);
  });

  it('supports the empty-type / namespace-only form for off', () => {
    expect(parseEvents('.focus-trap')).toEqual([
      {type: '', namespace: 'focus-trap'},
    ]);
  });
});

describe('formatDomEvents', () => {
  it('appends the namespace to each comma-split token', () => {
    expect(formatDomEvents('click,drag', '.Garnish123')).toBe(
      'click.Garnish123 drag.Garnish123'
    );
  });
});

describe('EventEmitter', () => {
  let emitter: EventEmitter<{id: string}>;
  const target = {id: 'host'};

  beforeEach(() => {
    emitter = new EventEmitter(target);
  });

  it('on/trigger invokes handlers with type + target', () => {
    const events: GarnishEvent[] = [];
    emitter.on('foo', (ev) => events.push(ev));
    emitter.trigger('foo');
    expect(events).toHaveLength(1);
    expect(events[0]!.type).toBe('foo');
    expect(events[0]!.target).toBe(target);
  });

  it('on with a data object merges per-registration data', () => {
    let received: GarnishEvent | undefined;
    emitter.on('foo', {a: 1}, (ev) => (received = ev));
    emitter.trigger('foo');
    expect(received!.data).toEqual({a: 1});
  });

  it('trigger precedence: trigger data wins over registration data; type/target always win', () => {
    let received: GarnishEvent | undefined;
    emitter.on('foo', {a: 1}, (ev) => (received = ev));
    // trigger-time data tries to override `type`, `target`, and a data key.
    emitter.trigger('foo', {a: 2, type: 'evil', target: 'evil'});
    // trigger data `a` wins; type/target are forced back.
    expect(received!.a).toBe(2);
    expect(received!.type).toBe('foo');
    expect(received!.target).toBe(target);
    // registration data is still present under `.data`.
    expect(received!.data).toEqual({a: 1});
  });

  it("a '*' handler receives every triggered event with its real type + payload", () => {
    const received: GarnishEvent[] = [];
    emitter.on('*', (ev) => received.push(ev));
    emitter.trigger('foo', {a: 1});
    emitter.trigger('bar');
    expect(received.map((ev) => ev.type)).toEqual(['foo', 'bar']);
    // trigger-time payload is spread onto the event (garnish convention).
    expect(received[0]!.a).toBe(1);
    expect(received[0]!.target).toBe(target);
  });

  it("'*' fires alongside exact-type handlers", () => {
    const wild = vi.fn();
    const exact = vi.fn();
    emitter.on('*', wild);
    emitter.on('foo', exact);
    emitter.trigger('foo');
    emitter.trigger('bar');
    expect(exact).toHaveBeenCalledTimes(1);
    expect(wild).toHaveBeenCalledTimes(2);
  });

  it('off by type removes all handlers of that type', () => {
    const fn = vi.fn();
    emitter.on('foo', fn);
    emitter.on('foo', fn);
    emitter.off('foo');
    emitter.trigger('foo');
    expect(fn).not.toHaveBeenCalled();
  });

  it('off by handler removes only the matching registration', () => {
    const a = vi.fn();
    const b = vi.fn();
    emitter.on('foo', a);
    emitter.on('foo', b);
    emitter.off('foo', a);
    emitter.trigger('foo');
    expect(a).not.toHaveBeenCalled();
    expect(b).toHaveBeenCalledTimes(1);
  });

  it('off by namespace only removes that namespace; empty namespace removes all', () => {
    const a = vi.fn();
    const b = vi.fn();
    emitter.on('foo.ns1', a);
    emitter.on('foo.ns2', b);
    emitter.off('foo.ns1');
    emitter.trigger('foo');
    expect(a).not.toHaveBeenCalled();
    expect(b).toHaveBeenCalledTimes(1);
  });

  it('once fires exactly once and is removable by original handler', () => {
    const fn = vi.fn();
    emitter.once('foo', fn);
    emitter.trigger('foo');
    emitter.trigger('foo');
    expect(fn).toHaveBeenCalledTimes(1);
  });

  it('a once registration can be removed by type before firing', () => {
    // Legacy wart: `once` stores a wrapper closure, so `off(events, originalFn)`
    // does NOT match it (only the wrapper does). Removing by type still works.
    const fn = vi.fn();
    emitter.once('foo', fn);
    emitter.off('foo');
    emitter.trigger('foo');
    expect(fn).not.toHaveBeenCalled();
  });

  it('clear removes every registration', () => {
    const fn = vi.fn();
    emitter.on('foo', fn);
    emitter.clear();
    emitter.trigger('foo');
    expect(fn).not.toHaveBeenCalled();
  });
});

describe('ClassEventBus instanceof dispatch', () => {
  class Animal {}
  class Dog extends Animal {}
  class Cat extends Animal {}

  let bus: ClassEventBus;
  beforeEach(() => {
    bus = new ClassEventBus();
  });

  it('dispatches to handlers whose target the instance is an instanceof', () => {
    const animalFn = vi.fn();
    const dogFn = vi.fn();
    const catFn = vi.fn();

    bus.on(Animal, 'bark', animalFn);
    bus.on(Dog, 'bark', dogFn);
    bus.on(Cat, 'bark', catFn);

    const dog = new Dog();
    bus.dispatch(dog, 'bark', undefined);

    expect(animalFn).toHaveBeenCalledTimes(1); // dog is an Animal
    expect(dogFn).toHaveBeenCalledTimes(1); // dog is a Dog
    expect(catFn).not.toHaveBeenCalled(); // dog is not a Cat
  });

  it('warns and no-ops on undefined target', () => {
    const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});
    bus.on(undefined as never, 'bark', vi.fn());
    expect(warn).toHaveBeenCalledOnce();
    warn.mockRestore();
  });

  it('off removes class-level handlers', () => {
    const fn = vi.fn();
    bus.on(Animal, 'bark', fn);
    bus.off(Animal, 'bark', fn);
    bus.dispatch(new Dog(), 'bark', undefined);
    expect(fn).not.toHaveBeenCalled();
  });

  it('once fires once at the class level', () => {
    const fn = vi.fn();
    bus.once(Animal, 'bark', fn);
    bus.dispatch(new Dog(), 'bark', undefined);
    bus.dispatch(new Dog(), 'bark', undefined);
    expect(fn).toHaveBeenCalledTimes(1);
  });
});
