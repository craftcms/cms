<script setup lang="ts">
  /**
   * Confirms a Table node row deletion with a server-built UI, for deletions
   * that need more than a yes/no — where to move a deleted record's data, say.
   * The UI is loaded from `modalUrl`, and its values are posted to
   * `deleteUrl` alongside the row's `id`.
   */
  import {t} from '@craftcms/ui';
  import UiModal from './UiModal.vue';

  defineProps<{
    modalUrl: string;
    deleteUrl: string;
    rowId: string | number;
  }>();

  const emit = defineEmits<{
    (event: 'close'): void;
    (event: 'deleted'): void;
  }>();
</script>

<template>
  <UiModal
    :modal-url="modalUrl"
    :action-url="deleteUrl"
    :params="{id: rowId}"
    :title="t('Delete')"
    :submit-label="t('Delete')"
    width="sm"
    @close="emit('close')"
    @submitted="emit('deleted')"
  />
</template>
