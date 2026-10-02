import {onScopeDispose, nextTick} from 'vue';
import {router} from '@inertiajs/vue3';

/** How long the highlight stays up before fading itself out. */
const HIGHLIGHT_MS = 5000;

/**
 * Draws attention to the field a URL fragment points at.
 *
 * Deep links like `settings/general#form-maintenanceMode` land on a long form
 * where the field in question is easy to miss. The browser's own `:target`
 * handling isn't enough here: an Inertia visit swaps the page with `pushState`,
 * which doesn't re-evaluate `:target`, and the field doesn't exist yet at the
 * moment the URL changes.
 *
 * Only fields are highlighted. The shell's own skip links point at `#main` and
 * `#secondary-nav`, and flashing the whole main column when someone uses one
 * would be worse than doing nothing.
 */
export function useFieldHighlight(): void {
  let timer: ReturnType<typeof setTimeout> | undefined;
  let highlighted: HTMLElement | undefined;

  function clear(): void {
    clearTimeout(timer);
    highlighted?.removeAttribute('data-highlighted');
    highlighted = undefined;
  }

  async function highlightTarget(): Promise<void> {
    const id = window.location.hash.slice(1);

    if (!id) {
      return;
    }

    // The page is swapped after the URL changes, so the field isn't in the
    // document yet when a visit settles.
    await nextTick();

    // Fragments are author-supplied and needn't be valid selectors.
    const target = document.getElementById(decodeURIComponent(id));
    const field = target?.closest<HTMLElement>('craft-field');

    if (!field) {
      return;
    }

    clear();
    highlighted = field;
    field.setAttribute('data-highlighted', '');

    field.scrollIntoView({
      block: 'center',
      behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ? 'auto'
        : 'smooth',
    });

    timer = setTimeout(clear, HIGHLIGHT_MS);
  }

  void highlightTarget();

  // `navigate` covers the back/forward buttons and any visit that pushes.
  //
  // It does *not* cover a link to the page you're already on, which is exactly
  // what the maintenance-mode badge is once you're in Settings: Inertia sees
  // the same URL bar the fragment, replaces the history entry instead of
  // pushing one, and skips `navigate` on the way through. `success` fires for
  // that visit either way, and fires after the page has been swapped in.
  const stopListening = [
    router.on('navigate', () => void highlightTarget()),
    router.on('success', () => void highlightTarget()),
  ];

  window.addEventListener('hashchange', highlightTarget);

  onScopeDispose(() => {
    clear();
    stopListening.forEach((stop) => stop());
    window.removeEventListener('hashchange', highlightTarget);
  });
}
