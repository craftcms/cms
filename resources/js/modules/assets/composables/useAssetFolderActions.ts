import {actionClient, getActionUrl, t} from '@craftcms/ui';
import {useEventListener} from '@vueuse/core';
import {computed, shallowRef} from 'vue';
import {router} from '@inertiajs/vue3';
import {VolumeFolderSelectorModal} from '@/modules/element-selector-modal/volume-folder-selector-modal';
import {
  type FolderConflictResolution,
  moveFolders,
} from '@/modules/assets/assetMover';
import {useElementIndexTable} from '@/modules/elements/composables/useElementIndexTable';
import axios from 'axios';

interface FolderActionDetail {
  elementIds?: Array<string | number>;
  folderId?: number;
  folderIds?: number[];
  label?: string;
  navigate?: boolean;
  redirectUrl?: string;
  sources?: string[];
  defaultSource?: string;
  defaultSourcePath?: Array<{folderId?: number; label?: string}>;
  disabledFolderIds?: number[];
}

interface FolderConflictPrompt {
  message: string;
  resolve: (choice: FolderConflictResolution) => void;
}

function detail(event: Event): FolderActionDetail | null {
  return event instanceof CustomEvent ? (event.detail ?? {}) : null;
}

function folderIds(action: FolderActionDetail): number[] {
  if (action.folderIds?.length) {
    return action.folderIds;
  }

  return (action.elementIds ?? [])
    .map((id) => /^folder:(\d+)$/.exec(String(id))?.[1])
    .filter((id): id is string => id !== undefined)
    .map(Number);
}

