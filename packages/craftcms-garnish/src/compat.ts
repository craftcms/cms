/**
 * @craftcms/garnish/compat — opt-in legacy compatibility layer.
 *
 * Restores the entire legacy Garnish authoring contract on top of the modern,
 * jQuery-free core:
 *
 *  - `Garnish.Base.extend(instanceMembers, staticMembers)` → real subclasses
 *  - `init()` as the constructor trampoline (subclasses write `init`, not `constructor`)
 *  - `this.base(...)` super-dispatch with per-call save/restore re-entrancy,
 *    ported faithfully from Dean Edwards `lib/Base.js` (the `/\bbase\b/` source-sniff)
 *  - jQuery-collection coercion for `$container`-style constructor args
 *  - `isJquery`, jQuery-wrapped `$win`/`$doc`/`$bod`/`$scrollContainer`
 *  - `getFocusedElement()` returning a jQuery collection (legacy shape)
 *  - the deprecated `Menu` / `ShortcutManager` aliases
 *  - the `$.fn.activate/textchange/resize` chaining sugar (when jQuery is present)
 *  - a legacy-shaped `window.Garnish` global, guarded like the legacy code
 *
 * ## Opt-in story
 *
 * This entry is **side-effecting**: importing it installs `window.Garnish`
 * (under the same `if (typeof window.Garnish === 'undefined')` guard the legacy
 * library used) and gives you `.extend()`-able, jQuery-shaped classes.
 *
 *   // legacy plugin, zero code changes beyond this one line:
 *   import '@craftcms/garnish/compat';
 *   Craft.MyModal = Garnish.Modal.extend({ init() { … } });
 *
 * To **drop** the compat layer, delete that import line and move to the modern
 * surface (`import {Modal} from '@craftcms/garnish'; class extends Modal {…}`).
 * Tree-shaking then removes all of this code and the `window.Garnish` global.
 *
 * For programmatic / non-auto-install use, call `installGarnishCompat()` (it is
 * idempotent and returns the assembled namespace), or import the named
 * `compatify` / `GarnishCompat` exports directly.
 *
 * jQuery is a **peer dependency of this entry only** — the modern `.` entry stays
 * jQuery-free. jQuery is detected at runtime from the global scope; the compat
 * layer degrades gracefully (or throws a clear error) when it is absent.
 */

import * as Core from './index';
import {Garnish as CoreGarnish, initGarnish} from './index';
import {getFocusedElement as coreGetFocusedElement} from './utils/focus';
import {win, doc, bod} from './globals';

/* ------------------------------------------------------------------------- *
 * jQuery runtime detection (peer dependency of ./compat only)
 * ------------------------------------------------------------------------- */

/**
 * Minimal structural type for a jQuery function/collection. We can't depend on
 * `@types/jquery` here (jQuery is only an optional peer of this entry), so this
 * is intentionally loose.
 */
export interface JQueryLike {
  (selectorOrEl?: unknown): JQueryCollection;
  fn: Record<string, unknown> & {jquery?: string};
  prototype: object;
}

export interface JQueryCollection {
  length: number;
  [index: number]: Element;
  get(index?: number): Element | Element[];
  on(...args: unknown[]): JQueryCollection;
  trigger(...args: unknown[]): JQueryCollection;
  toArray?(): Element[];
}

/**
 * Resolve jQuery from the global scope (`window.jQuery` or `window.$`), or
 * `null` if it isn't present. jQuery is an optional peer dependency of the
 * compat entry only and is detected at runtime.
 *
 * @returns The global jQuery function, or `null`.
 */
export function resolveJQuery(): JQueryLike | null {
  const g = globalThis as Record<string, unknown>;
  const candidate = (g.jQuery ?? g.$) as JQueryLike | undefined;
  if (typeof candidate === 'function' && (candidate as JQueryLike).fn) {
    return candidate as JQueryLike;
  }
  return null;
}

/** Throw a clear, actionable error when jQuery is required but absent. */
function requireJQuery(feature: string): JQueryLike {
  const jq = resolveJQuery();
  if (!jq) {
    throw new Error(
      `@craftcms/garnish/compat: jQuery is required for ${feature} but was not ` +
        `found on the global scope. Load jQuery before importing the compat ` +
        `layer, or migrate to the modern jQuery-free API.`
    );
  }
  return jq;
}

/**
 * Whether a value is a jQuery collection — the modern home of the legacy
 * `Garnish.isJquery`. Returns `false` when jQuery isn't loaded.
 *
 * @param val - The value to test.
 * @returns `true` if `val` is a jQuery collection.
 */
