import {actionClient, getActionUrl, t, isHttpError} from '@craftcms/ui';
import {useEventListener} from '@vueuse/core';
import {shallowRef} from 'vue';
import {submitNativeForm} from '@/common/utils/forms';
import {replaceAssetFile} from '@/modules/assets/replaceAssetFile';
import {VolumeFolderSelectorModal} from '@/modules/element-selector-modal/volume-folder-selector-modal';
import {
  openImageEditorDialog,
  type ImageEditorSettings,
} from '@/modules/image-editor/open-image-editor-dialog';
import type {ElementIndexOperations} from '@/modules/elements/index/types/model';

interface FileActionDetail {
  elementIds?: Array<string | number>;
  sources?: string[];
  defaultSource?: string;
  defaultSourcePath?: Array<{folderId?: number; label?: string}>;
  disabledFolderIds?: number[];
  imageEditorRatios?: ImageEditorSettings['imageEditorRatios'];
  allowDegreeFractions?: boolean;
  orientation?: ImageEditorSettings['orientation'];
}

function detail(event: Event): FileActionDetail | null {
  return event instanceof CustomEvent ? (event.detail ?? {}) : null;
}

/** Asset ids from the selection, leaving out any folder rows. */
function assetIds(action: FileActionDetail): number[] {
  return (action.elementIds ?? [])
    .filter((id) => !String(id).startsWith('folder:'))
    .map(Number)
    .filter(Number.isFinite);
}

/**
 * The asset index's file actions — Download, Rename file, Replace file, Copy
 * URL, Copy reference tag, Show in folder, Edit Image, and Move — which the
 * server serializes as client events (see `AssetIndexViewModel`), since their
 * legacy triggers don't run on the Inertia index.
 *
 * `performMove` is the drag-and-drop move, so a moved file's name conflicts
 * go through the same prompt either way.
 */
