import {onBeforeUnmount, onMounted} from 'vue';
import {router} from '@inertiajs/vue3';

/**
 * Bridges `@craftcms/cp`'s framework-agnostic `action:redirect` event into an
 * Inertia SPA visit.
 *
 * `runAction` dispatches a cancelable `action:redirect` event on a redirecting
 * action and otherwise falls back to a full-page `window.location` navigation.
 * Calling this composable intercepts that event, prevents the fallback, and does
 * an Inertia `router.visit()` instead — so it should be invoked once from the
 * app shell (e.g. the layout).
 */
export function useActionRedirect(): void {
  function handleActionRedirect(event: Event): void {
    const url = (event as CustomEvent<{url: string}>).detail?.url;
    if (!url) return;
    event.preventDefault();
    router.visit(url);
  }

  onMounted(() => {
    window.addEventListener('action:redirect', handleActionRedirect);
  });

  onBeforeUnmount(() => {
    window.removeEventListener('action:redirect', handleActionRedirect);
  });
}