export function isJquery(val: unknown): boolean {
  const jq = resolveJQuery();
  if (!jq) {
    return false;
  }
  // jQuery collections are instances of jQuery.fn.init; the cheapest reliable
  // check is the prototype chain, mirroring legacy `val instanceof $`.
  return val instanceof (jq as unknown as {prototype: object} & Function);
}

/**
 * Wrap a native element / list in a jQuery collection (`$(value)`).
 *
 * @param value - Anything jQuery's `$()` accepts.
 * @returns The jQuery collection.
 * @throws If jQuery is absent — the `$`-prefixed legacy surface is meaningless
 *   without it.
 */
export function toJq(value: unknown): JQueryCollection {
  const jq = requireJQuery('jQuery-wrapped values');
  return jq(value);
}

/**
 * Coerce a possibly-jQuery constructor argument down to the native input the
 * modern classes expect. Modern constructors accept
 * `Element | EventTarget | string | null`; legacy callers may pass a jQuery
 * collection (`$container`) — this unwraps it to its first element (or `null`
 * when empty). Non-jQuery values pass through untouched.
 *
 * @param value - The (possibly jQuery) value.
 * @returns The native element, or the value unchanged.
 *
 * @TODO replace instances of this with `toHtmlElement`
 */
export function unwrapJq(value: unknown): unknown {
  if (value == null) {
    return value;
  }
  if (isJquery(value)) {
    const coll = value as JQueryCollection;
    return coll.length ? coll[0] : null;
  }
  return value;
}

/**
 * Unwrap a jQuery collection (truthy `.jquery`) to its first native element,
 * pass a native `Element` through, and return `null` for anything else.
 *
 * This has some overlap with `unwrapJq` but the types are better here.
 */
export function toHtmlElement(value: unknown): HTMLElement | null {
  if (isJquery(value)) {
    return value.length && value[0] instanceof HTMLElement ? value[0] : null;
  }

  return value instanceof HTMLElement ? value : null;
}

/* ------------------------------------------------------------------------- *
 * compatify(ModernClass) — the legacy `.extend()` / `this.base()` shim
 * ------------------------------------------------------------------------- */

type AnyCtor = abstract new (...args: any[]) => object;
type ConcreteCtor = new (...args: any[]) => object;

export interface LegacyMembers {
  /** Legacy constructor trampoline; receives the `new` arguments. */
  init?: (...args: any[]) => void;
  [key: string]: unknown;
}

export interface LegacyCtor extends ConcreteCtor {
  /** Dean-Edwards-style subclassing: returns a new legacy-shaped subclass. */
  extend(instanceMembers?: LegacyMembers, staticMembers?: object): LegacyCtor;
  /** The class this one extends (legacy `klass.ancestor`). */
  ancestor?: unknown;
}

/**
 * The marker we stash on every compatified prototype so a subclass can find the
 * ancestor implementation of an overridden method when synthesizing `this.base`.
 *
 * We walk the *prototype chain* (real inheritance) rather than cloning prototypes
 * the way Dean Edwards' `new this()` did — the lookup is
 * `Object.getPrototypeOf(subclassProto)[name]`.
 */

/**
 * Detect whether a function's source references `base` as an identifier — the
 * exact `/\bbase\b/` source-sniff from `lib/Base.js`. Only methods that actually
 * call `this.base()` get wrapped, matching legacy behavior (and avoiding the
 * overhead/observable `this.base` mutation for methods that don't).
 */
const BASE_RE = /\bbase\b/;

function referencesBase(fn: unknown): fn is (...args: any[]) => unknown {
  return (
    typeof fn === 'function' &&
    BASE_RE.test(Function.prototype.toString.call(fn))
  );
}

/**
 * Wrap an override so that, on entry, `this.base` is set to the ancestor method
 * (bound to `this`), the override runs, and the prior `this.base` is restored on
 * exit. Ported verbatim from `lib/Base.js`'s save/restore dance, with a
 * try/finally so nested / re-entrant super-calls restore correctly even when an
 * override throws.
 */
function wrapWithBase(
  method: (...args: any[]) => unknown,
  ancestor: ((...args: any[]) => unknown) | undefined
): (...args: any[]) => unknown {
  const noopBase = function noBase(): void {
    // Calling this.base() with no ancestor is a legacy no-op.
  };

  return function baseWrapper(this: Record<string, unknown>, ...args: any[]) {
    const previous = this.base;
    this.base = ancestor ? ancestor.bind(this) : noopBase;
    try {
      return method.apply(this, args);
    } finally {
      this.base = previous;
    }
  };
}

