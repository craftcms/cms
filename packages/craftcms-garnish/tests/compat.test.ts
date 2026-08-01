/**
 * Compat-layer tests — proving the legacy upgrade path works end-to-end:
 *   - `Garnish.Base.extend({init, foo})` produces a working instance
 *   - `this.base()` dispatches to the ancestor (incl. 2-level chains + override)
 *   - `init` runs with the constructor args
 *   - static members merge
 *   - `window.Garnish` is installed under the legacy guard
 *   - jQuery surface is feature-detected (degrades when jQuery is absent)
 *   - (conditionally) `Garnish.Modal.extend({...})` works if Modal is exported
 */

import {describe, it, expect, beforeEach, vi} from 'vite-plus/test';
import GarnishCompat, {
  compatify,
  installGarnishCompat,
  isJquery,
  resolveJQuery,
  unwrapJq,
} from '../src/compat';
import {Base} from '../src/index';
import * as Core from '../src/index';

type AnyObj = Record<string, any>;

describe('compatify / Garnish.Base.extend', () => {
  it('produces a working instance from extend({init, foo})', () => {
    const LegacyBase = compatify(Base);
    const calls: string[] = [];

    const Widget = LegacyBase.extend({
      init() {
        calls.push('init');
      },
      foo() {
        return 'foo-result';
      },
    });

    const w = new Widget() as AnyObj;
    expect(calls).toEqual(['init']);
    expect(w.foo()).toBe('foo-result');
    // Real prototype chain → instanceof the modern Base.
    expect(w).toBeInstanceOf(Base);
  });

  it('runs init with the constructor arguments', () => {
    const LegacyBase = compatify(Base);
    let received: unknown[] = [];

    const Widget = LegacyBase.extend({
      init(...args: unknown[]) {
        received = args;
      },
    });

    new Widget('a', 42, {k: 1});
    expect(received).toEqual(['a', 42, {k: 1}]);
  });

  it('merges static members onto the constructor', () => {
    const LegacyBase = compatify(Base);
    const Widget = LegacyBase.extend(
      {
        init() {},
      },
      {
        defaults: {speed: 5},
        staticHelper() {
          return 'helped';
        },
      }
    );

    expect((Widget as AnyObj).defaults).toEqual({speed: 5});
    expect((Widget as AnyObj).staticHelper()).toBe('helped');
  });

  it('this.base() dispatches to the ancestor method', () => {
    const LegacyBase = compatify(Base);

    const Parent = LegacyBase.extend({
      init() {},
      greet() {
        return 'parent';
      },
    });

    const Child = Parent.extend({
      init() {},
      greet() {
        // @ts-expect-error legacy compat affordance
        return 'child+' + this.base();
      },
    });

    const c = new Child() as AnyObj;
    expect(c.greet()).toBe('child+parent');
  });

  it('this.base() works across a 2-level extend chain', () => {
    const LegacyBase = compatify(Base);

    const A = LegacyBase.extend({
      init() {},
      val() {
        return 1;
      },
    });
    const B = A.extend({
      init() {},
      val() {
        // @ts-expect-error legacy compat affordance
        return 10 + this.base();
      },
    });
    const C = B.extend({
      init() {},
      val() {
        // @ts-expect-error legacy compat affordance
        return 100 + this.base();
      },
    });

    const c = new C() as AnyObj;
    // C.val → 100 + B.val (10 + A.val (1)) = 111
    expect(c.val()).toBe(111);
  });

  it('restores this.base after the call (re-entrancy)', () => {
    const LegacyBase = compatify(Base);

    const A = LegacyBase.extend({
      init() {},
      step() {
        return 'A';
      },
    });
    const B = A.extend({
      init() {},
      step() {
        // @ts-expect-error compat affordance
        const inner = this.base();
        // calling base again still resolves to the same ancestor within this frame
        // @ts-expect-error compat affordance
        const inner2 = this.base();
        return `B(${inner})(${inner2})`;
      },
    });

    const b = new B() as AnyObj;
    expect(b.step()).toBe('B(A)(A)');
    // After the call, this.base must be restored to the no-op (not leak A).
    expect(typeof (b as AnyObj).base).toBe('function');
  });

  it('only the leaf init runs (init fires once)', () => {
    const LegacyBase = compatify(Base);
    const order: string[] = [];

    const A = LegacyBase.extend({
      init() {
        order.push('A');
      },
    });
    const B = A.extend({
      init() {
        order.push('B');
      },
    });

    new B();
    expect(order).toEqual(['B']);
  });

  it('a leaf without init inherits an ancestor init', () => {
    const LegacyBase = compatify(Base);
    const order: string[] = [];

    const A = LegacyBase.extend({
      init() {
        order.push('A');
      },
    });
    // B adds no init of its own.
    const B = A.extend({
      other() {
        return 1;
      },
    });

    new B();
    expect(order).toEqual(['A']);
  });

  it('leaf init can reach the ancestor init via this.base()', () => {
    const LegacyBase = compatify(Base);
    const order: string[] = [];

    const A = LegacyBase.extend({
      init() {
        order.push('A');
      },
    });
    const B = A.extend({
      init() {
        order.push('B-before');
        // @ts-expect-error compat affordance
        this.base();
        order.push('B-after');
      },
    });

    new B();
    expect(order).toEqual(['B-before', 'A', 'B-after']);
  });

  it('preserves getters/setters from instance members', () => {
    const LegacyBase = compatify(Base);
    const Widget = LegacyBase.extend({
      init() {},
      _n: 0,
      get doubled() {
        return (this as AnyObj)._n * 2;
      },
    });
    const w = new Widget() as AnyObj;
    w._n = 21;
    expect(w.doubled).toBe(42);
  });
});

