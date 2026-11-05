import {toRaw} from 'vue';
import {isRecord, pathsMatch, visitControls} from '../runtime';
import type {
  NestedUiPayload,
  UiControlPayload,
  UiNodePayload,
  UiPayload,
  UiValue,
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
