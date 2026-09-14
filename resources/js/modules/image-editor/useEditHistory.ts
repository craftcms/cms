import {computed, shallowRef, type ComputedRef} from 'vue';

export interface HistoryEntry<T> {
  /** What undoing this entry returns to. */
  before: T;
  /** What redoing it returns to. */
  after: T;
  /**
   * Recording again under the same key merges into this entry instead of
   * adding one -- how a run of keyboard nudges becomes a single step.
   */
  key?: string;
}

export interface EditHistoryOptions<T> {
  /** Whether two states are the same edit, so recording them is a no-op. */
  equals?: (a: T, b: T) => boolean;
  /** How many steps are kept before the oldest is dropped. */
  limit?: number;
}

export interface EditHistory<T> {
  canUndo: ComputedRef<boolean>;
  canRedo: ComputedRef<boolean>;
  record(before: T, after: T, key?: string): void;
  undo(): T | null;
  redo(): T | null;
  seal(): void;
  clear(): void;
}

/**
 * An undo/redo stack of before-and-after states.
 *
 * It holds states rather than operations: undoing hands back the state to
 * restore, not an action to reverse. Some edits have no clean inverse --
 * straightening back to the old angle doesn't give the old crop back, because
 * straightening fits the crop to the viewport as it goes -- so putting the
 * recorded state back is the only way an undo lands exactly.
 *
 * Knows nothing about images; the editor decides what a state is and how to
 * restore one.
 */
export function useEditHistory<T>({
  equals,
  limit = 100,
}: EditHistoryOptions<T> = {}): EditHistory<T> {
  const past = shallowRef<HistoryEntry<T>[]>([]);
  const future = shallowRef<HistoryEntry<T>[]>([]);

  const canUndo = computed(() => past.value.length > 0);
  const canRedo = computed(() => future.value.length > 0);

  function isNoOp(before: T, after: T): boolean {
    return equals?.(before, after) ?? false;
  }

  /** Adds a step, or extends the last one if it was recorded under `key`. */
  function record(before: T, after: T, key?: string): void {
    const top = past.value.at(-1);

    if (key !== undefined && top?.key === key) {
      const rest = past.value.slice(0, -1);

      // Nudged back to where the run started: nothing left to undo.
      past.value = isNoOp(top.before, after)
        ? rest
        : [...rest, {...top, after}];
      future.value = [];
      return;
    }

    if (isNoOp(before, after)) {
      return;
    }

    past.value = [...past.value, {before, after, key}].slice(-limit);
    future.value = [];
  }

  /** Steps back, returning the state to restore. */
  function undo(): T | null {
    const top = past.value.at(-1);

    if (!top) {
      return null;
    }

    past.value = past.value.slice(0, -1);
    future.value = [...future.value, {...top, key: undefined}];

    return top.before;
  }

  /** Steps forward again, returning the state to restore. */
  function redo(): T | null {
    const top = future.value.at(-1);

    if (!top) {
      return null;
    }

    future.value = future.value.slice(0, -1);
    past.value = [...past.value, top];

    return top.after;
  }

  /** Ends a keyed run, so the next record under that key is its own step. */
  function seal(): void {
    const top = past.value.at(-1);

    if (top?.key !== undefined) {
      past.value = [...past.value.slice(0, -1), {...top, key: undefined}];
    }
  }

  function clear(): void {
    past.value = [];
    future.value = [];
  }

  return {canUndo, canRedo, record, undo, redo, seal, clear};
}
