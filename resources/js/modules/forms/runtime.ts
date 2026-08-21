import {Validator} from '@lion/ui/form-core.js';
import type {InjectionKey, Slots} from 'vue';
import type {FormChange} from './types';

export const FormFailure: InjectionKey<(message: string) => void> =
  Symbol('FormFailure');

export const FormControlOverrides: InjectionKey<Readonly<Slots>> = Symbol(
  'FormControlOverrides'
);

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
    if (!(event as CustomEvent).detail?.initialize) {
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

export function valueAt(source: unknown, path: string[]): unknown {
  return path.reduce<unknown>(
    (value, segment) =>
      (value as Record<string, unknown> | undefined)?.[segment],
    source
  );
}

export function setValue(
  source: Record<string, unknown>,
  path: string[],
  value: unknown
): void {
  let target = source;

  path.forEach((segment, index) => {
    if (index === path.length - 1) {
      target[segment] = value;

      return;
    }

    target[segment] ??= {};
    target = target[segment] as Record<string, unknown>;
  });
}

export function unsetValue(source: unknown, path: string[]): void {
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

export function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}
