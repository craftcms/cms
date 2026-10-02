/**
 * Garnish event system (object pub/sub + class-level pub/sub).
 *
 * Reproduces the two in-memory observer registries from the legacy core:
 *  - `EventEmitter` backs `Base.on/off/once/trigger` (per-instance).
 *  - `ClassEventBus` backs `Garnish.on/off/once` (class-level, instanceof dispatch).
 *
 * DOM listeners are a separate concern (see dom-listeners.ts).
 */

import type {Constructor} from './types';

/** Result of parsing a single event token. */
export interface ParsedEvent {
  /** Event type, e.g. `'click'`. May be `''` for the "remove by namespace" form. */
  type: string;
  /** Namespace, e.g. `'foo'` for `'click.foo'`. `null` when none. */
  namespace: string | null;
}

/**
 * The event object passed to every Garnish event handler. For object events it
 * carries the emitting instance as {@link target} plus any data; for DOM events
 * routed through {@link Base.addListener} it is the native `Event` augmented with
 * these fields, so native props (`stopPropagation`, `currentTarget`, …) are also
 * available at runtime.
 */
export interface GarnishEvent<Target = unknown> {
  type: string;
  /** The emitting object (legacy: the Base instance). */
  target: Target;
  /** Per-registration data merged with trigger-time data. */
  data: Record<string, unknown>;
  /** Present when re-emitting a DOM event (legacy `activate` etc.). */
  originalEvent?: Event;
  /** Trigger-time payload is spread on (legacy `$.extend` behavior). */
  [extra: string]: unknown;
}

/**
 * A Garnish event handler: a callback receiving a {@link GarnishEvent}. This is
 * the handler type accepted by {@link Base.on} / {@link Base.once} and
 * {@link Base.addListener}.
 */
export type GarnishEventHandler<E extends GarnishEvent = GarnishEvent> = (
  event: E
) => void;

/** Internal registration record. */
interface Registration {
  type: string;
  namespace: string | null;
  data: Record<string, unknown>;
  handler: GarnishEventHandler;
}

/**
 * Parse a Garnish event string into `{type, namespace}` records.
 *
 * Legacy `_normalizeEvents` splits on spaces, then each token on its **first**
 * `.` into `[type, namespace]`. This preserves that grammar exactly.
 *
 * @param events  An event string or pre-split array.
 * @param splitOn Separator for multiple events: `' '` for pub/sub (default),
 *                `','` for `addListener` (the legacy split inconsistency).
 */
export function parseEvents(
  events: string | string[],
  splitOn: ' ' | ',' = ' '
): ParsedEvent[] {
  let tokens: string[];

  if (typeof events === 'string') {
    tokens = events.split(splitOn);
  } else {
    tokens = events;
  }

  const parsed: ParsedEvent[] = [];

  for (let token of tokens) {
    if (typeof token !== 'string') {
      continue;
    }

    // For comma splitting, legacy trims each token.
    if (splitOn === ',') {
      token = token.trim();
    }

    // Split on the first '.' only. 'click.a.b' => type='click', namespace='a.b'.
    const dotIndex = token.indexOf('.');
    if (dotIndex === -1) {
      parsed.push({type: token, namespace: null});
    } else {
      parsed.push({
        type: token.slice(0, dotIndex),
        namespace: token.slice(dotIndex + 1) || null,
      });
    }
  }

  return parsed;
}

/**
 * Format an event string for DOM binding by appending an instance namespace.
 *
 * Mirrors legacy `_formatEvents`: splits on commas (the `addListener` grammar),
 * trims, appends the namespace, and rejoins on spaces.
 *
 * `formatDomEvents('click,drag', '.Garnish123')` => `'click.Garnish123 drag.Garnish123'`
 */
export function formatDomEvents(
  events: string | string[],
  namespace: string
): string {
  let tokens: string[];

  if (typeof events === 'string') {
    tokens = events.split(',');
  } else {
    tokens = events.slice();
  }

  return tokens.map((token) => `${token.trim()}${namespace}`).join(' ');
}

/**
 * Build the event object passed to a handler.
 *
 * Reproduces the legacy precedence exactly:
 *   `$.extend({data: registration.data}, triggerData, {type, target})`
 *
 * i.e. trigger-time data keys win over registration `data`, and `type`/`target`
 * always win last. This is observable — some call sites pass `target` overrides
 * in `data` and legacy lets the `ev` object override them.
 */
function buildEvent(
  registrationData: Record<string, unknown>,
  triggerData: Record<string, unknown> | undefined,
  type: string,
  target: unknown
): GarnishEvent {
  return Object.assign({data: registrationData}, triggerData, {
    type,
    target,
  }) as GarnishEvent;
}

/**
 * Per-instance object pub/sub registry backing {@link Base.on} / `once` / `off`
 * / `trigger`. One emitter exists per `Base` instance and owns that instance as
 * the event {@link GarnishEvent.target}. Exported for advanced/standalone use;
 * most code interacts with it through `Base`.
 */
export class EventEmitter<Target = unknown> {
  private readonly registrations: Registration[] = [];

  constructor(private readonly target: Target) {}

