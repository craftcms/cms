import {reactive, readonly, ref} from 'vue';
import {
  canonical,
  isRecord,
  setValue as setPathValue,
  unsetValue,
  valueAt,
  visitControls,
  type UiControlBehavior,
} from '../runtime';
import type {
  NestedUiPayload,
  UiControlPayload,
  UiNodePayload,
  UiPayload,
  UiValue,
  UiValues,
} from '../types';
import {cloneRaw, isWithin} from './uiTree';

/**
 * The values a UI holds, measured against the baseline the server last
 * saved: what's changed, what's been touched, and what to submit.
 *
 * Values are changed in place. Controls compare the value they're handed by
 * identity — the legacy HTML control repaints its inputs only when its value
 * is a new object — so an ancestor changing identity would read as a new
 * value from the server.
 */
export function useUiValues(initial: UiPayload) {
  const values = reactive(cloneRaw(initial.values));
  let baseline = cloneRaw(initial.values);
  const knownControls = new Map<string, UiControlPayload>();
  const touchedPaths = reactive(new Set<string>());
  /**
   * Dotted paths changed since the last explicit save or reset. Nested fields
   * use these to mark changes not yet reported by the server.
   */
  const changedPaths = ref(new Set<string>());
  const controlBehaviors = reactive(
    new Map<string, {path: string[]; behavior: UiControlBehavior}>()
  );

  rememberControls(initial.nodes);

  function write(path: string[], value: UiValue): void {
    setPathValue(values, path, value);
  }

  /**
   * Notes an edit at `path`. A control can report a change that leaves its
   * value where it started — rewriting an input's value on reset makes it
   * re-emit, for one. That isn't a change, and recording it would badge a
   * field nobody touched.
   */
  function recordEdit(path: string[]): void {
    if (
      canonical(valueAt(values, path)) !== canonical(valueAt(baseline, path))
    ) {
      touchedPaths.add(JSON.stringify(path));
      changedPaths.value.add(path.join('.'));
    }
  }

  /**
   * Takes in a refreshed payload: values the client has never held, and the
   * values of every control the client can't edit. The client's own unsaved
   * values stay.
   */
  function adopt(refreshed: UiPayload): void {
    mergeMissing(values, refreshed.values);
    rememberControls(refreshed.nodes);
    visitControls(refreshed.nodes, (control) => {
      if (control.mode !== 'editable') {
        write(control.path, valueAt(refreshed.values, control.path));
      }
    });
  }

  /** Seeds the values again from `source`, as mounting it would have. */
  function reset(source: UiPayload): void {
    touchedPaths.clear();
    changedPaths.value.clear();
    knownControls.clear();

    for (const key of Object.keys(values)) {
      delete values[key];
    }

    Object.assign(values, cloneRaw(source.values));
    baseline = cloneRaw(source.values);
    rememberControls(source.nodes);
  }

  /** Takes `source` as what the server now holds, and drops the changes it settles. */
  function rebase(source: UiValues): void {
    baseline = cloneRaw(source);
    changedPaths.value = new Set(
      [...changedPaths.value].filter((path) => {
        const segments = path.split('.');
        return (
          groupCanonical(valueAt(values, segments), segments) !==
          groupCanonical(valueAt(baseline, segments), segments)
        );
      })
    );
  }

  function snapshot(): UiValues;
  function snapshot(scope: string[]): UiValue;
  function snapshot(scope?: string[]): UiValue {
    return cloneRaw(scope ? valueAt(values, scope) : values);
  }

  /** The editable delta groups of `nodes` that differ from the baseline. */
  function mutation(
    nodes: UiNodePayload[],
    includeGroups: string[][] = []
  ): UiValues {
    const included = new Set(includeGroups.map((path) => JSON.stringify(path)));
    const groups = new Map<string, string[]>();
    const editablePaths = new Set<string>();

    visitControls(nodes, (control) => {
      if (control.mode === 'editable') {
        groups.set(JSON.stringify(control.deltaGroup), control.deltaGroup);
        editablePaths.add(JSON.stringify(control.path));
      }
    });

    const result: UiValues = {};

    for (const path of groups.values()) {
      const current = groupValue(values, path, editablePaths);
      const original = groupValue(baseline, path, editablePaths);

      if (
        included.has(JSON.stringify(path)) ||
        groupCanonical(current, path) !== groupCanonical(original, path)
      ) {
        if (path.length === 0 && isRecord(current)) {
          Object.assign(result, current);

          continue;
        }

        setPathValue(result, path, current);
      }
    }

    return result;
  }

  /** Every delta group of `nodes`, whatever its mode. */
  function currentValues(nodes: UiNodePayload[]): UiValues {
    const groups = new Map<string, string[]>();
    const controlPaths = new Set<string>();

    visitControls(nodes, (control) => {
      groups.set(JSON.stringify(control.deltaGroup), control.deltaGroup);
      controlPaths.add(JSON.stringify(control.path));
    });

    const result: UiValues = {};

    for (const path of groups.values()) {
      const value = groupValue(values, path, controlPaths);

      if (path.length === 0 && isRecord(value)) {
        Object.assign(result, value);

        continue;
      }

      if (value !== undefined) {
        setPathValue(result, path, value);
      }
    }

    return result;
  }

  /** Serializes a scope's values the way change detection compares them. */
  function groupCanonical(value: UiValue, path: string[]): string {
    const comparisons: UiValue[] = [];
    for (const {path: controlPath, behavior} of controlBehaviors.values()) {
      if (behavior.comparisonValue && isWithin(controlPath, path)) {
        comparisons.push([
          controlPath,
          behavior.comparisonValue(
            valueAt(value, controlPath.slice(path.length))
          ),
        ]);
      }
    }
    return canonical([value, comparisons]);
  }

  function registerBehavior(
    path: string[],
    behavior: UiControlBehavior
  ): () => void {
    const key = JSON.stringify(path);
    controlBehaviors.set(key, {path, behavior});
    return () => controlBehaviors.delete(key);
  }

  function rememberControls(nodes: UiNodePayload[]): void {
    visitControls(nodes, (control) =>
      knownControls.set(JSON.stringify(control.path), control)
    );
  }

  /**
   * Whether the control at `path` now holds different nested UIs. When it
   * does, the controls inside the old ones are forgotten.
   */
  function restructured(path: string[], uis: NestedUiPayload[]): boolean {
    if (
      canonical(knownControls.get(JSON.stringify(path))?.uis ?? []) ===
      canonical(uis)
    ) {
      return false;
    }

    for (const [key, known] of knownControls) {
      if (known.path.length > path.length && isWithin(known.path, path)) {
        knownControls.delete(key);
      }
    }

    return true;
  }

  function groupValue(
    source: UiValues,
    groupPath: string[],
    editablePaths: Set<string>
  ): UiValue {
    const value = cloneRaw(valueAt(source, groupPath));

    if (
      knownControls.get(JSON.stringify(groupPath))?.omitNullValue &&
      value == null
    ) {
      return undefined;
    }

    for (const [key, control] of knownControls) {
      if (
        (!editablePaths.has(key) ||
          (control.omitNullValue && valueAt(source, control.path) == null)) &&
        control.path
          .slice(0, groupPath.length)
          .every((segment, index) => segment === groupPath[index])
      ) {
        unsetValue(value, control.path.slice(groupPath.length));
      }
    }

    return value;
  }

  return {
    /** Handed to every control below; written only through {@link write}. */
    values,
    touchedPaths,
    changedPaths: readonly(changedPaths),
    write,
    recordEdit,
    adopt,
    reset,
    rebase,
    snapshot,
    mutation,
    currentValues,
    groupCanonical,
    registerBehavior,
    rememberControls,
    restructured,
  };
}

function mergeMissing(target: UiValues, source: UiValues): void {
  for (const [key, value] of Object.entries(source)) {
    if (!(key in target)) {
      target[key] = cloneRaw(value);

      continue;
    }

    if (isRecord(target[key]) && isRecord(value)) {
      mergeMissing(target[key], value);
    }
  }
}
