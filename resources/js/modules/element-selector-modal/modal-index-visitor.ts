import {ref, type Ref} from 'vue';
import type {
  IndexVisitOptions,
  IndexVisitor,
} from '@/modules/elements/composables/useElementIndexVisits';

/**
 * An {@link IndexVisitor} for an index that isn't a page.
 *
 * The page visitor keeps index state in the URL and re-requests through Inertia.
 * A modal can do neither: it has no URL of its own, and an Inertia visit would
 * replace the page behind it. So the query lives in a ref here, and "visiting"
 * means asking `load` for a fresh payload.
 *
 * The contract is otherwise identical, which is what lets the `useElementIndex*`
 * composables drive a modal index without knowing they are.
 */
export function createModalIndexVisitor(
  load: (query: Record<string, string>) => Promise<void>,
  initialQuery: Record<string, string> = {}
): IndexVisitor & {query: Ref<Record<string, string>>} {
  const query = ref<Record<string, string>>({...initialQuery});

  function currentQuery(replacing: Array<string> = []): Record<string, string> {
    return Object.fromEntries(
      Object.entries(query.value).filter(
        ([key]) =>
          !replacing.some((name) => key === name || key.startsWith(`${name}[`))
      )
    );
  }

  function visit(
    next: Record<string, unknown>,
    _options: IndexVisitOptions = {}
  ): void {
    query.value = Object.fromEntries(
      Object.entries(next)
        .filter(([, value]) => value !== null && value !== undefined)
        .map(([key, value]) => [key, String(value)])
    );

    void load(query.value);
  }

  function merge(
    params: Record<string, unknown>,
    options: IndexVisitOptions = {}
  ): void {
    const replacing = Object.keys(params);

    if (options.resetPage) {
      // Off `window` rather than the bare global: this runs before the legacy
      // bundle has necessarily defined `Craft`, and a bare reference throws.
      replacing.push((window as any).Craft?.pageTrigger ?? 'page');
    }

    const next: Record<string, unknown> = currentQuery(replacing);

    for (const [key, value] of Object.entries(params)) {
      if (value !== null && value !== undefined) {
        next[key] = value;
      }
    }

    visit(next, options);
  }

  return {currentQuery, visit, merge, query};
}
