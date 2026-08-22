import {Validator} from '@lion/ui/form-core.js';
import type {InjectionKey, Ref, Slots} from 'vue';
import type {FormChange, FormValue, FormValues} from './types';

export const FormFailure: InjectionKey<(message: string) => void> =
  Symbol('FormFailure');

export const FormControlOverrides: InjectionKey<Readonly<Slots>> = Symbol(
  'FormControlOverrides'
);

/** Modified delta groups as dotted paths, provided to every field beneath. */
export const FormModifiedGroups: InjectionKey<Readonly<Ref<Set<string>>>> =
  Symbol('FormModifiedGroups');

class ServerError extends Validator {
  static override validatorName = 'ServerError';

  override execute(): boolean {
    return true;
  }
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

export function isRecord(value: FormValue): value is FormValues {
  return (
    value instanceof Object && !Array.isArray(value) && !(value instanceof File)
  );
}
