/**
 * Namespaced DOM-listener registry — jQuery-free replacement for
 * `Base.addListener/removeListener/removeAllListeners`.
 *
 * Native `addEventListener` has no namespace concept, so the registry stores a
 * tuple per binding `{element, type, namespace, wrappedHandler, capture}` and
 * replays jQuery's namespaced `.off` semantics over that array.
 *
 * Custom Garnish event types (`activate`/`textchange`/`resize`) are routed
 * through the matching installer so `addListener(el, 'activate', fn)` keeps
 * working.
 */

import { parseEvents } from './events';
import type { GarnishEvent, GarnishEventHandler } from './events';
import type { ElementInput } from './types';
import { coerceElements } from './utils/dom';
import { globals } from './globals';
import { installActivate, installResize, installTextchange } from './custom-events';

export type { ElementInput } from './types';

export interface DomListenerOptions {
  /** Delegated target selector; handler fires only when the event target matches/closests this. */
  delegate?: string;
  /** Extra data; used by custom events (e.g. textchange `delay`). Merged into the event object. */
  data?: Record<string, unknown>;
  capture?: boolean;
  passive?: boolean;
}

/** The minimal host contract the registry needs (a `Base`). */
export interface DomListenerHost {
  readonly disabled: boolean;
}

interface Binding {
  element: EventTarget;
  type: string;
  namespace: string | null;
  wrappedHandler: EventListener;
  capture: boolean;
  /** Disposer for synthetic custom events; native listeners use removeEventListener. */
  dispose?: () => void;
}

const CUSTOM_EVENT_TYPES = new Set(['activate', 'textchange', 'resize']);

/**
 * The namespaced DOM-listener registry that backs {@link Base.addListener} /
 * `removeListener` / `removeAllListeners`. Each `Base` owns one. It records every
 * binding so jQuery-style namespaced removal and bulk teardown work without
 * jQuery, and routes the custom Garnish events (`activate`/`textchange`/`resize`)
 * through their installers. Exported for advanced/standalone use; most code goes
 * through `Base`.
 */
export class DomListenerRegistry {
  private readonly bindings: Binding[] = [];

  constructor(private readonly host: DomListenerHost) {}

  /**
   * Bind one or more (comma/space-grammar) events on the resolved elements.
   * The handler runs with the host's `this` already applied by the caller, and
   * is short-circuited while `host.disabled` is true.
   */
  add(elements: ElementInput, events: string, handler: GarnishEventHandler, options: DomListenerOptions = {}): void {
    const targets = coerceElements(elements);
    if (targets.length === 0) {
      return;
    }

    // `addListener` grammar: split on commas.
    const parsed = parseEvents(events, ',');
    const capture = options.capture ?? false;

    for (const target of targets) {
      for (const ev of parsed) {
        if (CUSTOM_EVENT_TYPES.has(ev.type)) {
          this.bindCustom(target, ev.type, ev.namespace, handler, options);
        } else {
          this.bindNative(target, ev.type, ev.namespace, handler, options, capture);
        }
      }
    }
  }

  private wrap(
    target: EventTarget,
    type: string,
    handler: GarnishEventHandler,
    options: DomListenerOptions,
  ): EventListener {
    return (nativeEvent: Event): void => {
      // Disabled gate (legacy `_disabled`).
      if (this.host.disabled) {
        return;
      }

      let currentTarget: EventTarget | null = target;

      // Delegation: only fire when the event originated within the selector.
      if (options.delegate) {
        const origin = nativeEvent.target as Element | null;
        const matched = origin?.closest?.(options.delegate) ?? null;
        if (!matched || !(target as Element).contains?.(matched)) {
          return;
        }
        currentTarget = matched;
      }

      // Build a Garnish-style event object that also exposes native props.
      const garnishEvent = this.toGarnishEvent(nativeEvent, type, currentTarget, options);
      handler(garnishEvent);
    };
  }