  /**
   * Subscribe to instance event(s). Supports multiple space-separated events and
   * `.namespace`s; the special type `'*'` subscribes to **every** event (the
   * handler still receives the real `type`).
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
    let data: Record<string, unknown>;
    let fn: GarnishEventHandler;

    if (typeof dataOrHandler === 'function') {
      fn = dataOrHandler;
      data = {};
    } else {
      data = dataOrHandler;
      fn = handler as GarnishEventHandler;
    }

    for (const ev of parseEvents(events)) {
      this.registrations.push({
        type: ev.type,
        namespace: ev.namespace,
        data,
        handler: fn,
      });
    }
  }

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
    let data: Record<string, unknown>;
    let fn: GarnishEventHandler;

    if (typeof dataOrHandler === 'function') {
      fn = dataOrHandler;
      data = {};
    } else {
      data = dataOrHandler;
      fn = handler as GarnishEventHandler;
    }

    // Wrapper-closure `once` (legacy behavior): `off(events, onceler)` removes it.
    const onceler: GarnishEventHandler = (event) => {
      this.off(events, onceler);
      fn(event);
    };

    this.on(events, data, onceler);
  }

  /**
   * Remove matching registrations. A registration matches when its `type` ===
   * the parsed type, AND (the parsed namespace is empty OR namespaces match),
   * AND (handler omitted OR handler ===). Iterates backwards + splices.
   */
  off(events: string, handler?: GarnishEventHandler): void {
    for (const ev of parseEvents(events)) {
      for (let i = this.registrations.length - 1; i >= 0; i--) {
        const reg = this.registrations[i]!;

        if (
          reg.type === ev.type &&
          (!ev.namespace || reg.namespace === ev.namespace) &&
          (handler === undefined || reg.handler === handler)
        ) {
          this.registrations.splice(i, 1);
        }
      }
    }
  }

  /** Dispatch to instance handlers matching `type` (plus any `'*'` listeners). */
  trigger(type: string, data?: Record<string, unknown>): void {
    // Snapshot the matching handlers first; a `once` handler mutates the array.
    // A `'*'` registration matches every type (used to forward all events); its
    // handler still receives the real `type` via `buildEvent`.
    const matching = this.registrations.filter(
      (reg) => reg.type === type || reg.type === '*'
    );

    for (const reg of matching) {
      const event = buildEvent(reg.data, data, type, this.target);
      reg.handler(event);
    }
  }

  /** Remove every registration (used by destroy). */
  clear(): void {
    this.registrations.length = 0;
  }
}

/**
 * Class-level pub/sub bus backing `Garnish.on/off/once`. Handlers are registered
 * against a class; any instance's {@link Base.trigger} dispatches to class-level
 * handlers whose target the instance is an `instanceof`. A single shared
 * instance powers the `Garnish` namespace; exported for advanced use.
 */
export class ClassEventBus {
  private readonly registrations: Array<Registration & {target: Constructor}> =
    [];

  on(target: Constructor, events: string, handler: GarnishEventHandler): void;
  on(
    target: Constructor,
    events: string,
    data: Record<string, unknown>,
    handler: GarnishEventHandler
  ): void;
  on(
    target: Constructor,
    events: string,
    dataOrHandler: Record<string, unknown> | GarnishEventHandler,
    handler?: GarnishEventHandler
  ): void {
    if (typeof target === 'undefined') {
      console.warn('Garnish.on() called for an invalid target class.');
      return;
    }

    let data: Record<string, unknown>;
    let fn: GarnishEventHandler;

    if (typeof dataOrHandler === 'function') {
      fn = dataOrHandler;
      data = {};
    } else {
      data = dataOrHandler;
      fn = handler as GarnishEventHandler;
    }

    for (const ev of parseEvents(events)) {
      this.registrations.push({
        target,
        type: ev.type,
        namespace: ev.namespace,
        data,
        handler: fn,
      });
    }
  }

  once(target: Constructor, events: string, handler: GarnishEventHandler): void;
  once(
    target: Constructor,
    events: string,
    data: Record<string, unknown>,
    handler: GarnishEventHandler
  ): void;
  once(
    target: Constructor,
    events: string,
    dataOrHandler: Record<string, unknown> | GarnishEventHandler,
    handler?: GarnishEventHandler
  ): void {
    if (typeof target === 'undefined') {
      console.warn('Garnish.once() called for an invalid target class.');
      return;
    }

    let data: Record<string, unknown>;
    let fn: GarnishEventHandler;

    if (typeof dataOrHandler === 'function') {
      fn = dataOrHandler;
      data = {};
    } else {
      data = dataOrHandler;
      fn = handler as GarnishEventHandler;
    }

    const onceler: GarnishEventHandler = (event) => {
      this.off(target, events, onceler);
      fn(event);
    };

    this.on(target, events, data, onceler);
  }

  off(
    target: Constructor,
    events: string,
    handler?: GarnishEventHandler
  ): void {
    for (const ev of parseEvents(events)) {
      for (let i = this.registrations.length - 1; i >= 0; i--) {
        const reg = this.registrations[i]!;

        if (
          reg.target === target &&
          reg.type === ev.type &&
          (!ev.namespace || reg.namespace === ev.namespace) &&
          (handler === undefined || reg.handler === handler)
        ) {
          this.registrations.splice(i, 1);
        }
      }
    }
  }

  /**
   * Dispatch class-level handlers whose target the instance is an `instanceof`
   * (legacy `Garnish._eventHandlers` walk inside `Base.trigger`).
   */
  dispatch(
    instance: object,
    type: string,
    data: Record<string, unknown> | undefined
  ): void {
    const matching = this.registrations.filter(
      (reg) =>
        reg && reg.target && instance instanceof reg.target && reg.type === type
    );

    for (const reg of matching) {
      const event = buildEvent(reg.data, data, type, instance);
      reg.handler(event);
    }
  }

  /** Remove every registration (mostly for tests). */
  clear(): void {
    this.registrations.length = 0;
  }
}
