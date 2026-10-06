import {Validator} from '@lion/ui/form-core.js';
import {type InjectionKey, type Ref, type Slots} from 'vue';
import type {
  CanonicalFormValue,
  FormChange,
  FormControlPayload,
  FormNodePayload,
  NestedFormPayload,
  FormValue,
  FormValues,
} from './types';
import type {ActionItems} from '@/common/types';

export const FormFailure: InjectionKey<(message: string) => void> =
  Symbol('FormFailure');

export const FormPending: InjectionKey<Readonly<Ref<boolean>>> =
  Symbol('FormPending');

export const FormErrors: InjectionKey<{
  clearChildren(path: string[]): void;
  childrenCleared(path: string[]): boolean;
}> = Symbol('FormErrors');

export type FormControlBehavior = {
  comparisonValue: (value: FormValue) => FormValue;
};

export const FormControlBehaviors: InjectionKey<
  (path: string[], behavior: FormControlBehavior) => () => void
> = Symbol('FormControlBehaviors');

export const FormControlStructure: InjectionKey<
  (
    control: Pick<FormControlPayload, 'path'>,
    forms: NestedFormPayload[]
  ) => void
> = Symbol('FormControlStructure');

export const FormControlOverrides: InjectionKey<Readonly<Slots>> = Symbol(
  'FormControlOverrides'
);

/** Modified delta groups as dotted paths, provided to every field beneath. */
export const FormModifiedGroups: InjectionKey<Readonly<Ref<Set<string>>>> =
  Symbol('FormModifiedGroups');

/**
 * Dotted paths of every control changed since the form was last reset. A field
 * holding nested forms badges when one lands at or below it — see FieldNode.
 */
export const FormChangedPaths: InjectionKey<Readonly<Ref<Set<string>>>> =
  Symbol('FormChangedPaths');

/**
 * Lets a field's control rewrite the field's "⋮" menu with state only the
 * control has — a Matrix relabels "Copy all blocks" once blocks are selected.
 * Provided by each FieldNode, so a nested field's control reaches only its own
 * field's menu.
 */
export const FieldActionItems: InjectionKey<
  Ref<((items: ActionItems) => ActionItems) | undefined>
> = Symbol('FieldActionItems');

/**
 * Whether the surrounding field's label is visually hidden, so controls with
 * their own label chrome (e.g. `craft-select`) can hide theirs too.
 */
export const FieldLabelSrOnly: InjectionKey<Readonly<Ref<boolean>>> =
  Symbol('FieldLabelSrOnly');

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
  // A component that doesn't declare `change` lets the listener fall through
  // to its root, so a child component's own `change` payload — a condition
  // builder's config, say — can arrive here. Only a real FormChange counts.
  if (!(change instanceof Event)) {
    return isFormChange(change) ? change : null;
  }

  const detail = change instanceof CustomEvent ? change.detail : null;

  // Only a Control's own CustomEvent carries a FormChange. Plenty of other
  // CustomEvents bubble through a form — request lifecycle events put
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

/**
 * The id of the field wrapping a control, mirroring `FormHtmlRenderer::id()`
 * so both renderers agree on how a path becomes an id.
 *
 * PHP uses `rawurlencode`, which differs from `encodeURIComponent` on `!'()*` —
 * unreachable for ordinary field handles, but cheap to match exactly rather
 * than leave the two renderers free to disagree on an exotic path segment.
 */
