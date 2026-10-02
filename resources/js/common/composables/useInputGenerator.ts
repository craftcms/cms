import {ref, watch, type WatchStopHandle} from 'vue';

/**
 * Vue composable that auto-generates a target value from a source value.
 * The onUpdate callback receives the raw source value and is responsible
 * for transforming and assigning it. Replaces legacy BaseInputGenerator
 * and its subclasses.
 *
 * Auto-generation stops when the target is manually changed (via markDirty).
 */
export function useInputGenerator(
  source: () => string,
  onUpdate: (value: string) => void
) {
  const isDirty = ref(false);
  let watcher: WatchStopHandle | null = null;

  function generate() {
    onUpdate(source());
  }

  function start() {
    if (watcher) return;

    watcher = watch(source, () => {
      if (!isDirty.value) {
        generate();
      }
    });
  }

  function stop() {
    watcher?.();
    watcher = null;
  }

  function markDirty() {
    isDirty.value = true;
  }

  function markClean() {
    isDirty.value = false;
  }

  start();

  return {isDirty, stop, start, markDirty, markClean};
}
