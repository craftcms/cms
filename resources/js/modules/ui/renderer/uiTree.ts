import {toRaw} from 'vue';
import {isRecord, pathsMatch, valueAt, visitControls} from '../runtime';
import type {
  NestedUiPayload,
  UiControlPayload,
  UiNodePayload,
  UiPayload,
  UiValue,
  UiValues,
} from '../types';

/**
 * Pure transforms over UI payloads and value trees. Each returns a new tree
 * rather than changing the one it's given, so a payload the host handed in
 * is never edited behind its back.
 */

export function cloneRaw<T extends UiValue>(value: T): T {
  // SAFETY: unwrap removes Vue proxies without changing the recursive value
  // tree represented by T.
  return structuredClone(unwrap(value)) as T;
}

function unwrap(value: UiValue): UiValue {
  const raw = toRaw(value);

  if (Array.isArray(raw)) {
    return raw.map(unwrap);
  }

  if (isRecord(raw)) {
    return Object.fromEntries(
      Object.entries(raw).map(([key, value]) => [key, unwrap(value)])
    );
  }

  return raw;
}

/**
 * `next`, with each part that's deeply equal to the same part of `previous`
 * taken from `previous` instead. A server sends the whole tree again even
 * when little of it changed, and the parts that didn't keep their identity
 * this way, so Vue skips re-rendering them. The result is always raw, so a
 * part shared from a reactive payload has the same identity as one shared from
 * a plain one.
 */
export function shareEqual<T extends UiValue>(
  previous: UiValue | undefined,
  next: T
): T {
  // Walks the raw values, so a computed calling this doesn't track every node
  // it reads.
  const prev = toRaw(previous);
  const raw = toRaw(next);

  if (Object.is(prev, raw)) {
    return raw;
  }

  if (Array.isArray(prev) && Array.isArray(raw)) {
    let same = prev.length === raw.length;
    const shared = raw.map((item, index) => {
      const result = shareEqual(prev[index], item);
      same &&= result === prev[index];

      return result;
    });

    // SAFETY: each item is `next`'s own item or one deeply equal to it.
    return (same ? prev : shared) as T;
  }

  if (isRecord(prev) && isRecord(raw)) {
    const keys = Object.keys(raw);
    let same = keys.length === Object.keys(prev).length;
    const shared: UiValues = {};

    for (const key of keys) {
      shared[key] = shareEqual(prev[key], raw[key]!);
      same &&= key in prev && shared[key] === prev[key];
    }

    // SAFETY: each entry is `next`'s own entry or one deeply equal to it.
    return (same ? prev : shared) as T;
  }

  return raw;
}

/**
 * `next`, with everything but its values shared with `previous` by
 * {@link shareEqual}. Its values stay its own, because hosts take a payload's
 * values whenever a new payload arrives, even when only the values changed.
 * Gives back `previous` when nothing differs, so a host recomputing its
 * payload from the same source doesn't hand its renderer a new one.
 * {@link withReusedUis} first fills in the placeholders the server sent.
 */
export function sharePayload<T extends UiPayload | null | undefined>(
  previous: UiPayload | null | undefined,
  next: T
): T {
  if (!next) {
    return next;
  }

  if (!previous) {
    return toRaw(next);
  }

  // SAFETY: UI payloads are JSON, so each of their parts is a UiValue.
  const raw = withReusedUis(previous, toRaw(next)) as unknown as UiValues;
  const prev = toRaw(previous) as unknown as UiValues;

  const shared = Object.fromEntries(
    Object.entries(raw).map(([key, value]) => [
      key,
      key === 'values' ? value : shareEqual(prev[key], value),
    ])
  );
  const keys = Object.keys(shared);
  const unchanged =
    keys.length === Object.keys(prev).length &&
    keys.every((key) => shared[key] === prev[key]);

  // SAFETY: `shared` has `next`'s keys, each holding `next`'s own value or one
  // deeply equal to it.
  return (unchanged ? previous : shared) as T;
}

/**
 * `next`, with each `reused` placeholder replaced by the nested UI at the same
 * scope in `previous`, the payload the client shows, and that UI's values
 * copied over. A server sends placeholders only to a client that asks for
 * them, so it builds and sends only the nested UIs a request changed.
 *
 * A placeholder stays when `previous` has no UI at its scope. That only
 * happens when a host asks for placeholders without showing the whole payload.
 */
