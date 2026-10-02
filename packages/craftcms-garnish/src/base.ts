/**
 * Garnish base class — native `class extends`, jQuery-free.
 *
 * Replaces the legacy Dean Edwards `Base.extend()` + `init()` trampoline. The
 * `init()` indirection and `this.base()` super-dispatch are intentionally NOT
 * here — those are the compat layer's job. Subclasses use a real `constructor`
 * and `super()`.
 */

import {DomListenerRegistry, type DomListenerOptions} from './dom-listeners';
import {EventEmitter, type GarnishEventHandler} from './events';
import {garnishClassBus} from './globals';
import type {ElementInput, GarnishBaseSettings} from './types';

function makeNamespace(): string {
  const uuid =
    typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function'
      ? crypto.randomUUID()
      : String(Math.floor(Math.random() * 1000000000));
  return `.Garnish${uuid}`;
}

/**
 * The base class every Garnish component extends — a modern, jQuery-free,
 * native-`class` replacement for the legacy `Garnish.Base`.
 *
 * `Base` provides four facilities to subclasses:
 *
 * - **Settings** — a typed, shallow-merged `settings` object (see {@link setSettings}).
 * - **Object pub/sub** — per-instance events via {@link on} / {@link once} /
 *   {@link off} / {@link trigger}, plus class-level subscription through the
 *   `Garnish.on/off/once` proxies.
 * - **DOM listeners** — namespaced, auto-tracked bindings via
 *   {@link addListener} / {@link removeListener} / {@link removeAllListeners}
 *   that are torn down automatically on {@link destroy}.
 * - **Lifecycle** — {@link enable} / {@link disable} (which gate DOM listeners)
 *   and {@link destroy}.
 *
 * Unlike the legacy library, there is **no `init()` trampoline and no
 * `this.base()`** — subclasses use a real `constructor` + `super()` and call
 * `super.method()` for super-dispatch. Those legacy affordances live only in the
 * opt-in `@craftcms/garnish/compat` layer.
 *
 * @typeParam S - The subclass's settings shape; defaults to {@link GarnishBaseSettings}.
 *
 * @example Defining a component
 * ```ts
 * import {Base, type GarnishBaseSettings} from '@craftcms/garnish';
 *
 * interface CounterSettings extends GarnishBaseSettings {
 *   step: number;
 * }
 *
 * class Counter extends Base<CounterSettings> {
 *   static defaults: CounterSettings = {step: 1};
 *   value = 0;
 *
 *   constructor(button: HTMLElement, settings?: Partial<CounterSettings>) {
 *     super();
 *     this.setSettings(settings, Counter.defaults);
 *     this.addListener(button, 'click', () => {
 *       this.value += this.settings!.step;
 *       this.trigger('change', {value: this.value});
 *     });
 *   }
 * }
 * ```
 */
export abstract class Base<
  S extends GarnishBaseSettings = GarnishBaseSettings,
