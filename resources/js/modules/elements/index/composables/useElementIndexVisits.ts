import {ref} from 'vue';
import {router} from '@inertiajs/vue3';

import type {
  QueryParams as IndexQueryParams,
  QueryValue as IndexQueryValue,
} from '@/common/types/query';
export type {
  QueryParams as IndexQueryParams,
  QueryValue as IndexQueryValue,
} from '@/common/types/query';

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
  /**
   * Refresh without the index-wide loading state (or progress bar), for
   * changes that show their own progress — e.g. expanding a structure row.
   */
  silent?: boolean;
  /** Called once the visit finishes, whether it succeeded or not. */
  onFinish?: (result: {completed: boolean}) => void;
}

/**
 * A composable's contribution to the single mount-time "restore persisted
 * state" visit: the query params to merge in and the props to pull back. The
 * shared index composes every non-null contribution into one visit (see
 * {@link useElementIndex}) so the restores can't interrupt each other.
 */
export interface IndexRestore {
  /** Params to merge into the restore visit (e.g. `{viewMode}`, `{columns}`). */
  params: IndexQueryParams;
  /** Props this restore needs re-pulled; unioned across all contributions. */
  only: Array<string>;
}

export interface IndexVisitor {
  currentQuery(replacing?: Array<string>): IndexQueryParams;
  visit(query: IndexQueryParams, options?: IndexVisitOptions): void;
  merge(params: IndexQueryParams, options?: IndexVisitOptions): void;
}

/** Shared query replacement and page reset for every index transport. */
export function createElementIndexVisitor(
  readQuery: () => IndexQueryParams,
  visit: IndexVisitor['visit']
): IndexVisitor {
  function currentQuery(replacing: Array<string> = []): IndexQueryParams {
    return Object.fromEntries(
      Object.entries(readQuery()).filter(
        ([key]) =>
          !replacing.some((name) => key === name || key.startsWith(`${name}[`))
      )
    );
  }

  function merge(
    params: IndexQueryParams,
    options: IndexVisitOptions = {}
  ): void {
    const replacing = Object.keys(params);

    if (options.resetPage) {
      replacing.push(window.Craft?.pageTrigger ?? 'page');
    }

    const query = currentQuery(replacing);

    for (const [key, value] of Object.entries(params)) {
      if (value !== null && value !== undefined) {
        query[key] = value;
      }
    }

    visit(query, options);
  }

  return {currentQuery, visit, merge};
}

/** State- and scroll-preserving Inertia visits for a page index. */
export function createIndexVisitor(route: ElementIndexRoute): IndexVisitor {
  return createElementIndexVisitor(
    () => Object.fromEntries(new URLSearchParams(window.location.search)),
    (query, options = {}) => {
      let succeeded = false;

      router.visit(route.url(query), {
        only: options.only ?? [],
        preserveState: true,
        preserveScroll: true,
        replace: options.replace ?? false,
        showProgress: !options.silent,
        onSuccess: () => {
          succeeded = true;
        },
        onFinish: () => options.onFinish?.({completed: succeeded}),
      });
    }
  );
}

/**
 * An {@link IndexVisitor} for an index that isn't a page.
 *
 * The page visitor keeps index state in the URL and re-requests through Inertia.
 * A modal can do neither: it has no URL of its own, and an Inertia visit would
 * replace the page behind it. So the query lives in a ref here, and "visiting"
 * means asking `load` for a fresh payload. It returns true when applied and
 * false when superseded; a failed latest visit restores the confirmed query.
 *
 * The contract is otherwise identical, which is what lets the `useElementIndex*`
 * composables drive a modal index without knowing they are.
 */
export function createDetachedIndexVisitor(
  load: (query: IndexQueryParams, isCurrent: () => boolean) => Promise<boolean>,
  initialQuery: IndexQueryParams = {}
) {
  const query = ref<IndexQueryParams>({...initialQuery});
  let confirmedQuery = query.value;
  let latestRequest = 0;

  async function visit(
    next: IndexQueryParams,
    options: IndexVisitOptions = {}
  ): Promise<void> {
    const requestId = ++latestRequest;

    // Values are kept structured rather than stringified. The page visitor has
    // to flatten everything into a URL; this one POSTs JSON, and `sort` is an
    // object — `String()`-ing it sent the server a literal "[object Object]".
    query.value = Object.fromEntries(
      Object.entries(next).filter(
        ([, value]) => value !== null && value !== undefined
      )
    ) as IndexQueryParams;

    const attemptedQuery = query.value;

    try {
      const applied = await load(
        attemptedQuery,
        () => requestId === latestRequest
      );

      if (applied) {
        confirmedQuery = attemptedQuery;
      }

      options.onFinish?.({completed: applied});
    } catch (error) {
      if (requestId === latestRequest) {
        query.value = confirmedQuery;
      }

      options.onFinish?.({completed: false});
      throw error;
    }
  }

  return {...createElementIndexVisitor(() => query.value, visit), visit, query};
}
