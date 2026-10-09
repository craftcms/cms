import type {UrlMethodPair} from '@inertiajs/core';
import {router, usePage} from '@inertiajs/vue3';

export interface InertiaReorderOptions {
  /** Where the new order is posted. */
  url: string | UrlMethodPair;
  /** The page prop holding the rows. It's reordered optimistically. */
  prop: string;
  /** The row field the posted ids come from. */
  key?: string;
  /** The param the ids are posted under. */
  param?: string;
}

/**
 * Returns an `AdminTable` `reorder` handler that moves the row in its page
 * prop straight away and posts the new id order. Inertia restores the prop if
 * the save fails.
 */
export function useInertiaReorder({
  url,
  prop,
  key = 'id',
  param = 'ids',
}: InertiaReorderOptions) {
  const page = usePage();

  return function onReorder(startIndex: number, finishIndex: number) {
    const rows = [
      ...((page.props[prop] as Array<Record<string, unknown>> | undefined) ??
        []),
    ];
    const [moved] = rows.splice(startIndex, 1);
    if (moved === undefined) {
      return;
    }
    rows.splice(finishIndex, 0, moved);

    router.visit(url, {
      method: typeof url === 'string' ? 'post' : url.method,
      data: {[param]: rows.map((row) => row[key]) as string[]},
      optimistic: () => ({[prop]: rows}),
      preserveScroll: true,
      preserveState: true,
    });
  };
}