export function withReusedUis(previous: UiPayload, next: UiPayload): UiPayload {
  let values = next.values;

  const fillUis = (uis: NestedUiPayload[]): NestedUiPayload[] => {
    const filled = uis.map((ui) => {
      if (!ui.reused) {
        const nodes = fillNodes(ui.nodes);

        return nodes === ui.nodes ? ui : {...ui, nodes};
      }

      const shown = findNestedUi(toRaw(previous).nodes, ui.scope);

      if (!shown || shown.reused) {
        return ui;
      }

      values = withMissingValues(
        values,
        ui.scope,
        valueAt(toRaw(previous).values, ui.scope)
      );

      return shown;
    });

    return filled.every((ui, index) => ui === uis[index]) ? uis : filled;
  };

  const fillNodes = (nodes: UiNodePayload[]): UiNodePayload[] => {
    const filled = nodes.map((node) => {
      const uis = node.control?.uis && fillUis(node.control.uis);
      const children = node.children && fillNodes(node.children);

      if (uis === node.control?.uis && children === node.children) {
        return node;
      }

      return {
        ...node,
        ...(node.control && {control: {...node.control, uis}}),
        ...(children && {children}),
      };
    });

    return filled.every((node, index) => node === nodes[index])
      ? nodes
      : filled;
  };

  const nodes = fillNodes(next.nodes);

  return nodes === next.nodes ? next : {...next, nodes, values};
}

/**
 * `values`, with the keys `source` has at `path` and `values` lacks there
 * added. Copies only the records along `path`.
 */
function withMissingValues(
  values: UiValues,
  path: string[],
  source: UiValue
): UiValues {
  if (!isRecord(source)) {
    return values;
  }

  const [segment, ...rest] = path;
  const current = values[segment!];
  const target = isRecord(current) ? current : {};

  return {
    ...values,
    [segment!]: rest.length
      ? withMissingValues(target, rest, source)
      : {...source, ...target},
  };
}

export function isWithin(path: string[], scope: string[]): boolean {
  return scope.every((segment, index) => path[index] === segment);
}

/** The nodes with every control disabled. */
export function disabledNodes(nodes: UiNodePayload[]): UiNodePayload[] {
  const disabled = cloneRaw(nodes);
  visitControls(disabled, (control) =>
    Object.assign(control, {mode: 'disabled'})
  );

  return disabled;
}

/**
 * The nodes with every editable control read-only, for a form that can't be
 * edited for now: its values stay legible and focusable, unlike disabled ones.
 */
export function readOnlyNodes(nodes: UiNodePayload[]): UiNodePayload[] {
  const readOnly = cloneRaw(nodes);
  visitControls(readOnly, (control) => {
    if (control.mode === 'editable') {
      Object.assign(control, {mode: 'readOnly'});
    }
  });

  return readOnly;
}

/** The payload with the control at `path` redefined. */
export function withControl(
  payload: UiPayload,
  path: string[],
  definition: UiControlPayload<object>
): UiPayload {
  const updated = cloneRaw(payload);
  visitControls(updated.nodes, (control) => {
    if (pathsMatch(control.path, path)) {
      Object.assign(control, definition);
    }
  });

  return updated;
}

/** The payload with the control at `path` holding `uis`. */
export function withUis(
  payload: UiPayload,
  path: string[],
  uis: NestedUiPayload[]
): UiPayload {
  const updated = cloneRaw(payload);
  visitControls(updated.nodes, (control) => {
    if (pathsMatch(control.path, path)) control.uis = uis;
  });

  return updated;
}

/**
 * The payload with the nested UI at `scope` replaced by its refreshed
 * presentation, errors included.
 */
export function withNestedUi(
  payload: UiPayload,
  scope: string[],
  refreshed: UiPayload
): UiPayload {
  const updated = cloneRaw(payload);
  const nested = findNestedUi(updated.nodes, scope);

  if (!nested) {
    throw new Error(`Refreshed UI scope [${scope.join('.')}] was not found.`);
  }

  nested.nodes = refreshed.nodes;
  nested.refreshable = refreshed.refreshable;

  return {
    ...updated,
    errors: [
      ...updated.errors.filter((error) => !isWithin(error.path, scope)),
      ...refreshed.errors,
    ],
  };
}

function findNestedUi(
  nodes: UiNodePayload[],
  scope: string[]
): NestedUiPayload | undefined {
  for (const node of nodes) {
    for (const ui of node.control?.uis ?? []) {
      if (pathsMatch(ui.scope, scope)) {
        return ui;
      }

      const nested = findNestedUi(ui.nodes, scope);

      if (nested) {
        return nested;
      }
    }

    const nested = findNestedUi(node.children ?? [], scope);

    if (nested) {
      return nested;
    }
  }
}