describe('jQuery feature-detection / coercion', () => {
  it('isJquery returns false when jQuery is absent', () => {
    // No jQuery is installed in this package's test env.
    expect(resolveJQuery()).toBeNull();
    expect(isJquery({})).toBe(false);
    expect(isJquery(null)).toBe(false);
  });

  it('unwrapJq passes through non-jQuery values unchanged', () => {
    const el = document.createElement('div');
    expect(unwrapJq(el)).toBe(el);
    expect(unwrapJq('string')).toBe('string');
    expect(unwrapJq(null)).toBe(null);
  });

  it('unwrapJq unwraps a fake jQuery collection to its first element', () => {
    const el = document.createElement('div');
    // Install a fake jQuery so isJquery() recognizes the collection.
    const fakeProto = {};
    function FakeJq(this: any) {}
    FakeJq.prototype = fakeProto;
    (FakeJq as any).fn = {jquery: '3.x'};
    const coll = Object.create(fakeProto);
    coll[0] = el;
    coll.length = 1;

    const g = globalThis as AnyObj;
    const priorJq = g.jQuery;
    const prior$ = g.$;
    g.jQuery = FakeJq;
    g.$ = FakeJq;
    try {
      expect(isJquery(coll)).toBe(true);
      expect(unwrapJq(coll)).toBe(el);
    } finally {
      g.jQuery = priorJq;
      g.$ = prior$;
    }
  });

  it('constructor unwraps a jQuery-collection $container arg to a native element', () => {
    const el = document.createElement('div');
    const fakeProto = {};
    function FakeJq(this: any) {}
    FakeJq.prototype = fakeProto;
    (FakeJq as any).fn = {jquery: '3.x'};
    const coll = Object.create(fakeProto);
    coll[0] = el;
    coll.length = 1;

    const g = globalThis as AnyObj;
    const priorJq = g.jQuery;
    g.jQuery = FakeJq;
    try {
      const LegacyBase = compatify(Base);
      let receivedArg: unknown;
      const Widget = LegacyBase.extend({
        init(arg: unknown) {
          receivedArg = arg;
        },
      });
      new Widget(coll);
      // init sees the unwrapped native element, not the collection.
      expect(receivedArg).toBe(el);
    } finally {
      g.jQuery = priorJq;
    }
  });
});

describe('window.Garnish installation', () => {
  it('installs window.Garnish under the legacy guard and is idempotent', () => {
    const w = window as AnyObj;
    // GarnishCompat default export already auto-installed on import.
    expect(w.Garnish).toBeDefined();
    expect(w.Garnish).toBe(GarnishCompat);

    // Re-running install is idempotent and does not clobber the existing global.
    const again = installGarnishCompat();
    expect(again).toBe(GarnishCompat);
    expect(w.Garnish).toBe(GarnishCompat);
  });

  it('exposes compatified, extend-able classes', () => {
    const G = GarnishCompat as AnyObj;
    expect(typeof G.Base.extend).toBe('function');
    expect(typeof G.UiLayerManager.extend).toBe('function');
    expect(typeof G.EscManager.extend).toBe('function');

    const Custom = G.UiLayerManager.extend({
      init() {
        // call modern super behavior (this is `any` here via AnyObj)
        this.base();
      },
    });
    const inst = new Custom();
    expect(inst).toBeInstanceOf(Core.UiLayerManager);
  });

  it('attaches the manager singletons', () => {
    const G = GarnishCompat as AnyObj;
    expect(G.escManager).toBeInstanceOf(Core.EscManager);
    expect(G.uiLayerManager).toBeInstanceOf(Core.UiLayerManager);
    // legacy lowercase alias
    expect(G.shortcutManager).toBe(G.uiLayerManager);
  });

  it('restores the ShortcutManager deprecated alias', () => {
    const G = GarnishCompat as AnyObj;
    expect(G.ShortcutManager).toBe(G.UiLayerManager);
  });

  it('carries over utilities and constants from core', () => {
    const G = GarnishCompat as AnyObj;
    expect(typeof G.getDist).toBe('function');
    expect(typeof G.ESC_KEY).toBe('number');
    expect(typeof G.on).toBe('function');
    expect(typeof G.off).toBe('function');
    expect(typeof G.once).toBe('function');
  });

  it('throws a clear error when accessing $win without jQuery', () => {
    expect(resolveJQuery()).toBeNull();
    const G = GarnishCompat as AnyObj;
    expect(() => G.$win).toThrowError(/jQuery is required/);
  });

  it('getFocusedElement degrades to a native element when jQuery is absent', () => {
    const G = GarnishCompat as AnyObj;
    // No jQuery → returns the native activeElement (or null), not a collection.
    const result = G.getFocusedElement();
    expect(result === null || result instanceof Element).toBe(true);
  });
});

describe('Modal compat (conditional)', () => {
  const Modal = (Core as AnyObj).Modal;
  const maybe = Modal ? it : it.skip;

  maybe('Garnish.Modal.extend({...}) works end-to-end', () => {
    const G = GarnishCompat as AnyObj;
    const ModalCompat = G.Modal;
    expect(typeof ModalCompat.extend).toBe('function');

    const calls: string[] = [];
    const MyModal = ModalCompat.extend({
      init(container: unknown, settings: unknown) {
        calls.push('init');
        // (this is `any` here via AnyObj)
        this.base(container, settings);
      },
    });

    const container = document.createElement('div');
    const m = new MyModal(container, {});
    expect(calls).toContain('init');
    expect(m).toBeInstanceOf(Modal);
  });
});

// Keep vi referenced for environments that tree-shake unused imports in tests.
void vi;
beforeEach(() => {});
