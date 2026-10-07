import {Validator} from '@lion/ui/form-core.js';
import {type InjectionKey, type Ref, type Slots} from 'vue';
import type {
  CanonicalUiValue,
  UiChange,
  UiControlPayload,
  UiNodePayload,
  NestedUiPayload,
  UiValue,
  UiValues,
} from './types';
import type {ActionItems} from '@/common/types';

export const UiFailure: InjectionKey<(message: string) => void> =
  Symbol('UiFailure');

export const UiPending: InjectionKey<Readonly<Ref<boolean>>> =
  Symbol('UiPending');

export const UiErrors: InjectionKey<{
  clearChildren(path: string[]): void;
  childrenCleared(path: string[]): boolean;
}> = Symbol('UiErrors');

export type UiControlBehavior = {
  comparisonValue: (value: UiValue) => UiValue;
};

export const UiControlBehaviors: InjectionKey<
  (path: string[], behavior: UiControlBehavior) => () => void
> = Symbol('UiControlBehaviors');

export const UiControlStructure: InjectionKey<
  (control: Pick<UiControlPayload, 'path'>, uis: NestedUiPayload[]) => void
> = Symbol('UiControlStructure');

export const UiControlOverrides: InjectionKey<Readonly<Slots>> =
  Symbol('UiControlOverrides');

/** Modified delta groups as dotted paths, provided to every field beneath. */
export const UiModifiedGroups: InjectionKey<Readonly<Ref<Set<string>>>> =
  Symbol('UiModifiedGroups');

/**
 * Dotted paths of every control changed since the form was last reset. A field
 * holding nested uis badges when one lands at or below it — see FieldNode.
 */
export const UiChangedPaths: InjectionKey<Readonly<Ref<Set<string>>>> =
  Symbol('UiChangedPaths');

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

/** Control paths whose changes have an active UI refresh. */
export const UiRefreshingFields: InjectionKey<Readonly<Ref<Set<string>>>> =
  Symbol('UiRefreshingFields');

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
  values: UiValue,
  // `emptyValue` is typed here and nowhere else: `UiControlPayload` omits it
  // on purpose — see the note there — and this is the only thing that reads it.
  control: {path: string[]; emptyValue?: unknown}
): UiValue {
  const value = valueAt(values, control.path);

  if (value !== undefined) {
    return value;
  }

  // SAFETY: what a Control ships as its empty value is a form value.
  return control.emptyValue as UiValue;
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

export function uiChangeFromEvent(change: UiChange | Event): UiChange | null {
  // A component that doesn't declare `change` lets the listener fall through
  // to its root, so a child component's own `change` payload — a condition
  // builder's config, say — can arrive here. Only a real UiChange counts.
  if (!(change instanceof Event)) {
    return isUiChange(change) ? change : null;
  }

  const detail = change instanceof CustomEvent ? change.detail : null;

  // Only a Control's own CustomEvent carries a UiChange. Plenty of other
  // CustomEvents bubble through a form — request lifecycle events put
  // `{elt, xhr, …}` in `detail` — and forwarding one as a change hands
  // listeners an object with no `path`.
  return isUiChange(detail) ? detail : null;
}

function isUiChange(value: unknown): value is UiChange {
  return (
    typeof value === 'object' &&
    value !== null &&
    Array.isArray((value as UiChange).path)
  );
}

/**
 * The id of the field wrapping a control, mirroring `UiHtmlRenderer::id()`
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

export function uiTabPanelId(uid: string, scope: string[]): string {
  const id = `form-tab-${uid}`;

  return scope.length ? Craft.namespaceId(id, inputName(scope)) : id;
}

export function valueAt(source: UiValue, path: string[]): UiValue {
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
  source: UiValues,
  path: string[],
  value: UiValue
): void {
  let target: UiValues | UiValue[] = source;

  for (let index = 0; index < path.length; index++) {
    const segment = path[index]!;

    if (Array.isArray(target)) {
      if (!/^(0|[1-9][0-9]*)$/.test(segment)) {
        throw new Error(`UI array path [${segment}] must be a row index.`);
      }
      const rowIndex = Number(segment);

      if (index === path.length - 1) {
        target[rowIndex] = value;
        return;
      }

      const child: UiValue = target[rowIndex];
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

    const child: UiValue = target[segment];
    if (Array.isArray(child) || isRecord(child)) {
      target = child;
    } else {
      target[segment] = {};
      target = target[segment];
    }
  }
}

export function unsetValue(source: UiValue, path: string[]): void {
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
  nodes: UiNodePayload[],
  visit: (control: UiControlPayload) => void
): void {
  for (const node of nodes) {
    if (node.control) {
      visit(node.control);
      node.control.uis?.forEach((form) => visitControls(form.nodes, visit));
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
export function canonical(value: UiValue): string {
  return JSON.stringify(canonicalValue(value));
}

export function canonicalValue(value: UiValue): CanonicalUiValue {
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

export function isRecord(value: UiValue): value is UiValues {
  return (
    value instanceof Object && !Array.isArray(value) && !(value instanceof File)
  );
}