/**
 * Copy a single legacy instance member onto a subclass prototype. Functions that
 * reference `base` are wrapped for super-dispatch against the ancestor found on
 * the prototype chain.
 */
function defineInstanceMember(
  proto: object,
  parentProto: object,
  key: string,
  value: unknown
): void {
  if (referencesBase(value)) {
    const ancestor = (parentProto as Record<string, unknown>)[key] as
      | ((...args: any[]) => unknown)
      | undefined;
    Object.defineProperty(proto, key, {
      value: wrapWithBase(value, ancestor),
      writable: true,
      configurable: true,
      enumerable: false,
    });
  } else {
    Object.defineProperty(proto, key, {
      value,
      writable: true,
      configurable: true,
      enumerable: false,
    });
  }
}

/**
 * Turn a modern ES class into a legacy-shaped constructor that supports the full
 * Dean-Edwards authoring contract: `.extend(instanceMembers, staticMembers)`,
 * `init` as the constructor trampoline, and `this.base(...)` super-dispatch.
 *
 * The returned constructor is directly `new`-able (it forwards to the modern
 * class) and exposes a static `extend`. Each `.extend()` produces a **real
 * subclass** (native prototype chain) so `instanceof` and `super` keep working,
 * with the legacy affordances layered on top: `init` runs once on the most
 * derived class, and methods whose source references `base` get a per-call
 * `this.base` bound to the ancestor implementation.
 *
 * This is what makes an unmodified legacy plugin run against the modern core.
 * For TypeScript-authored code, prefer plain `class extends ModernClass` with
 * `super.method()` instead — `compatify` exists for the upgrade path.
 *
 * @typeParam T - The modern (abstract or concrete) class to wrap.
 * @param Modern - The modern ES class.
 * @returns A legacy-shaped constructor with `.extend()`.
 *
 * @example
 * ```ts
 * import {Modal} from '@craftcms/garnish';
 * import {compatify} from '@craftcms/garnish/compat';
 *
 * const LegacyModal = compatify(Modal);
 *
 * const MyModal = LegacyModal.extend({
 *   init(container) {
 *     this.base(container, {closeOtherModals: true}); // calls Modal's constructor
 *   },
 *   onShow() {
 *     this.base();   // calls Modal.prototype.onShow
 *     // ...custom behavior
 *   },
 * });
 *
 * new MyModal(document.querySelector('#my-modal'));
 * ```
 */
export function compatify<T extends AnyCtor>(Modern: T): LegacyCtor {
  // The root compat constructor simply forwards to the modern class. Modern
  // classes have plain constructors (no init trampoline), so a bare `new` is a
  // valid legacy instantiation with no `init` member.
  const Root = makeSubclass(Modern as unknown as ConcreteCtor, undefined);

  // Faithful to lib/Base.js (`proto.base = function () {}`): every compatified
  // class has a default no-op `base` on its prototype, so `this.base` is ALWAYS
  // callable even outside a wrapped override, and the save/restore dance in
  // `wrapWithBase` always restores to a function rather than `undefined`.
  if (!Object.prototype.hasOwnProperty.call(Root.prototype, 'base')) {
    Object.defineProperty(Root.prototype, 'base', {
      value: function noBase(): void {
        // call this method from any other method to invoke that method's ancestor
      },
      writable: true,
      configurable: true,
      enumerable: false,
    });
  }

  return Root;
}

/**
 * Build one level of the legacy-shaped class hierarchy.
 *
 * @param Parent  The (modern or already-compatified) constructor to extend.
 * @param members The legacy instance members for THIS level (or undefined for
 *                the root wrapper, which adds none).
 * @param statics Static members to copy onto the constructor.
 */
