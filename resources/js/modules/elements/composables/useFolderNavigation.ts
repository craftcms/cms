import {router} from '@inertiajs/vue3';

type FolderNavigationRouter = Pick<typeof router, 'visit'>;

/**
 * Navigation into an asset folder shown inline in the index listing.
 *
 * A folder row is just a link to that folder's own index URL, which the server
 * provides on the row as `folderUrl`. Visiting it carries the current view
 * query (view mode, columns, sort) across so the folder opens in the same view,
 * drops the `source` query param (the folder's path is now the source), and
 * restarts paging.
 */
export function useFolderNavigation(
  navigationRouter: FolderNavigationRouter = router
) {
  function navigateToFolder(folderUrl: string) {
    const url = new URL(folderUrl, window.location.origin);

    const current = new URLSearchParams(window.location.search);
    // The folder path is the source now; the page restarts in the new folder.
    current.delete('source');
    current.delete(Craft.pageTrigger ?? 'page');

    for (const [key, value] of current) {
      if (!url.searchParams.has(key)) {
        url.searchParams.set(key, value);
      }
    }

    // Mirror the source-switch visit in ElementSources: keep the page component
    // and scroll position, swap in the new folder's data.
    navigationRouter.visit(url.href, {
      preserveState: true,
      preserveScroll: true,
    });
  }

  /** Whether a row/card represents a navigable folder. */
  interface FolderRow {
    id?: string | number;
    isFolder?: boolean;
    folderUrl?: string;
    folderId?: string | number;
    canMoveTo?: boolean;
  }

  function isFolderRow(row: FolderRow): row is FolderRow & {folderUrl: string} {
    return Boolean(row.isFolder && row.folderUrl);
  }

  /**
   * data-attributes wiring a row into asset-move drag-and-drop: asset rows are
   * draggable (`data-movable-asset` + `data-id`), folder rows are move targets
   * (`data-folder-drop-target` + `data-folder-id` + `data-can-move-to`). Inert
   * unless an asset-move drag is set up on the page (asset index only).
   */
  function rowMoveAttrs(row: FolderRow) {
    const folder = isFolderRow(row);

    return {
      'data-id': String(row.id),
      'data-folder-drop-target': folder ? '' : undefined,
      'data-folder-id':
        folder && row.folderId != null ? String(row.folderId) : undefined,
      'data-can-move-to': folder && row.canMoveTo ? '' : undefined,
      'data-movable-asset': folder ? undefined : '',
    };
  }

  return {navigateToFolder, isFolderRow, rowMoveAttrs};
}
