import {readonly, shallowRef, watch, type Ref} from 'vue';

/** Delay showing progress, but hide it immediately when loading finishes. */
export function useDelayedLoading(
  loading: Readonly<Ref<boolean>>,
  delay = 200
) {
  const visible = shallowRef(false);

  watch(
    loading,
    (loading, _, onCleanup) => {
      if (!loading) {
        visible.value = false;

        return;
      }

      const timeout = setTimeout(() => {
        visible.value = true;
      }, delay);
      onCleanup(() => clearTimeout(timeout));
    },
    {immediate: true, flush: 'sync'}
  );

  return readonly(visible);
}
