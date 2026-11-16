import {router} from '@inertiajs/vue3';
import {readonly, shallowRef, type Ref} from 'vue';

const pendingUrl = shallowRef<string | null>(null);
let listening = false;

/**
 * The URL of the page an Inertia visit is on its way to, from the moment it's
 * made until the page arrives or the visit fails. Things that follow the page,
 * such as the nav's selection, can follow this instead, so a click shows up
 * straight away rather than once the server has responded.
 *
 * Only visits to another page count. Partial reloads, prefetches, and
 * background visits leave the page where it is.
 */
export function usePendingVisitUrl(): Readonly<Ref<string | null>> {
  if (!listening) {
    listening = true;
    listen();
  }

  return readonly(pendingUrl);
}

function listen(): void {
  router.on('before', (event) => {
    const visit = event.detail.visit;

    if (
      visit.prefetch ||
      visit.async ||
      visit.method !== 'get' ||
      visit.only.length > 0 ||
      visit.except.length > 0
    ) {
      return;
    }

    // Once every listener has had its say, as one could still cancel it.
    queueMicrotask(() => {
      if (!event.defaultPrevented) {
        pendingUrl.value = visit.url.href;
      }
    });
  });

  const clear = () => {
    pendingUrl.value = null;
  };

  router.on('navigate', clear);
  router.on('httpException', clear);
  router.on('networkError', clear);

  // A canceled or interrupted visit, unless another one has taken its place.
  router.on('finish', (event) => {
    const visit = event.detail.visit;

    if (!visit.completed && pendingUrl.value === visit.url.href) {
      clear();
    }
  });
}