function makeSubclass(
  Parent: ConcreteCtor,
  members: LegacyMembers | undefined,
  statics?: object
): LegacyCtor {
  const parentProto = Parent.prototype as object;
  const hasInit = typeof members?.init === 'function';

  // The legacy-shaped constructor. It runs the modern constructor (super), then,
  // if this level (or an ancestor level) supplied an `init`, invokes it with the
  // original arguments — matching legacy `this.init.apply(this, arguments)`.
  class Subclass extends (Parent as ConcreteCtor) {
    constructor(...args: any[]) {
      // Coerce jQuery-collection / selector args down to native inputs the
      // modern constructor (and the legacy `init`) understand
      // ($container → element). Unwrap once so super() and init() agree.
      const nativeArgs = args.map(unwrapJq) as any[];
      super(...nativeArgs);

      // Only run init from the *most derived* level that defined one. Because
      // each subclass constructor runs this, we guard so `init` fires exactly
      // once: only when `this.constructor === Subclass` (the instantiated class).
      // Inherited `init`s are found via the prototype and run by the leaf ctor.
      if (this.constructor === Subclass) {
        const initFn = (this as Record<string, unknown>).init;
        if (typeof initFn === 'function') {
          (initFn as (...a: any[]) => void).apply(this, nativeArgs);
        }
      }
    }
  }

  // Copy instance members onto the subclass prototype, wrapping `base`-callers.
  if (members) {
    for (const key of Object.keys(members)) {
      if (key === 'constructor') {
        continue;
      }
      const desc = Object.getOwnPropertyDescriptor(members, key)!;
      if ('value' in desc) {
        defineInstanceMember(Subclass.prototype, parentProto, key, desc.value);
      } else {
        // Preserve getters/setters (legacy Dean Edwards copied descriptors).
        Object.defineProperty(Subclass.prototype, key, desc);
      }
    }
  }
  // Mark whether this level brought its own init (debug/parity aid).
  void hasInit;

  const Ctor = Subclass as unknown as LegacyCtor;

  // Static members.
  if (statics) {
    Object.assign(Ctor, statics);
  }
  Ctor.ancestor = Parent;

  // The legacy `.extend()` entry point: build the next level down.
  Ctor.extend = function extend(
    instanceMembers?: LegacyMembers,
    staticMembers?: object
  ): LegacyCtor {
    return makeSubclass(
      Ctor as unknown as ConcreteCtor,
      instanceMembers,
      staticMembers
    );
  };

  return Ctor;
}

/* ------------------------------------------------------------------------- *
 * Legacy-shaped Garnish namespace assembly
 * ------------------------------------------------------------------------- */

/** The set of class keys on the core namespace we run through `compatify`. */
const CLASS_KEYS = ['Base', 'EscManager', 'UiLayerManager'] as const;

export interface GarnishCompatNamespace {
  [key: string]: unknown;
}

let installed: GarnishCompatNamespace | undefined;

/**
 * Assemble the legacy-shaped `Garnish` namespace **without** assigning it to
 * `window`: {@link compatify} each Garnish class so `.extend()` works, run
 * `initGarnish()` for the manager singletons, re-add the jQuery-only members
 * (`isJquery`, `$win`/`$doc`/`$bod`/`$scrollContainer`, the `$.fn.activate/…`
 * chaining sugar), and restore the deprecated aliases (`Menu`,
 * `ShortcutManager`).
 *
 * Use {@link installGarnishCompat} (or the side-effecting `import` of this
 * module) if you also want `window.Garnish` set.
 *
 * @returns The assembled namespace. Idempotent — repeated calls return the same object.
 */
export function buildGarnishCompat(): GarnishCompatNamespace {
  if (installed) {
    return installed;
  }

  // Start from the live core namespace so utilities, constants, flags, and the
  // class-level event bus proxies (`on`/`off`/`once`) are all carried over.
  const G: GarnishCompatNamespace = Object.assign({}, CoreGarnish);

  // Wrap every (currently-ported) class so `.extend()` / `init` / `this.base()`
  // work. New classes (Modal, HUD, …) are picked up automatically as the core
  // namespace gains them.
  for (const key of Object.keys(CoreGarnish)) {
    const value = (CoreGarnish as Record<string, unknown>)[key];
    if (isCompatifiableClass(key, value)) {
      G[key] = compatify(value as AnyCtor);
    }
  }

  // --- jQuery-only surface (re-added from core, which omitted it) -----------

  G.isJquery = isJquery;

  // jQuery-wrapped DOM refs (lazy getters so jQuery only needs to exist at
  // access time, not import time).
  Object.defineProperties(G, {
    $win: {
      configurable: true,
      enumerable: true,
      get: () => toJq(win),
    },
    $doc: {
      configurable: true,
      enumerable: true,
      get: () => toJq(doc),
    },
    $bod: {
      configurable: true,
      enumerable: true,
      get: () => toJq(bod),
    },
    $scrollContainer: {
      configurable: true,
      enumerable: true,
      get: () => toJq(CoreGarnish.scrollContainer),
    },
  });

  // getFocusedElement returned a jQuery `:focus` collection in legacy.
  G.getFocusedElement = (): JQueryCollection | Element | null => {
    if (resolveJQuery()) {
      const el = coreGetFocusedElement();
      return toJq(el ?? []);
    }
    return coreGetFocusedElement();
  };

  // --- Manager singletons ---------------------------------------------------

  initGarnish();
  G.escManager = CoreGarnish.escManager;
  G.uiLayerManager = CoreGarnish.uiLayerManager;

  // --- Deprecated aliases ---------------------------------------------------

  // Menu → CustomSelect (when CustomSelect is ported), else fall back to nothing.
  const customSelect = (G as Record<string, unknown>).CustomSelect;
  if (customSelect) {
    G.Menu = customSelect;
  }
  // ShortcutManager → UiLayerManager (compatified form).
  G.ShortcutManager = G.UiLayerManager;
  // Legacy lowercase alias used by index.js.
  G.shortcutManager = CoreGarnish.uiLayerManager;

  // --- $.fn chaining sugar (only when jQuery is present) --------------------
  installJqueryFnSugar();

  installed = G;
  return installed;
}

