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
   * URL for switching to a source. Columns and sort options are per-source,
   * so sort/page params are dropped and only cross-source state (search,
   * site, status) is kept.
   */
  function sourceUrl(key: string): string {
    const current = currentQuery();
    const query = new URLSearchParams({source: key});

    for (const keep of ['search', 'site', 'status']) {
      if (current[keep]) {
        query.set(keep, current[keep]);
      }
    }

    return `${window.location.pathname}?${query.toString()}`;
  }

  return {apply, sourceUrl};
}
