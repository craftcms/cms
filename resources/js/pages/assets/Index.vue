<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {useEventListener} from '@vueuse/core';
  import ElementIndexPage from '@/modules/elements/components/ElementIndexPage.vue';
  import {
    appendIndexQuery,
    type ElementIndexRoute,
  } from '@/modules/elements/composables/useElementIndexVisits';
  import {usePage} from '@inertiajs/vue3';
  import {index} from '@routes/cp/assets';
  import Breadcrumbs, {
    type BreadcrumbItem,
  } from '@/common/components/Breadcrumbs.vue';
  import Modal from '@/common/components/Modal.vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import {useFolderNavigation} from '@/modules/elements/composables/useFolderNavigation';
  import {useAssetMoveDrag} from '@/modules/elements/composables/useAssetMoveDrag';
  import {useNewSubfolder} from '@/modules/elements/composables/useNewSubfolder';
  import AssetUploadButton from './AssetUploadButton.vue';

  const page = usePage<CraftCms.Cms.Http.ViewModels.AssetIndexViewModel>();

  // Breadcrumb clicks navigate the same way folder rows do, so the current view
  // (mode, columns, sort) carries across when moving up the folder tree.
  const {navigateToFolder} = useFolderNavigation();

  // Keep the current volume/folder segment in the URL so index reloads (sort,
  // filter, pagination) stay in the same folder instead of bouncing to the root.
  const route: ElementIndexRoute = {
    url: (query = {}) =>
      appendIndexQuery(
        index.url({defaultSource: page.props.defaultSource ?? undefined}),
        query
      ),
  };

  // The breadcrumb trail is built server-side (labels, folder links, drop-target
  // attrs, and the current folder's action menu) — see
  // AssetIndexViewModel::breadcrumbs(). We render it as-is.
  const breadcrumbs = computed<BreadcrumbItem[]>(
    () => page.props.breadcrumbs ?? []
  );

  const uploadSource = computed(() => {
    const data = page.props.source?.data as
      | Record<string, boolean | number | string>
      | undefined;

    return {
      canUpload: data?.['can-upload'] === true,
      folderId: data?.['folder-id'] as number | undefined,
      fsType: data?.['fs-type'] as string | undefined,
    };
  });

  // "New subfolder" prompt for the current folder. Its breadcrumb menu item is a
  // server-driven `event` action (AssetIndexViewModel::NEW_SUBFOLDER_EVENT); we
  // listen for that event and open the name prompt, since the modal itself can't
  // be described server-side. On submit the composable creates the folder and
  // refreshes the active index.
  const {
    isOpen: newFolderOpen,
    name: newFolderName,
    submitting: creatingFolder,
    open: openNewFolder,
    close: closeNewFolder,
    submit: createSubfolder,
  } = useNewSubfolder();

  useEventListener(window, 'assets:new-subfolder', (event: Event) => {
    if (!(event instanceof CustomEvent)) {
      return;
    }
    const folderId = event.detail?.folderId;
    if (Object(folderId).constructor === Number) {
      openNewFolder(Number(folderId));
    }
  });

  const {conflictPrompt, resolveConflictChoice} = useAssetMoveDrag();
</script>

<template>
  <ElementIndexPage :route="route">
    <template #navbar>
      <Breadcrumbs :items="breadcrumbs" @navigate="navigateToFolder" />
    </template>
    <template #actions>
      <AssetUploadButton v-bind="uploadSource" />
    </template>
  </ElementIndexPage>

  <!-- Filename conflict prompt shown while moving assets into a folder. -->
  <Modal
    :is-active="conflictPrompt !== null"
    width="sm"
    @close="resolveConflictChoice('cancel')"
  >
    <div class="cp:p-lg cp:flex cp:flex-col cp:gap-4">
      <p>
        {{
          t(
            'A file named “{filename}” already exists in the destination folder.',
            {filename: conflictPrompt?.conflict.filename ?? ''}
          )
        }}
      </p>
      <div class="cp:flex cp:gap-2 cp:justify-end">
        <craft-button @click="resolveConflictChoice('cancel')">
          {{ t('Cancel') }}
        </craft-button>
        <craft-button @click="resolveConflictChoice('keepBoth')">
          {{ t('Keep both') }}
        </craft-button>
        <craft-button
          variant="danger"
          @click="resolveConflictChoice('replace')"
        >
          {{ t('Replace') }}
        </craft-button>
      </div>
    </div>
  </Modal>

  <!-- New subfolder prompt, opened from the current folder's breadcrumb menu. -->
  <Modal :is-active="newFolderOpen" width="sm" @close="closeNewFolder">
    <form
      class="cp:p-lg cp:flex cp:flex-col cp:gap-4"
      @submit.prevent="createSubfolder"
    >
      <label class="cp:flex cp:flex-col cp:gap-2">
        <span class="cp:font-medium">{{ t('Folder name') }}</span>
        <CraftInput
          v-model="newFolderName"
          :label="t('Folder name')"
          label-sr-only
          autofocus
        />
      </label>
      <div class="cp:flex cp:gap-2 cp:justify-end">
        <craft-button type="button" @click="closeNewFolder">
          {{ t('Cancel') }}
        </craft-button>
        <craft-button
          type="submit"
          variant="primary"
          :disabled="!newFolderName.trim() || creatingFolder"
        >
          {{ t('Create') }}
        </craft-button>
      </div>
    </form>
  </Modal>
</template>

<style scoped lang="scss"></style>