export function fieldId(path: string[]): string {
  const encoded = path.map((segment) =>
    encodeURIComponent(segment).replace(
      /[!'()*]/g,
      (character) => `%${character.charCodeAt(0).toString(16).toUpperCase()}`
    )
  );

  return `form-${encoded.join('-')}`;
}

/** The id of the control's own input, which sits inside that field. */
export function inputId(path: string[]): string {
  return `${fieldId(path)}-input`;
}

export function inputName(path: string[]): string {
  return `${path[0]}${path
    .slice(1)
    .map((segment) => `[${segment}]`)
    .join('')}`;
}

const ID_CHARACTERS =
  'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

function randomId(length = 10): string {
  let id = '';

  for (let index = 0; index < length; index++) {
    id += ID_CHARACTERS.charAt(
      Math.floor(Math.random() * ID_CHARACTERS.length)
    );
  }

  return id;
}

/**
 * Normalizes a string into an element id.
 *
 * A port of `CraftCms\Cms\Support\Html::id()`, which is what builds these ids
 * server-side — the markup a form renders carries ids the client then has to
 * address, so the two have to agree character for character.
 */
export function elementId(id = ''): string {
  // Placeholders pass through untouched, e.g. `__NAMESPACE__-fieldId`.
  if (/^__[A-Z_]+__/.test(id)) {
    return id;
  }

  const normalized = id
    // Drop invalid characters already sitting against a hyphen, so they don't
    // each become a hyphen of their own below.
    .replace(/(?<=-)[^A-Za-z0-9_.-]+|[^A-Za-z0-9_.-]+(?=-)/g, '')
    // Collapse whatever invalid characters are left into single hyphens.
    .replace(/[^A-Za-z0-9_.-]+/g, '-')
    .replace(/^-+|-+$/g, '');

  return normalized || randomId();
}

/**
 * Namespaces an element id.
 *
 * Mirrors `InputNamespace::namespaceId()`: the namespace and id are joined and
 * then normalized **once**, rather than normalized separately and joined. That
 * distinction matters, because the server builds the id the same way and the
 * two must match.
 *
 * Deliberately not `Craft.namespaceId()`. That lives only in the legacy CP
 * bundle, so borrowing it made rendering a form node fail outright wherever
 * that bundle isn't loaded — `Craft.namespaceId is not a function`. It also
 * normalizes each part separately, so it disagreed with the server for ids the
 * joining itself affects.
 */
export function namespaceId(id: string, namespace?: string | null): string {
  if (id === '') {
    return id;
  }

  return namespace ? elementId(`${namespace}-${id}`) : elementId(id);
}

export function formTabPanelId(uid: string, scope: string[]): string {
  const id = `form-tab-${uid}`;

  return scope.length ? namespaceId(id, inputName(scope)) : id;
}

export function valueAt(source: FormValue, path: string[]): FormValue {
  let value = source;

  for (const segment of path) {
    if (Array.isArray(value)) {
      if (!/^(0|[1-9][0-9]*)$/.test(segment)) return undefined;
      value = value[Number(segment)];
    } else if (isRecord(value)) {
      value = value[segment];
    } else {
      return undefined;
    }
  }

  return value;
}

export function setValue(
  source: FormValues,
  path: string[],
  value: FormValue
): void {
  let target: FormValues | FormValue[] = source;

  for (let index = 0; index < path.length; index++) {
    const segment = path[index]!;

    if (Array.isArray(target)) {
      if (!/^(0|[1-9][0-9]*)$/.test(segment)) {
        throw new Error(`Form array path [${segment}] must be a row index.`);
      }
      const rowIndex = Number(segment);

      if (index === path.length - 1) {
        target[rowIndex] = value;
        return;
      }

      const child: FormValue = target[rowIndex];
      if (Array.isArray(child) || isRecord(child)) {
        target = child;
      } else {
        target[rowIndex] = {};
        target = target[rowIndex];
      }
      continue;
    }

    if (index === path.length - 1) {
      target[segment] = value;
      return;
    }

    const child: FormValue = target[segment];
    if (Array.isArray(child) || isRecord(child)) {
      target = child;
    } else {
      target[segment] = {};
      target = target[segment];
    }
  }
}

export function unsetValue(source: FormValue, path: string[]): void {
  if (path.length === 0) return;
  const parent = valueAt(source, path.slice(0, -1));
  const segment = path.at(-1)!;

  if (Array.isArray(parent) && /^(0|[1-9][0-9]*)$/.test(segment)) {
    parent[Number(segment)] = undefined;
  } else if (isRecord(parent)) {
    delete parent[segment];
  }
}

export function visitControls(
  nodes: FormNodePayload[],
  visit: (control: FormControlPayload) => void
): void {
  for (const node of nodes) {
    if (node.control) {
      visit(node.control);
      node.control.forms?.forEach((form) => visitControls(form.nodes, visit));
    }

    if (node.children) {
      visitControls(node.children, visit);
    }
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