  private toGarnishEvent(
    nativeEvent: Event,
    type: string,
    currentTarget: EventTarget | null,
    options: DomListenerOptions,
  ): GarnishEvent {
    // Pass through the real DOM event; only define fields that are missing so we
    // never write to read-only native getters (`type`/`target` already exist).
    const ev = nativeEvent as unknown as GarnishEvent;

    // jQuery semantics: a handler bound with `data` always sees it as `ev.data`.
    // It must win even when the native event has its OWN `data` property —
    // `InputEvent`/`CompositionEvent` expose `data` (the inserted text, which is
    // `null` in browsers / `''` in happy-dom for a programmatic
    // `new InputEvent('input')`), which would otherwise shadow the bound
    // registration data and crash handlers that read `ev.data.<key>`. When nothing
    // was bound, default to `{}` only if the event has no `data` of its own.
    if (options.data !== undefined) {
      Object.defineProperty(ev, 'data', {
        value: options.data,
        configurable: true,
        enumerable: true,
        writable: true,
      });
    } else if (!('data' in ev) || ev.data === undefined) {
      Object.defineProperty(ev, 'data', {
        value: {},
        configurable: true,
        enumerable: true,
        writable: true,
      });
    }

    // Expose the delegated match (parity with jQuery's delegate currentTarget).
    if (options.delegate && currentTarget) {
      Object.defineProperty(ev, 'garnishTarget', {
        value: currentTarget,
        configurable: true,
        enumerable: true,
        writable: true,
      });
    }

    return ev;
  }

  private bindNative(
    target: EventTarget,
    type: string,
    namespace: string | null,
    handler: GarnishEventHandler,
    options: DomListenerOptions,
    capture: boolean,
  ): void {
    const wrapped = this.wrap(target, type, handler, options);
    target.addEventListener(type, wrapped, {
      capture,
      passive: options.passive,
    });
    this.bindings.push({
      element: target,
      type,
      namespace,
      wrappedHandler: wrapped,
      capture,
    });
  }

  private bindCustom(
    target: EventTarget,
    type: string,
    namespace: string | null,
    handler: GarnishEventHandler,
    options: DomListenerOptions,
  ): void {
    const el = target as HTMLElement;

    // Install the synthetic event source (idempotent per element/type).
    let installerDispose: () => void = () => {};
    if (type === 'activate') {
      installerDispose = installActivate(el);
    } else if (type === 'textchange') {
      installerDispose = installTextchange(el as HTMLElement & { value: string }, {
        delay: (options.data?.delay as number | null | undefined) ?? null,
      });
    } else if (type === 'resize') {
      installerDispose = installResize(el);
    }

    // The synthetic event dispatches as a native CustomEvent on the element.
    const wrapped = this.wrap(target, type, handler, options);
    target.addEventListener(type, wrapped);

    const binding: Binding = {
      element: target,
      type,
      namespace,
      wrappedHandler: wrapped,
      capture: false,
    };
    binding.dispose = () => {
      target.removeEventListener(type, wrapped);
      // Only tear down the installer if this was the last listener of its type.
      const stillBound = this.bindings.some((b) => b !== binding && b.element === target && b.type === type);
      if (!stillBound) {
        installerDispose();
      }
    };
    this.bindings.push(binding);
  }

  /** Remove specific event(s) previously bound by this host on the element(s). */
  remove(elements: ElementInput, events: string): void {
    const targets = coerceElements(elements);
    const parsed = parseEvents(events, ',');

    for (const target of targets) {
      for (const ev of parsed) {
        this.removeWhere(
          (b) =>
            b.element === target &&
            // An empty type with a namespace removes all of that namespace.
            (ev.type === '' || b.type === ev.type) &&
            (!ev.namespace || b.namespace === ev.namespace),
        );
      }
    }
  }

  /** Remove all listeners this host bound on the element(s). */
  removeAllOn(elements: ElementInput): void {
    const targets = coerceElements(elements);
    for (const target of targets) {
      this.removeWhere((b) => b.element === target);
    }
  }

  /** Remove everything this host bound anywhere (used by destroy). */
  removeAll(): void {
    this.removeWhere(() => true);
  }

  private removeWhere(predicate: (b: Binding) => boolean): void {
    for (let i = this.bindings.length - 1; i >= 0; i--) {
      const binding = this.bindings[i]!;
      if (!predicate(binding)) {
        continue;
      }

      if (binding.dispose) {
        binding.dispose();
      } else {
        binding.element.removeEventListener(binding.type, binding.wrappedHandler, {
          capture: binding.capture,
        });
      }
      this.bindings.splice(i, 1);
    }
  }
}

// Re-export for convenience; keeps `globals` import tree-shake friendly.
void globals;