> {
  /**
   * The instance's resolved settings (defaults merged with the passed
   * overrides). `null` until {@link setSettings} has run — most subclasses call
   * it in their constructor, so guard with `this.settings!` once you know it has.
   */
  settings: S | null = null;

  protected _disabled = false;

  /** Per-instance namespace; kept for compat/debugging parity. */
  protected readonly _namespace: string = makeNamespace();

  /** Object pub/sub registry. */
  protected readonly _emitter = new EventEmitter<this>(this);

  /** Tracked DOM listener bindings for namespaced removal + bulk teardown. */
  protected readonly _domListeners: DomListenerRegistry =
    new DomListenerRegistry(this);

  constructor() {
    // No init() trampoline. Subclasses do their own setup after super().
  }

  // --- Settings -------------------------------------------------------------

  /**
   * Resolve {@link settings} by shallow-merging, in increasing precedence:
   * any existing `settings`, then `defaults`, then the passed `settings`.
   *
   * Matches the legacy `$.extend({}, base, defaults, settings)` precedence — it
   * is a **shallow** merge, not a deep one. Nested objects are replaced wholesale,
   * not recursively combined.
   *
   * @param settings - Per-instance overrides (highest precedence).
   * @param defaults - The component's default settings.
   *
   * @example
   * ```ts
   * this.setSettings(passedSettings, MyComponent.defaults);
   * // this.settings is now {...defaults, ...passedSettings}
   * ```
   */
  setSettings(settings?: Partial<S>, defaults?: Partial<S>): void {
    const base = this.settings ?? {};
    this.settings = Object.assign({}, base, defaults, settings) as S;
  }

  // --- Object pub/sub -------------------------------------------------------

  /**
   * Subscribe to one or more instance events.
   *
   * The event string follows the legacy grammar: space-separated for multiple
   * events, with an optional `.namespace` suffix per token
   * (e.g. `'show hide.myPlugin'`). Namespaces let you remove a related group of
   * handlers later with {@link off}.
   *
   * @param events - Space-separated event name(s), each optionally `.namespaced`.
   * @param data - Optional per-registration data, merged into the event object
   *   passed to the handler.
   * @param handler - The callback, invoked with a {@link GarnishEvent}.
   *
   * @example
   * ```ts
   * modal.on('show', (ev) => console.log('shown', ev.target));
   * modal.on('hide.myPlugin', (ev) => cleanup());
   * modal.off('.myPlugin'); // remove every handler in that namespace
   * ```
   */
  on(events: string, handler: GarnishEventHandler): void;
  on(
    events: string,
    data: Record<string, unknown>,
    handler: GarnishEventHandler
  ): void;
  on(
    events: string,
    dataOrHandler: Record<string, unknown> | GarnishEventHandler,
    handler?: GarnishEventHandler
  ): void {
    // Delegate; the emitter handles the overload.
    (this._emitter.on as (...a: unknown[]) => void)(
      events,
      dataOrHandler,
      handler
    );
  }

  /**
   * Like {@link on}, but the handler is automatically removed after it fires
   * once (per matched event token).
   *
   * @param events - Space-separated event name(s), each optionally `.namespaced`.
   * @param data - Optional per-registration data merged into the event object.
   * @param handler - The callback, invoked at most once.
   */
  once(events: string, handler: GarnishEventHandler): void;
  once(
    events: string,
    data: Record<string, unknown>,
    handler: GarnishEventHandler
  ): void;
  once(
    events: string,
    dataOrHandler: Record<string, unknown> | GarnishEventHandler,
    handler?: GarnishEventHandler
  ): void {
    (this._emitter.once as (...a: unknown[]) => void)(
      events,
      dataOrHandler,
      handler
    );
  }

  /**
   * Unsubscribe from instance events.
   *
   * Matching mirrors jQuery's namespaced `.off`: a registration is removed when
   * its type matches AND (the requested namespace is empty OR the namespaces
   * match) AND (`handler` is omitted OR is the same function reference).
   *
   * - `off('show')` — remove all `show` handlers.
   * - `off('show', fn)` — remove only `fn`.
   * - `off('.myPlugin')` — remove every handler registered under that namespace,
   *   across all event types.
   *
   * @param events - Space-separated event name(s), each optionally `.namespaced`.
   * @param handler - When given, only the matching handler reference is removed.
   */
  off(events: string, handler?: GarnishEventHandler): void {
    this._emitter.off(events, handler);
  }

  /**
   * Emit an event. Dispatches first to this instance's handlers (registered via
   * {@link on} / {@link once}), then to class-level handlers registered through
   * `Garnish.on(SomeClass, …)` whose target this instance is an `instanceof`
   * (two-pass dispatch, matching the legacy behavior).
   *
   * @param type - The event type (no namespace; namespaces are a subscribe-side
   *   concept only).
   * @param data - Optional payload, spread onto the {@link GarnishEvent} passed
   *   to handlers (trigger-time keys win over per-registration `data`).
   *
   * @example
   * ```ts
   * this.trigger('change', {value: this.value});
   * ```
   */
  trigger(type: string, data?: Record<string, unknown>): void {
    this._emitter.trigger(type, data);
    garnishClassBus.dispatch(this, type, data);
  }

  // --- DOM listeners --------------------------------------------------------

  /**
   * Bind one or more native DOM event listeners, tracked by this instance so
   * they can be removed by name/namespace or torn down in bulk on
   * {@link destroy}. This is the jQuery-free replacement for the legacy
   * `addListener`.
   *
   * Signature-compatible with legacy: `(elem, events, handler)` or
   * `(elem, events, data, handler)`. The `handler` may be a function (bound to
   * `this`) or a **method-name string** resolved against `this` at call time.
   *
   * The `events` string uses the comma grammar (e.g. `'click, keydown'`), each
   * token optionally `.namespaced`. Handlers do not fire while the instance is
   * {@link disable | disabled}. Custom Garnish events (`activate`, `textchange`,
   * `resize`) are supported transparently.
   *
   * Supported `data` keys: `delegate` (a selector — fire only when the event
   * originates within a matching descendant) and event-specific options such as
   * `delay` for `textchange`.
   *
   * @param elem - Target(s): an element, `window`/`document`, an array/NodeList,
   *   or a CSS selector string (see {@link ElementInput}).
   * @param events - Comma-separated event name(s), each optionally `.namespaced`.
   * @param dataOrHandler - Either the handler, or a `data` object when a 4th arg
   *   is supplied.
   * @param handler - The handler (function or method-name string) when `data`
   *   is passed.
   *
   * @example
   * ```ts
   * this.addListener(this.$button, 'click', (ev) => this.handleClick(ev));
   * this.addListener(this.$shade, 'click', 'hide'); // method-name string
   * this.addListener(this.$list, 'click', {delegate: 'li'}, this.onItem);
   * ```
   */
  addListener(
    elem: ElementInput,
    events: string,
    dataOrHandler: Record<string, unknown> | GarnishEventHandler | string,
    handler?: GarnishEventHandler | string
  ): void {
    let data: Record<string, unknown> = {};
    let func: GarnishEventHandler | string | undefined;

    // Param mapping: if handler is undefined and `dataOrHandler` isn't a plain
    // object, treat `dataOrHandler` as the handler.
    if (handler === undefined && !isPlainObject(dataOrHandler)) {
      func = dataOrHandler as GarnishEventHandler | string;
    } else {
      data = (dataOrHandler as Record<string, unknown>) ?? {};
      func = handler;
    }

    let resolved: GarnishEventHandler;
    if (typeof func === 'function') {
      resolved = func.bind(this);
    } else if (typeof func === 'string') {
      // Method-name string (e.g. UiLayerManager uses 'triggerShortcut').
      resolved = (this[func as keyof this] as GarnishEventHandler).bind(this);
    } else {
      return;
    }

    const options: DomListenerOptions = {};
    if ('delegate' in data && typeof data.delegate === 'string') {
      options.delegate = data.delegate;
    }
    if ('delay' in data || Object.keys(data).length) {
      options.data = data;
    }

    this._domListeners.add(elem, events, resolved, options);
  }

  /**
   * Remove specific DOM listener(s) this instance bound on the element(s).
   * Honors namespaces: `removeListener(el, '.myNs')` removes every namespaced
   * binding regardless of type.
   *
   * @param elem - The same target(s) the listener was bound on.
   * @param events - Comma-separated event name(s), each optionally `.namespaced`.
   */
  removeListener(elem: ElementInput, events: string): void {
    this._domListeners.remove(elem, events);
  }

  /**
   * Remove every DOM listener this instance bound on the given element(s).
   *
   * @param elem - The target(s) to clear listeners from.
   */
  removeAllListeners(elem: ElementInput): void {
    this._domListeners.removeAllOn(elem);
  }

  // --- Enable/disable/destroy ----------------------------------------------

  /**
   * Disable the instance. While disabled, DOM listeners bound via
   * {@link addListener} are short-circuited (they don't fire). Object events
   * ({@link trigger}) are unaffected.
   */
  disable(): void {
    this._disabled = true;
  }

  /** Re-enable the instance, restoring DOM-listener dispatch. */
  enable(): void {
    this._disabled = false;
  }

  /** Whether the instance is currently {@link disable | disabled}. */
  get disabled(): boolean {
    return this._disabled;
  }

  /**
   * Tear the instance down: emit a `destroy` event, then remove every tracked
   * DOM listener and clear all object-event registrations. Subclasses that
   * override this should call `super.destroy()`.
   */
  destroy(): void {
    this.trigger('destroy');
    this._domListeners.removeAll();
    this._emitter.clear();
  }
}

function isPlainObject(val: unknown): val is Record<string, unknown> {
  return (
    typeof val === 'object' &&
    val !== null &&
    (Object.getPrototypeOf(val) === Object.prototype ||
      Object.getPrototypeOf(val) === null)
  );
}
