import {onMounted, onUnmounted, ref} from 'vue';
import {router} from '@inertiajs/vue3';

/**
 * Tracks whether the element index is currently refetching its list.
 *
 * Every list refetch — search/status filters, sort, pagination, and source
 * switches — goes through an Inertia GET visit back to the current index path.
 * Rather than thread a separate status through each of those composables, we
 * watch Inertia's global router lifecycle and flip `loading` on for visits
 * whose target pathname matches the page we're on, and off again when any visit
 * finishes. The initial page is server-rendered with data, so this only ever
 * surfaces during in-page refetches.
 */
export function useElementIndexLoading() {
  const loading = ref(false);
  const offHandlers: Array<() => void> = [];

  onMounted(() => {
    // The location still points at the index when a refetch starts; the visit's
    // target pathname matches it for same-page query changes (filters, sort,
    // pagination, source), but not for navigations away (e.g. opening an editor).
    const indexPath = window.location.pathname;

    offHandlers.push(
      router.on('start', (event) => {
        const {visit} = event.detail;
        if (
          !visit.prefetch &&
          visit.method === 'get' &&
          visit.url.pathname === indexPath
        ) {
          loading.value = true;
        }
      })
    );

    // `finish` fires for every visit (success, error, or cancel), so pairing it
    // with `start` can't leave the flag stuck on.
    offHandlers.push(
      router.on('finish', () => {
        loading.value = false;
      })
    );
  });

  onUnmounted(() => {
    offHandlers.forEach((off) => off());
  });

  return {loading};
}
