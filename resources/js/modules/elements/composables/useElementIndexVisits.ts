import {router} from '@inertiajs/vue3';

export type IndexQueryValue =
  | string
  | number
  | boolean
  | null
  | undefined
  | IndexQueryValue[]
  | IndexQueryParams;

export interface IndexQueryParams {
  [key: string]: IndexQueryValue;
}

/**
 * The route an element index lives at. The page owns the actual route helper
 * (e.g. wayfinder's `content.index`) and hands the composables this minimal
 * builder, so they stay agnostic of which index they're driving.
 */
export interface ElementIndexRoute {
  /** Builds a visitable URL for this index with the given query params. */
  url(query?: IndexQueryParams): string;
}

/** Appends the nested element-index query shape to a Wayfinder base URL. */
export function appendIndexQuery(url: string, query: IndexQueryParams): string {
  const search = new URLSearchParams();

  function append(key: string, value: IndexQueryValue): void {
    if (value === null || value === undefined) return;

    if (Array.isArray(value)) {
      value.forEach((item, index) => append(`${key}[${index}]`, item));
      return;
    }

    if (value instanceof Object) {
      Object.entries(value).forEach(([childKey, item]) =>
        append(`${key}[${childKey}]`, item)
      );
      return;
    }

    search.append(key, String(value));
  }

  Object.entries(query).forEach(([key, value]) => append(key, value));
  const serialized = search.toString();
  return serialized ? `${url}?${serialized}` : url;
}

export interface IndexVisitOptions {
  /** Restrict the partial reload to these props. */
  only?: Array<string>;
  /** Replace the current history entry instead of pushing one. */
  replace?: boolean;
  /** Drop the page param, restarting at page 1 (for filter-ish changes). */
  resetPage?: boolean;
}

/**
 * A composable's contribution to the single mount-time "restore persisted
 * state" visit: the query params to merge in and the props to pull back. The
 * page composes every non-null contribution into one visit (see
 * {@link useElementIndexPage}) so the restores can't interrupt each other.
 */
export interface IndexRestore {
  /** Params to merge into the restore visit (e.g. `{viewMode}`, `{columns}`). */
  params: IndexQueryParams;
  /** Props this restore needs re-pulled; unioned across all contributions. */
  only: Array<string>;
}

/**
 * Shared visit plumbing for the element index composables: merging params into
 * the current query and performing state/scroll-preserving Inertia visits
 * against the injected route.
 */
export function createIndexVisitor(route: ElementIndexRoute) {
  /**
   * The current query params, minus any whose keys are being replaced. Arrays
   * and objects serialize as `key[0]`, `key[0][field]`, …, so replacements
   * strip every bracketed form of the key — otherwise stale values accumulate
   * alongside the new ones.
   */
  function currentQuery(replacing: Array<string> = []): Record<string, string> {
    return Object.fromEntries(
      [...new URLSearchParams(window.location.search)].filter(
        ([key]) =>
          !replacing.some((name) => key === name || key.startsWith(`${name}[`))
      )
    );
  }

  /** Visits the index with the given query, as-is. */
  function visit(query: IndexQueryParams, options: IndexVisitOptions = {}) {
    router.visit(route.url(query), {
      only: options.only,
      preserveState: true,
      preserveScroll: true,
      replace: options.replace ?? false,
    });
  }

  /**
   * Merges the given params into the current URL's query (replacing their
   * previous values) and visits the index. `null`/`undefined` values remove
   * the param.
   */
  function merge(params: IndexQueryParams, options: IndexVisitOptions = {}) {
    const replacing = Object.keys(params);

    if (options.resetPage) {
      replacing.push(Craft.pageTrigger ?? 'page');
    }

    const query: IndexQueryParams = currentQuery(replacing);

    for (const [key, value] of Object.entries(params)) {
      if (value !== null && value !== undefined) {
        query[key] = value;
      }
    }

    visit(query, options);
  }

  return {currentQuery, visit, merge};
}

export type IndexVisitor = ReturnType<typeof createIndexVisitor>;
