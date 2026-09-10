import type {InertiaForm} from '@inertiajs/vue3';
import {
  computed,
  shallowRef,
  toRaw,
  toValue,
  watch,
  type MaybeRefOrGetter,
} from 'vue';
import type {FormChangeKind, FormPayload, FormValue} from './types';
import {pathsMatch, visitControls} from './runtime';

interface FormRendererInstance {
  advanceBaseline(): void;
  currentValues(): FormPayload['values'];
  resetValues(): void;
  setValue(path: string[], value: FormValue, kind?: FormChangeKind): void;
}

interface Options<T extends object, Key extends Extract<keyof T, string>> {
  mutationKey?: Key;
  mapErrorPath?: (path: string) => string[] | null;
}

/**
 * Bridges a FormRenderer island into an Inertia form. The Inertia field always
 * contains the editable mutation; `values` contains the full current values.
 */
export function useInertiaFormRenderer<
  T extends object,
  Key extends Extract<keyof T, string> = Extract<keyof T, string>,
>(
  form: InertiaForm<T>,
  payload: MaybeRefOrGetter<FormPayload | null>,
  {mutationKey, mapErrorPath}: Options<T, Key> = {}
) {
  const renderer = shallowRef<FormRendererInstance | null>(null);
  const values = shallowRef(clone(toValue(payload)?.values ?? {}));
  const formData: T = form;
  const rootKeys = new Set(Object.keys(form.data()));

  replaceMutation({});
  form.defaults();

  watch(
    () => toValue(payload),
    (currentPayload) => (values.value = clone(currentPayload?.values ?? {}))
  );

  const errors = computed(() =>
    Object.entries(form.errors).flatMap(([path, message]) => {
      const mappedPath = mapErrorPath
        ? mapErrorPath(path)
        : defaultErrorPath(path, toValue(payload));

      return mappedPath
        ? [{path: mappedPath, messages: [String(message)]}]
        : [];
    })
  );

  /**
   * Applies the renderer's mutation to the Inertia form, and reports whether it
   * carried anything.
   *
   * The mutation *is* the difference against the values the server sent, so an
   * empty one means the screen matches the server — a control announcing itself
   * as it's populated, say, rather than an edit. Callers that act on changes
   * (autosave) read that from here rather than deciding for themselves, and
   * `form.isDirty` says the same thing a tick later for callers that can wait.
   */
  function onMutation(mutation: FormPayload['values']): boolean {
    replaceMutation(mutation);
    values.value = renderer.value?.currentValues() ?? values.value;

    return Object.keys(mutation).length > 0;
  }

  function replaceMutation(mutation: FormPayload['values']): void {
    if (mutationKey !== undefined) {
      Object.assign(formData, {
        [mutationKey]: clone(mutation[mutationKey] ?? {}),
      });

      return;
    }

    for (const key of rootKeys) {
      Reflect.deleteProperty(formData, key);
    }

    for (const [key, value] of Object.entries(mutation)) {
      if (!rootKeys.has(key)) {
        form.defaults({...form.data(), [key]: undefined});
        rootKeys.add(key);
      }

      Object.assign(formData, {[key]: clone(value)});
    }
  }

  function advanceBaseline(): void {
    renderer.value?.advanceBaseline();
    form.defaults();
  }

  /**
   * Throws away the unsaved values and takes the currently loaded payload as
   * the new baseline, leaving the Inertia form clean.
   *
   * Only for the case where the user has explicitly abandoned their edits —
   * discarding a provisional draft. An ordinary refresh must keep them.
   */
  function resetValues(): void {
    renderer.value?.resetValues();
    // The renderer's reset emits an empty mutation of its own, but the bridge
    // has to hold up on its own when nothing is mounted to emit one.
    replaceMutation({});
    values.value = clone(toValue(payload)?.values ?? {});
    form.defaults();
  }

  return {advanceBaseline, errors, onMutation, renderer, resetValues, values};
}

/**
 * Resolves a server error key onto the control that owns it.
 *
 * The key is whatever the validator used — a field handle, say — while a
 * control's path is where it actually sits in the form, which can be deeper: a
 * field layout nests its custom fields under `fields`. The server resolves this
 * when it renders (walking up from the error path to the owning control), but
 * errors that come back from a save arrive as raw validator keys and need the
 * same treatment, or they match no control and read as global.
 *
 * Resolved against the payload's real control paths rather than by assuming a
 * prefix, so nesting the runtime doesn't know about still lands.
 */
function defaultErrorPath(
  path: string,
  payload: FormPayload | null | undefined
): string[] | null {
  const segments = path.split('.');
  const scope = payload?.scope ?? [];
  const withinScope = scope.every(
    (segment, index) => segments[index] === segment
  );
  const paths: string[][] = [];
  visitControls(payload?.nodes ?? [], (control) => paths.push(control.path));

  if (
    withinScope &&
    paths.some((candidate) => pathsMatch(candidate, segments))
  ) {
    return segments;
  }

  // Nothing owns the key as sent, so look for the control it names — the
  // shortest path ending in those segments, to avoid reaching past a nearer
  // owner into something nested.
  const owners = paths
    .filter(
      (candidate) =>
        candidate.length > segments.length &&
        scope.every((segment, index) => candidate[index] === segment) &&
        pathsMatch(candidate.slice(-segments.length), segments)
    )
    .sort((a, b) => a.length - b.length);

  if (owners.length > 0) {
    return owners[0]!;
  }

  // Unowned keys stay as they are, so they surface as global errors rather
  // than being dropped.
  return withinScope ? segments : null;
}

function clone<T>(value: T): T {
  return structuredClone(toRaw(value));
}
