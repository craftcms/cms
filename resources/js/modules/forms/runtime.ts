import {Validator} from '@lion/ui/form-core.js';
import {
  computed,
  type ComputedRef,
  type InjectionKey,
  type Ref,
  type Slots,
} from 'vue';
import type {
  CanonicalFormValue,
  FormChange,
  FormValue,
  FormValues,
} from './types';

export const FormFailure: InjectionKey<(message: string) => void> =
  Symbol('FormFailure');

export const FormControlOverrides: InjectionKey<Readonly<Slots>> = Symbol(
  'FormControlOverrides'
);

/** Modified delta groups as dotted paths, provided to every field beneath. */
export const FormModifiedGroups: InjectionKey<Readonly<Ref<Set<string>>>> =
  Symbol('FormModifiedGroups');

/** Control paths whose changes have an active Form refresh. */
export const FormRefreshingFields: InjectionKey<Readonly<Ref<Set<string>>>> =
  Symbol('FormRefreshingFields');

class ServerError extends Validator {
  static override validatorName = 'ServerError';

  override execute(): boolean {
    return true;
  }
}

/**
 * A control's value, with an empty stand-in for the beat before it arrives.
 *
 * A control renders as soon as the form describing it does, and inside a nested
 * form that can be one emit ahead of the values filling it — a block the server
 * has just minted, a repeater whose identity the server has just rewritten. A
 * control that reaches into its value throws in that gap, and a throw during
 * render is what "Failed to render Form Control" is: the renderer swaps the
 * control for the error, and the field is gone until the page is reloaded.
 *
 * Controls whose value is a scalar don't need this — they coerce `undefined`
 * on their own. It's for the ones that reach into a shape.
 *
 * The stand-in is frozen and shared across every render, so a control can't
 * write through it by accident.
 *
 * @param  value  Getter for the control's own `value` prop.
 * @param  empty  What to read while the real value is missing.
 */
export function controlValue<T extends object>(
  value: () => T | undefined,
  empty: T
): ComputedRef<T> {
  const fallback = Object.freeze(empty);

  return computed(() => value() ?? fallback);
}

export function serverErrorValidators(invalid: boolean): Validator[] {
  return invalid
    ? [
        new ServerError(undefined, {
          getMessage: () => '',
          visibilityDuration: Infinity,
        }),
      ]
    : [];
}

export function ignoreModelValueInitialization(
  callback: (event: Event) => void
): (event: Event) => void {
  return (event) => {
    if (!(event instanceof CustomEvent) || !event.detail?.initialize) {
      callback(event);
    }
  };
}

export function formChangeFromEvent(
  change: FormChange | Event
): FormChange | null {
  if (!(change instanceof Event)) {
    return change;
  }

  const detail = change instanceof CustomEvent ? change.detail : null;

  // Only a Control's own CustomEvent carries a FormChange. Plenty of other
  // CustomEvents bubble through a form — htmx's request lifecycle puts
  // `{elt, xhr, …}` in `detail` — and forwarding one as a change hands
  // listeners an object with no `path`.
  return isFormChange(detail) ? detail : null;
}

function isFormChange(value: unknown): value is FormChange {
  return (
    typeof value === 'object' &&
    value !== null &&
    Array.isArray((value as FormChange).path)
  );
}

export function inputName(path: string[]): string {
  return `${path[0]}${path
    .slice(1)
    .map((segment) => `[${segment}]`)
    .join('')}`;
}

export function formTabPanelId(uid: string, scope: string[]): string {
  const id = `form-tab-${uid}`;

  return scope.length ? Craft.namespaceId(id, inputName(scope)) : id;
}

export function valueAt(source: FormValue, path: string[]): FormValue {
  let value = source;

  for (const segment of path) {
    if (!isRecord(value)) {
      return undefined;
    }
    value = value[segment];
  }

  return value;
}

export function setValue(
  source: FormValues,
  path: string[],
  value: FormValue
): void {
  let target = source;

  path.forEach((segment, index) => {
    if (index === path.length - 1) {
      target[segment] = value;

      return;
    }

    if (!isRecord(target[segment])) {
      target[segment] = {};
    }
    target = target[segment];
  });
}

export function unsetValue(source: FormValue, path: string[]): void {
  if (!isRecord(source) || path.length === 0) {
    return;
  }

  const parent = valueAt(source, path.slice(0, -1));

  if (isRecord(parent)) {
    delete parent[path.at(-1)!];
  }
}

export function pathsMatch(left: string[], right: string[]): boolean {
  return (
    left.length === right.length &&
    left.every((segment, index) => segment === right[index])
  );
}

/**
 * A stable string for a form value, for equality checks.
 *
 * `JSON.stringify` on its own is key-order sensitive, so two values a form
 * would treat as identical can serialize differently purely because a server
 * renderer emitted the keys in another order. Anything asking "did this
 * change?" wants this rather than raw `JSON.stringify`.
 */
export function canonical(value: FormValue): string {
  return JSON.stringify(canonicalValue(value));
}

export function canonicalValue(value: FormValue): CanonicalFormValue {
  // Nothing and empty mean the same thing to a form, so a control reporting
  // one where the server sent the other has not edited anything. Without
  // this, populating a field on load can read as a change purely because the
  // control's idea of empty differs from the server's.
  if (value === null || value === undefined || value === '') {
    return '';
  }

  if (Array.isArray(value)) {
    return value.map(canonicalValue);
  }

  if (value instanceof File) {
    return {
      name: value.name,
      size: value.size,
      type: value.type,
      lastModified: value.lastModified,
    };
  }

  if (!isRecord(value)) return value;

  return Object.fromEntries(
    Object.keys(value)
      .sort()
      .map((key) => [key, canonicalValue(value[key])])
  );
}

export function isRecord(value: FormValue): value is FormValues {
  return (
    value instanceof Object && !Array.isArray(value) && !(value instanceof File)
  );
}
