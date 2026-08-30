import {computed, ref} from 'vue';
import {actionClient, getActionUrl, t} from '@craftcms/ui';
import {useElementIndexTable} from '@/modules/elements/composables/useElementIndexTable';
import axios from 'axios';

/**
 * The "New subfolder" flow for the asset index: a name-prompt whose state drives
 * a modal, plus the `assets/create-folder` POST. On success it notifies and
 * refreshes the active element index so the new folder shows up; failures
 * surface the server error.
 */
export function useNewSubfolder() {
  const {onActionPerformed} = useElementIndexTable();
  // The parent folder the new subfolder goes in; non-null means the prompt is
  // open (the caller binds a modal to `isOpen`).
  const parentId = ref<number | null>(null);
  const name = ref('');
  const submitting = ref(false);

  const isOpen = computed(() => parentId.value !== null);

  function open(folderId: number) {
    parentId.value = folderId;
    name.value = '';
  }

  function close() {
    parentId.value = null;
    name.value = '';
  }

  async function submit() {
    const folderName = name.value.trim();
    if (parentId.value === null || !folderName || submitting.value) {
      return;
    }

    submitting.value = true;
    try {
      await actionClient.post(getActionUrl('assets/create-folder'), {
        parentId: parentId.value,
        folderName,
      });
      Craft.cp?.displayNotification?.('notice', t('Folder created.'));
      close();
      onActionPerformed();
    } catch (error) {
      const message = axios.isAxiosError<{message?: string}>(error)
        ? (error.response?.data?.message ?? t('Couldn’t create the folder.'))
        : t('Couldn’t create the folder.');
      Craft.cp?.displayError?.(message);
    } finally {
      submitting.value = false;
    }
  }

  return {parentId, name, submitting, isOpen, open, close, submit};
}
