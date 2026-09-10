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
 * The value a control should render with.
 *
 * {@link valueAt} returns undefined when the path isn't in the tree, which
 * happens for a beat inside a nested form: the payload describing a control can
 * arrive an emit ahead of the values filling it — a Matrix block the server has
 * just minted, a repeater whose identity the server has just rewritten. A
 * control whose value is a shape would reach into nothing and throw, and a
 * throw during render takes the whole field down with it.
 *
 * So the control's own declared empty value stands in until the real one lands.
 * `Control::emptyValue()` decides what empty means for each control, and ships
 * it in the payload; a control whose value is a scalar declares nothing and
 * still reads undefined, which is what those coerce anyway.
 *
 * Every path that hands a value to a control goes through here, so no control
 * has to remember to guard itself.
 */
export function controlValueAt(
  values: FormValue,
  // `emptyValue` is typed here and nowhere else: `FormControlPayload` omits it
  // on purpose — see the note there — and this is the only thing that reads it.
  control: {path: string[]; emptyValue?: unknown}
): FormValue {
  const value = valueAt(values, control.path);

  if (value !== undefined) {
    return value;
  }

  // SAFETY: what a Control ships as its empty value is a form value.
  return control.emptyValue as FormValue;
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