export function useAssetFileActions(
  index: ElementIndexOperations,
  performMove: (
    targetFolderId: number,
    assetIds: number[],
    folderIds: number[]
  ) => Promise<void>
) {
  function onActionPerformed(): void {
    index.clearSelection();
    void index.refresh();
  }

  const renameAssetId = shallowRef<number | null>(null);
  const renameFilename = shallowRef('');
  const renameFileError = shallowRef<string | null>(null);
  const renamingFile = shallowRef(false);

  function firstAsset(event: Event): number | null {
    const action = detail(event);

    return action ? (assetIds(action)[0] ?? null) : null;
  }

  function rowValue(assetId: number, key: string): unknown {
    return index.findRow(assetId)?.[key];
  }

  function onDownload(event: Event) {
    const action = detail(event);
    const ids = action ? assetIds(action) : [];
    if (!ids.length) return;

    submitNativeForm(getActionUrl('assets/download-asset'), {assetId: ids});
  }

  function onRename(event: Event) {
    const assetId = firstAsset(event);
    if (assetId === null) return;

    const filename = rowValue(assetId, 'assetFilename');
    renameAssetId.value = assetId;
    renameFilename.value = typeof filename === 'string' ? filename : '';
    renameFileError.value = null;
  }

  function closeRenameFile() {
    renameAssetId.value = null;
    renameFilename.value = '';
    renameFileError.value = null;
  }

  async function submitRenameFile() {
    const filename = renameFilename.value.trim();
    if (renameAssetId.value === null || !filename || renamingFile.value) {
      return;
    }

    if (filename === rowValue(renameAssetId.value, 'assetFilename')) {
      closeRenameFile();
      return;
    }

    renamingFile.value = true;
    renameFileError.value = null;
    try {
      const {data} = await actionClient.post<{conflict?: string}>(
        getActionUrl('assets/move-asset'),
        {assetId: renameAssetId.value, filename}
      );
      if (data.conflict) {
        renameFileError.value = data.conflict;
        return;
      }
      closeRenameFile();
      onActionPerformed();
      // Renaming onto an existing file merges the two, which can leave
      // reference tags to update.
      Craft.cp?.runQueue?.();
    } catch (error) {
      const message = isHttpError<{message?: string}>(error)
        ? (error.response?.data?.message ?? t('A server error occurred.'))
        : t('A server error occurred.');
      renameFileError.value = message;
      Craft.cp?.displayError?.(message);
    } finally {
      renamingFile.value = false;
    }
  }

  function onReplace(event: Event) {
    const assetId = firstAsset(event);
    if (assetId === null) return;

    replaceAssetFile(assetId, onActionPerformed);
  }

  function onCopyUrl(event: Event) {
    const assetId = firstAsset(event);
    const url = assetId === null ? null : rowValue(assetId, 'viewUrl');
    if (typeof url !== 'string') return;

    Craft.ui.createCopyTextPrompt({label: t('Copy the URL'), value: url});
  }

  function onCopyReferenceTag(event: Event) {
    const assetId = firstAsset(event);
    if (assetId === null) return;

    Craft.ui.createCopyTextPrompt({
      label: t('Copy the reference tag'),
      value: `{asset:${assetId}}`,
    });
  }

  function onShowInFolder(event: Event) {
    const assetId = firstAsset(event);
    if (assetId === null) return;

    // A full page load, as the asset's own menu does: the action redirects to
    // the asset's folder, searching for its filename, but answers an Inertia
    // visit's XHR with JSON instead.
    // The action URL can already carry a query string (the site).
    const url = new URL(
      getActionUrl('assets/show-in-folder'),
      window.location.origin
    );
    url.searchParams.set('assetId', String(assetId));
    window.location.assign(url.href);
  }

  function onEditImage(event: Event) {
    const action = detail(event);
    const assetId = firstAsset(event);
    if (!action || assetId === null) return;

    const filename = rowValue(assetId, 'assetFilename');
    void openImageEditorDialog(
      {
        assetId,
        filename: typeof filename === 'string' ? filename : '',
        focalPoint:
          (rowValue(
            assetId,
            'focalPoint'
          ) as ImageEditorSettings['focalPoint']) ?? null,
        imageWidth: (rowValue(assetId, 'imageWidth') as number | null) ?? null,
        imageHeight:
          (rowValue(assetId, 'imageHeight') as number | null) ?? null,
        imageEditorRatios: action.imageEditorRatios ?? {},
        allowDegreeFractions: action.allowDegreeFractions ?? false,
        orientation: action.orientation ?? 'ltr',
      },
      onActionPerformed
    );
  }

  function onMove(event: Event) {
    const action = detail(event);
    const ids = action ? assetIds(action) : [];
    if (!action || !ids.length) return;

    const modal = new VolumeFolderSelectorModal({
      sources: action.sources,
      showTitle: true,
      modalTitle: t('Move to'),
      selectBtnLabel: t('Move'),
      disabledFolderIds: action.disabledFolderIds ?? [],
      defaultSource: action.defaultSource,
      defaultSourcePath: action.defaultSourcePath,
      onSelect: async ([targetFolder]) => {
        if (!targetFolder) return;

        // The conflict dialog is modal. Close the non-modal picker first so its
        // own focus trap cannot compete if the move needs a decision.
        modal.hide();

        // It reports its own failures.
        await performMove(Number(targetFolder.folderId), ids, []).catch(
          () => {}
        );
      },
    });
  }

  useEventListener(window, 'assets:download-file', onDownload);
  useEventListener(window, 'assets:rename-file', onRename);
  useEventListener(window, 'assets:replace-file', onReplace);
  useEventListener(window, 'assets:copy-url', onCopyUrl);
  useEventListener(window, 'assets:copy-reference-tag', onCopyReferenceTag);
  useEventListener(window, 'assets:show-in-folder', onShowInFolder);
  useEventListener(window, 'assets:edit-image', onEditImage);
  useEventListener(window, 'assets:move-assets', onMove);

  return {
    renameAssetId,
    renameFilename,
    renameFileError,
    renamingFile,
    closeRenameFile,
    submitRenameFile,
  };
}
