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

  /**
   * Takes in nodes the server held back, such as a deferred tab's, along with
   * their values. Those values are what the server already holds, so they join
   * the baseline as well as the values, rather than reading as edits.
   */
  function adoptDeferred(nodes: UiNodePayload[], source: UiValues): void {
    visitControls(nodes, (control) => {
      const group = control.deltaGroup;

      if (valueAt(baseline, group) === undefined) {
        setPathValue(baseline, group, cloneRaw(valueAt(source, group)));
      }

      if (valueAt(values, group) === undefined) {
        write(group, cloneRaw(valueAt(source, group)));
      }
    });
    rememberControls(nodes);
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

  /**
   * Takes in a successful submission's response, the payload the server
   * rendered from what it saved. `submitted` is what the submission sent, and
   * `held` what the form held when the response arrived.
   *
   * What the server saved can differ from what was sent: applying a draft
   * replaces the nested entries created in it, say. So values left as they
   * were sent take the server's, rather than going on referring to elements
   * that are gone. Edits made while the submission was out stay unsaved, on
   * the identities that replaced the ones they were made to.
   *
   * `later` settles only the groups in `saved`, such as those of tabs the
   * server sent once the page had loaded, keeping the rest of the baseline.
   */
  function settle(
    saved: UiPayload,
    submitted: UiValues,
    held: UiValues,
    later = false
  ): void {
    const savedBaseline = cloneRaw(later ? baseline : submitted);
    const groups = new Map<string, string[]>();

    visitControls(saved.nodes, (control) => {
      // A group at the root is the whole form, which no one control speaks for.
      if (control.mode === 'editable' && control.deltaGroup.length) {
        groups.set(JSON.stringify(control.deltaGroup), control.deltaGroup);
      }
    });

    for (const group of groups.values()) {
      const savedValue = valueAt(saved.values, group);

      if (savedValue === undefined) {
        continue;
      }

      setPathValue(savedBaseline, group, cloneRaw(savedValue));

      const sentValue = valueAt(submitted, group);
      const sent = groupCanonical(sentValue, group);

      if (groupCanonical(valueAt(held, group), group) === sent) {
        if (
          groupCanonical(valueAt(values, group), group) !==
          groupCanonical(savedValue, group)
        ) {
          write(group, cloneRaw(savedValue));
        }

        continue;
      }

      const renames = new Map<string, string>();
      collectRenames(sentValue, savedValue, renames);

      if (renames.size) {
        write(group, renamed(valueAt(values, group), renames));
        renameTrackedPaths(renames);
      }
    }

    rebase(savedBaseline);
  }

  /** Moves the edits recorded under replaced identities onto their replacements. */
  function renameTrackedPaths(renames: Map<string, string>): void {
    const rename = (path: string[]) =>
      path.map((segment) => renames.get(segment) ?? segment);

    // A copy, as the loop changes the set.
    for (const key of Array.from(touchedPaths)) {
      const next = JSON.stringify(rename(JSON.parse(key) as string[]));

      if (next !== key) {
        touchedPaths.delete(key);
        touchedPaths.add(next);
      }
    }

    changedPaths.value = new Set(
      [...changedPaths.value].map((path) => rename(path.split('.')).join('.'))
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

  /** The control last seen at `path`, if any. */
  function controlAt(path: string[]): UiControlPayload | undefined {
    return knownControls.get(JSON.stringify(path));
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
    adoptDeferred,
    reset,
    rebase,
    settle,
    snapshot,
    mutation,
    currentValues,
    groupCanonical,
    registerBehavior,
    rememberControls,
    controlAt,
    restructured,
  };
}

/**
 * The identities a save replaced, such as nested entries' UIDs, read off lists
 * of them: the server keeps what was sent in the order it was sent, so an
 * identity in a list it saved stands for the one sent in its place. Records
 * are followed under the keys that replaced theirs, so the lists inside the
 * replaced entries are read too.
 */
function collectRenames(
  sent: UiValue,
  saved: UiValue,
  renames: Map<string, string>
): void {
  if (Array.isArray(sent) && Array.isArray(saved)) {
    if (sent.length !== saved.length) {
      return;
    }

    sent.forEach((sentItem, index) => {
      const savedItem = saved[index];

      if (
        typeof sentItem === 'string' &&
        typeof savedItem === 'string' &&
        sentItem !== savedItem &&
        !saved.includes(sentItem) &&
        !sent.includes(savedItem)
      ) {
        renames.set(sentItem, savedItem);
      } else {
        collectRenames(sentItem, savedItem, renames);
      }
    });

    return;
  }

  if (!isRecord(sent) || !isRecord(saved)) {
    return;
  }

  // Lists first, as they name the keys of the records beside them.
  const keys = Object.keys(sent).sort(
    (a, b) => Number(!Array.isArray(sent[a])) - Number(!Array.isArray(sent[b]))
  );

  for (const key of keys) {
    const savedKey = renames.get(key) ?? key;

    if (savedKey in saved) {
      collectRenames(sent[key], saved[savedKey], renames);
    }
  }
}

/**
 * A copy of `value` that uses the identities that replaced the ones it has,
 * as record keys and list items: the places {@link collectRenames} reads them.
 */
function renamed(value: UiValue, renames: Map<string, string>): UiValue {
  if (Array.isArray(value)) {
    return value.map((item) =>
      typeof item === 'string'
        ? (renames.get(item) ?? item)
        : renamed(item, renames)
    );
  }

  if (isRecord(value)) {
    const replaced = new Set(
      Object.keys(value).flatMap((key) => renames.get(key) ?? [])
    );
    const result: UiValues = Object.fromEntries(
      Object.entries(value)
        .filter(([key]) => !replaced.has(key))
        .map(([key, item]) => [renames.get(key) ?? key, renamed(item, renames)])
    );

    // The response's own copy of a replacement, which the values took in when
    // it arrived, gives way to the one the client is holding, but fills in
    // what that lacks, as it would have had their keys matched.
    for (const key of replaced) {
      const response = value[key];
      const held = result[key];

      if (isRecord(held) && isRecord(response)) {
        mergeMissing(held, response);
      }
    }

    return result;
  }

  return cloneRaw(value);
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
