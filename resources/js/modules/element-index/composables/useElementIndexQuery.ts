import {router} from '@inertiajs/vue3';

/**
 * URL-query-driven state changes for an element index. The URL is the single
 * source of truth: every change merges into the current query string and
 * triggers an Inertia visit on the current path.
 */
export function useElementIndexQuery() {
  const pageParam = Craft.pageTrigger ?? 'page';

  function currentQuery(): Record<string, string> {
    return Object.fromEntries(new URLSearchParams(window.location.search));
  }

  /**
   * Merge params into the current query and partially reload. Passing a
   * null/empty value removes the param. Filter changes reset pagination.
   */
  function apply(params: Record<string, string | null>, only: Array<string>) {
    const query: Record<string, string> = currentQuery();

    for (const [key, value] of Object.entries(params)) {
      if (value === null || value === '') {
        delete query[key];
      } else {
        query[key] = value;
      }
    }

    delete query[pageParam];

    router.get(window.location.pathname, query, {
      only,
      preserveState: true,
      preserveScroll: true,
    });
  }

  /**
   * Switch sources. Columns and sort options are per-source, so this is a
   * full visit that keeps only cross-source state (search, site, status).
   */
  function selectSource(key: string) {
    const current = currentQuery();
    const query: Record<string, string> = {source: key};

    for (const keep of ['search', 'site', 'status']) {
      if (current[keep]) {
        query[keep] = current[keep];
      }
    }

    router.get(window.location.pathname, query, {
      preserveScroll: true,
      preserveState: true,
    });
  }

  return {apply, selectSource};
}