/**
 * Re-add the legacy `$.fn.activate/textchange/resize` chaining sugar IF jQuery is
 * present. Routes through jQuery's own `.on(name, …)` / `.trigger(name)` exactly
 * like the legacy `$.each(['activate','textchange','resize'], …)` block. The
 * underlying custom-event semantics are installed by the core
 * `installActivate/Textchange/Resize` when the listeners actually bind via
 * `Garnish.Base#addListener`; this sugar only restores the fluent jQuery call
 * shape. Degrades to a no-op when jQuery is absent.
 */
function installJqueryFnSugar(): void {
  const jq = resolveJQuery();
  if (!jq || !jq.fn) {
    return;
  }
  for (const name of ['activate', 'textchange', 'resize'] as const) {
    if (typeof jq.fn[name] === 'function') {
      continue; // already installed (e.g. by the legacy bundle)
    }
    jq.fn[name] = function (
      this: JQueryCollection,
      data?: unknown,
      fn?: unknown
    ): JQueryCollection {
      return arguments.length > 0
        ? this.on(name, null, data, fn)
        : this.trigger(name);
    };
  }
}

function isCompatifiableClass(key: string, value: unknown): boolean {
  if (typeof value !== 'function') {
    return false;
  }
  // Known core classes, plus any future class exposed on the namespace whose
  // prototype descends from the (already-known) Base.
  if ((CLASS_KEYS as readonly string[]).includes(key)) {
    return true;
  }
  // Heuristic for not-yet-enumerated classes (Modal, HUD, …): a function with a
  // prototype that is a Base (or subclass) instance shape. We avoid wrapping
  // plain utility functions by requiring the value to be a class extending Core.Base.
  try {
    return value.prototype instanceof Core.Base;
  } catch {
    return false;
  }
}

/* ------------------------------------------------------------------------- *
 * window.Garnish installation
 * ------------------------------------------------------------------------- */

/**
 * Build the legacy-shaped `Garnish` namespace and install it onto `window`,
 * guarded exactly like the legacy library (`if (typeof window.Garnish ===
 * 'undefined')`) so it never clobbers an existing (legacy-bundle) global during
 * the coexistence period.
 *
 * Importing `@craftcms/garnish/compat` calls this automatically (via the eager
 * {@link GarnishCompat} export); call it yourself only for explicit/programmatic
 * installation.
 *
 * @returns The assembled namespace, regardless of whether it won the `window.Garnish`
 *   slot. Idempotent.
 *
 * @example
 * ```ts
 * import {installGarnishCompat} from '@craftcms/garnish/compat';
 * const Garnish = installGarnishCompat();
 * const MyModal = Garnish.Modal.extend({ init() { … } });
 * ```
 */
export function installGarnishCompat(): GarnishCompatNamespace {
  const G = buildGarnishCompat();

  const w = (typeof window !== 'undefined' ? window : undefined) as
    | (Window & {Garnish?: unknown})
    | undefined;

  if (w && typeof w.Garnish === 'undefined') {
    w.Garnish = G;
  }

  return G;
}

/**
 * The assembled legacy-shaped `Garnish` namespace, built (and installed onto
 * `window.Garnish`) eagerly when this module is imported. This is the default
 * export too, so `import Garnish from '@craftcms/garnish/compat'` gives you the
 * namespace directly.
 */
export const GarnishCompat = installGarnishCompat();

export default GarnishCompat;
