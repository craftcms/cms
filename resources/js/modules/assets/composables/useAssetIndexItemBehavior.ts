import {router} from '@inertiajs/vue3';
import type {ElementIndexItemBehavior} from '@/modules/elements/types/item-behavior';

interface AssetIndexItem {
  id: string | number;
  isFolder?: boolean;
  folderUrl?: string;
  folderId?: string | number;
  canMoveTo?: boolean;
  previewable?: boolean;
}

type NavigationRouter = Pick<typeof router, 'visit'>;

interface PreviewModalConstructor {
  new (assetId: number): unknown;
  openInstance?: {hide(): void} | null;
}

export function useAssetIndexItemBehavior(
  navigationRouter: NavigationRouter = router
) {
  function folderNavigationUrl(folderUrl: string): string {
    const url = new URL(folderUrl, window.location.origin);
    const current = new URLSearchParams(window.location.search);

    current.delete('source');
    current.delete(Craft.pageTrigger ?? 'page');

    for (const [key, value] of current) {
      if (!url.searchParams.has(key)) {
        url.searchParams.set(key, value);
      }
    }

    return url.href;
  }

  function navigateToFolder(folderUrl: string) {
    navigationRouter.visit(folderNavigationUrl(folderUrl), {
      preserveState: true,
      preserveScroll: true,
    });
  }

  function isFolderRow(
    item: AssetIndexItem
  ): item is AssetIndexItem & {folderUrl: string} {
    return Boolean(item.isFolder && item.folderUrl);
  }

  function rowMoveAttrs(item: AssetIndexItem) {
    const folder = isFolderRow(item);

    return {
      'data-row-id': String(item.id),
      'data-id': folder ? undefined : String(item.id),
      'data-folder-drop-target': folder ? '' : undefined,
      'data-folder-id':
        folder && item.folderId != null ? String(item.folderId) : undefined,
      'data-can-move-to': folder && item.canMoveTo ? '' : undefined,
      'data-is-folder': folder ? '' : undefined,
      'data-movable-item': '',
    };
  }

  function previewAsset(item: AssetIndexItem, event: KeyboardEvent): boolean {
    if (event.key !== ' ' || !event.shiftKey || !item.previewable) {
      return false;
    }

    event.preventDefault();
    event.stopPropagation();

    const PreviewFileModal = Craft.PreviewFileModal as PreviewModalConstructor;
    if (PreviewFileModal.openInstance) {
      PreviewFileModal.openInstance.hide();

      return true;
    }

    const assetId = Number(item.id);
    if (!item.isFolder && Number.isFinite(assetId)) {
      new PreviewFileModal(assetId);
    }

    return true;
  }

  const itemBehavior: ElementIndexItemBehavior<AssetIndexItem> = {
    attrs: rowMoveAttrs,
    onClick(item, event) {
      if (!isFolderRow(item)) {
        return false;
      }

      if (
        event.target instanceof HTMLElement &&
        event.target.closest(
          'a[href], button, input, craft-checkbox, craft-reorder-button'
        )
      ) {
        return true;
      }

      navigateToFolder(item.folderUrl);

      return true;
    },
    onKeydown(item, event) {
      if (previewAsset(item, event)) {
        return true;
      }

      if ((event.key === ' ' || event.key === 'Enter') && isFolderRow(item)) {
        event.preventDefault();
        navigateToFolder(item.folderUrl);

        return true;
      }

      return false;
    },
  };

  return {itemBehavior, folderNavigationUrl};
}