export function useAssetFolderActions() {
  const {table, onActionPerformed} = useElementIndexTable();
  const newFolderParentId = shallowRef<number | null>(null);
  const newFolderName = shallowRef('');
  const newFolderError = shallowRef<string | null>(null);
  const creatingFolder = shallowRef(false);
  const renameFolderId = shallowRef<number | null>(null);
  const renameName = shallowRef('');
  const renameError = shallowRef<string | null>(null);
  const renaming = shallowRef(false);
  const navigateAfterRename = shallowRef(false);
  const conflictPrompt = shallowRef<FolderConflictPrompt | null>(null);

  const newFolderOpen = computed(() => newFolderParentId.value !== null);

  function onNewSubfolder(event: Event) {
    const folderId = detail(event)?.folderId;
    if (folderId === undefined) {
      return;
    }

    newFolderParentId.value = folderId;
    newFolderName.value = '';
    newFolderError.value = null;
  }

  function closeNewFolder() {
    newFolderParentId.value = null;
    newFolderName.value = '';
    newFolderError.value = null;
  }

  async function createSubfolder() {
    const folderName = newFolderName.value.trim();
    if (
      newFolderParentId.value === null ||
      !folderName ||
      creatingFolder.value
    ) {
      return;
    }

    creatingFolder.value = true;
    newFolderError.value = null;
    try {
      await actionClient.post(getActionUrl('assets/create-folder'), {
        parentId: newFolderParentId.value,
        folderName,
      });
      Craft.cp?.displayNotification?.('notice', t('Folder created.'));
      closeNewFolder();
      onActionPerformed();
    } catch (error) {
      const message = axios.isAxiosError<{message?: string}>(error)
        ? (error.response?.data?.message ?? t('Couldn’t create the folder.'))
        : t('Couldn’t create the folder.');
      newFolderError.value = message;
      Craft.cp?.displayError?.(message);
    } finally {
      creatingFolder.value = false;
    }
  }

  function onRename(event: Event) {
    const action = detail(event);
    if (!action) return;

    const [folderId] = folderIds(action);
    if (!folderId) return;

    renameFolderId.value = folderId;
    renameName.value =
      action.label ??
      table.value?.getRow(`folder:${folderId}`)?.original?.folderName ??
      '';
    renameError.value = null;
    navigateAfterRename.value = action.navigate ?? false;
  }

  function closeRename() {
    renameFolderId.value = null;
    renameName.value = '';
    renameError.value = null;
    navigateAfterRename.value = false;
  }

  async function submitRename() {
    if (!renameFolderId.value || !renameName.value.trim()) return;

    renaming.value = true;
    renameError.value = null;
    try {
      const navigate = navigateAfterRename.value;
      const {data} = await actionClient.post<{folderUrl?: string}>(
        getActionUrl('assets/rename-folder'),
        {
          folderId: renameFolderId.value,
          newName: renameName.value.trim(),
        }
      );
      closeRename();
      Craft.cp?.displayNotice?.(t('Folder renamed.'));
      if (navigate && data.folderUrl) {
        router.visit(data.folderUrl);
      } else {
        onActionPerformed();
      }
    } catch (error) {
      const message = axios.isAxiosError<{message?: string}>(error)
        ? (error.response?.data?.message ?? t('Couldn’t rename the folder.'))
        : t('Couldn’t rename the folder.');
      renameError.value = message;
      Craft.cp?.displayError?.(message);
    } finally {
      renaming.value = false;
    }
  }

  async function onDelete(event: Event) {
    const action = detail(event);
    if (!action) return;

    const ids = folderIds(action);
    const label =
      action.label ??
      (ids.length === 1
        ? table.value?.getRow(`folder:${ids[0]}`)?.original?.folderName
        : null) ??
      t('Untitled');
    if (
      !ids.length ||
      !confirm(
        ids.length === 1
          ? t('Really delete folder “{folder}”?', {
              folder: label,
            })
          : t('Really delete the selected folders?')
      )
    ) {
      return;
    }

    try {
      for (const folderId of ids) {
        await actionClient.post(getActionUrl('assets/delete-folder'), {
          folderId,
        });
      }
      Craft.cp?.displayNotice?.(t('Folder deleted.'));
      if (action.redirectUrl) {
        router.visit(action.redirectUrl);
      } else {
        onActionPerformed();
      }
    } catch (error: any) {
      Craft.cp?.displayError?.(error?.response?.data?.message);
    }
  }

  function resolveConflict(message: string): Promise<FolderConflictResolution> {
    return new Promise((resolve) => {
      conflictPrompt.value = {message, resolve};
    });
  }

  function resolveConflictChoice(choice: FolderConflictResolution) {
    conflictPrompt.value?.resolve(choice);
    conflictPrompt.value = null;
  }

  function onMove(event: Event) {
    const action = detail(event);
    if (!action) return;

    const ids = folderIds(action);
    if (!ids.length) return;

    const modal = new VolumeFolderSelectorModal({
      sources: action.sources,
      showTitle: true,
      modalTitle: t('Move to'),
      selectBtnLabel: t('Move'),
      disabledFolderIds: [
        ...new Set([...ids, ...(action.disabledFolderIds ?? [])]),
      ],
      defaultSource: action.defaultSource,
      defaultSourcePath: action.defaultSourcePath,
      onSelect: async ([targetFolder]) => {
        if (!targetFolder) return;

        // The conflict dialog is modal. Close the non-modal picker first so its
        // own focus trap cannot compete if the move needs a decision.
        modal.hide();

        try {
          const result = await moveFolders(
            ids,
            Number(targetFolder.folderId),
            resolveConflict
          );
          if (result.moved > 0) {
            Craft.cp?.displayNotice?.(
              t('{num, plural, =1{Item} other{Items}} moved.', {
                num: result.moved,
              })
            );
            if (action.redirectUrl) {
              router.visit(action.redirectUrl);
            } else {
              onActionPerformed();
            }
          }
        } catch (error: any) {
          Craft.cp?.displayError?.(error?.response?.data?.message);
        }
      },
    });
  }

  useEventListener(window, 'assets:new-subfolder', onNewSubfolder);
  useEventListener(window, 'assets:rename-folders', onRename);
  useEventListener(window, 'assets:move-folders', onMove);
  useEventListener(window, 'assets:delete-folders', onDelete);

  return {
    newFolderOpen,
    newFolderName,
    newFolderError,
    creatingFolder,
    closeNewFolder,
    createSubfolder,
    renameFolderId,
    renameName,
    renameError,
    renaming,
    closeRename,
    submitRename,
    conflictPrompt,
    resolveConflictChoice,
  };
}
