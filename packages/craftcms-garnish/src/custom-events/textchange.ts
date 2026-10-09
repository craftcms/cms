/**
 * Garnish `textchange` custom event.
 *
 * Native replacement for the legacy `$.event.special.textchange`. Stores the
 * last value and dispatches a `textchange` CustomEvent only when the value
 * actually changes, with optional debounce.
 */

export interface TextchangeOptions {
  delay?: number | null;
}

const installed = new WeakMap<
  HTMLElement,
  {count: number; dispose: () => void}
>();

/**
 * Attach `textchange` semantics to an input: a `textchange` `CustomEvent` that
 * fires only when the value actually changes (across keypress/keyup/change/blur),
 * with optional debounce. Usually reached via
 * `addListener(input, 'textchange', fn)`. Idempotent per element (ref-counted).
 *
 * @param el - The input/textarea to watch.
 * @param options - `{delay}` to debounce dispatch by N milliseconds.
 * @returns A disposer that removes the listeners when the last consumer releases.
 */
export function installTextchange(
  el: HTMLElement & {value: string},
  options: TextchangeOptions = {}
): () => void {
  const existing = installed.get(el);
  if (existing) {
    existing.count++;
    return makeDisposer(el);
  }

  let lastValue = el.value;
  let delayTimeout: ReturnType<typeof setTimeout> | undefined;
  const delay = options.delay ?? null;

  const fire = (): void => {
    el.dispatchEvent(
      new CustomEvent('textchange', {bubbles: true, cancelable: true})
    );
  };

  const onInputEvent = (): void => {
    const val = el.value;
    if (val !== lastValue) {
      lastValue = val;

      if (delay) {
        if (delayTimeout) {
          clearTimeout(delayTimeout);
        }
        delayTimeout = setTimeout(fire, delay);
      } else {
        fire();
      }
    }
  };

  const events = ['keypress', 'keyup', 'change', 'blur'];
  for (const type of events) {
    el.addEventListener(type, onInputEvent as EventListener);
  }

  const dispose = (): void => {
    if (delayTimeout) {
      clearTimeout(delayTimeout);
    }
    for (const type of events) {
      el.removeEventListener(type, onInputEvent as EventListener);
    }
  };

  installed.set(el, {count: 1, dispose});
  return makeDisposer(el);
}

function makeDisposer(el: HTMLElement): () => void {
  let called = false;
  return () => {
    if (called) {
      return;
    }
    called = true;
    const record = installed.get(el);
    if (!record) {
      return;
    }
    record.count--;
    if (record.count <= 0) {
      record.dispose();
      installed.delete(el);
    }
  };
}
