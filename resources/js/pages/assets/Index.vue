<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, type ComponentPublicInstance, useTemplateRef} from 'vue';
  import ElementIndexPage from '@/modules/elements/components/ElementIndexPage.vue';
  import {router, usePage} from '@inertiajs/vue3';
  import {index} from '@routes/cp/assets';
  import Breadcrumbs, {
    type BreadcrumbItem,
  } from '@/common/components/Breadcrumbs.vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import {useAssetMoveDrag} from '@/modules/assets/composables/useAssetMoveDrag';
  import AssetUploadButton from './AssetUploadButton.vue';
  import {useAssetUploadRefresh} from '@/modules/uploader/useAssetUploadRefresh';
  import {useAssetFolderActions} from '@/modules/assets/composables/useAssetFolderActions';
  import {useAssetIndexItemBehavior} from '@/modules/assets/composables/useAssetIndexItemBehavior';
  import {
    appendIndexQuery,
    type ElementIndexRoute,
  } from '@/modules/elements/composables/useElementIndexVisits';

  const page = usePage<CraftCms.Cms.Http.ViewModels.AssetIndexViewModel>();
  const dropZone = document.body;
  const newFolderNameInput =
    useTemplateRef<ComponentPublicInstance>('newFolderNameInput');

  const {itemBehavior, folderNavigationUrl} = useAssetIndexItemBehavior();

  const route: ElementIndexRoute = {
    url: (query = {}) =>
      appendIndexQuery(
        index.url({defaultSource: page.props.defaultSource ?? undefined}),
        query
      ),
  };
  const sourceHref = index.url();

  function setIncludeSubfolders(event: Event) {
    const includeSubfolders = Boolean(
      (event.target as {checked?: boolean} | null)?.checked
    );
    const url = new URL(window.location.href);
    url.searchParams.delete(Craft.pageTrigger ?? 'page');
    if (includeSubfolders) {
      url.searchParams.set('includeSubfolders', '1');
    } else {
      url.searchParams.delete('includeSubfolders');
    }
    router.visit(url.href, {preserveState: true, preserveScroll: true});
  }

  // Preserve the current view query in each real href while leaving CpLink in
  // charge of the Inertia visit.
  const breadcrumbs = computed<BreadcrumbItem[]>(() =>
    (page.props.breadcrumbs ?? []).map((breadcrumb) => ({
      ...breadcrumb,
      href: breadcrumb.href
        ? folderNavigationUrl(breadcrumb.href)
        : breadcrumb.href,
    }))
  );

  const uploadSource = computed(() => {
    const data = page.props.source?.data as
      | Record<string, boolean | number | string>
      | undefined;

    return {
      canUpload: data?.['can-upload'] === true,
      folderId: data?.['folder-id'] as number | undefined,
    };
  });

  const uploadDestination = computed(() => ({
    folderId: uploadSource.value.folderId!,
    url: index.url(
      {defaultSource: page.props.defaultSource ?? undefined},
      {
        query: {source: page.props.source?.key},
      }
    ),
    label:
      breadcrumbs.value.map((crumb) => crumb.label).join(' / ') ||
      String(page.props.source?.label ?? t('Assets')),
  }));

  useAssetUploadRefresh(() => uploadSource.value.folderId);

  function focusNewFolderName() {
    newFolderNameInput.value?.$el?.focus();
  }

  const {
    conflictPrompt,
    resolveConflictChoice,
    folderConflictPrompt: dragFolderConflictPrompt,
    resolveFolderConflictChoice: resolveDragFolderConflictChoice,
  } = useAssetMoveDrag();

  const {
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
    conflictPrompt: folderConflictPrompt,
    resolveConflictChoice: resolveFolderConflictChoice,
  } = useAssetFolderActions();
</script>

<template>
  <ElementIndexPage
    :route="route"
    :source-href="sourceHref"
    :item-behavior="itemBehavior"
    :filter-params="{
      includeSubfolders: page.props.includeSubfolders ? 1 : undefined,
    }"
  >
    <template #search-options>
      <craft-checkbox
        v-if="page.props.search && page.props.canSearchSubfolders"
        class="mt-2"
        .checked="page.props.includeSubfolders"
        @model-value-changed="setIncludeSubfolders"
      >
        <label slot="label">{{ t('Search in subfolders') }}</label>
      </craft-checkbox>
    </template>
    <template #navbar>
      <Breadcrumbs :items="breadcrumbs" />
    </template>
    <template #actions>
      <AssetUploadButton
        v-bind="uploadSource"
        :destination="uploadDestination"
        :drop-zone="dropZone"
      />
    </template>
  </ElementIndexPage>

  <!-- Filename conflict prompt shown while moving assets into a folder. -->
  <craft-dialog
    .opened="conflictPrompt !== null"
    :label="t('File already exists')"
    @craft-before-hide="resolveConflictChoice('cancel')"
  >
    <p>
      {{
        t(
          'A file named “{filename}” already exists in the destination folder.',
          {filename: conflictPrompt?.conflict.filename ?? ''}
        )
      }}
    </p>
    <div slot="footer" class="flex gap-2 justify-end">
      <craft-button @click="resolveConflictChoice('cancel')">
        {{ t('Cancel') }}
      </craft-button>
      <craft-button @click="resolveConflictChoice('keepBoth')">
        {{ t('Keep both') }}
      </craft-button>
      <craft-button variant="danger" @click="resolveConflictChoice('replace')">
        {{ t('Replace') }}
      </craft-button>
    </div>
  </craft-dialog>

  <craft-dialog
    .opened="dragFolderConflictPrompt !== null"
    :label="t('Folder already exists')"
    @craft-before-hide="resolveDragFolderConflictChoice('cancel')"
  >
    <p>{{ dragFolderConflictPrompt?.message }}</p>
    <div slot="footer" class="flex gap-2 justify-end">
      <craft-button @click="resolveDragFolderConflictChoice('cancel')">
        {{ t('Cancel') }}
      </craft-button>
      <craft-button @click="resolveDragFolderConflictChoice('merge')">
        {{ t('Merge') }}
      </craft-button>
      <craft-button
        variant="danger"
        @click="resolveDragFolderConflictChoice('replace')"
      >
        {{ t('Replace') }}
      </craft-button>
    </div>
  </craft-dialog>

  <!-- New subfolder prompt, opened from the current folder's breadcrumb menu. -->
  <craft-dialog
    .opened="newFolderOpen"
    :label="t('New subfolder')"
    @craft-before-hide="closeNewFolder"
    @craft-after-show="focusNewFolderName"
  >
    <form class="flex flex-col gap-4" @submit.prevent="createSubfolder">
      <CraftInput
        ref="newFolderNameInput"
        v-model="newFolderName"
        :label="t('Folder name')"
        :error="newFolderError"
        :aria-invalid="newFolderError ? 'true' : 'false'"
        autofocus
      />
    </form>
    <div slot="footer" class="flex gap-2 justify-end">
      <craft-button type="button" @click="closeNewFolder">
        {{ t('Cancel') }}
      </craft-button>
      <craft-button
        variant="primary"
        :disabled="!newFolderName.trim() || creatingFolder"
        @click="createSubfolder"
      >
        {{ t('Create') }}
      </craft-button>
    </div>
  </craft-dialog>

  <craft-dialog
    .opened="renameFolderId !== null"
    :label="t('Rename folder')"
    @craft-before-hide="closeRename"
  >
    <form class="flex flex-col gap-4" @submit.prevent="submitRename">
      <CraftInput
        v-model="renameName"
        :label="t('Folder name')"
        :error="renameError"
        :aria-invalid="renameError ? 'true' : 'false'"
        autofocus
      />
    </form>
    <div slot="footer" class="flex gap-2 justify-end">
      <craft-button type="button" @click="closeRename">
        {{ t('Cancel') }}
      </craft-button>
      <craft-button
        variant="primary"
        :disabled="!renameName.trim() || renaming"
        @click="submitRename"
      >
        {{ t('Rename') }}
      </craft-button>
    </div>
  </craft-dialog>

  <craft-dialog
    .opened="folderConflictPrompt !== null"
    :label="t('Folder already exists')"
    @craft-before-hide="resolveFolderConflictChoice('cancel')"
  >
    <p>{{ folderConflictPrompt?.message }}</p>
    <div slot="footer" class="flex gap-2 justify-end">
      <craft-button @click="resolveFolderConflictChoice('cancel')">
        {{ t('Cancel') }}
      </craft-button>
      <craft-button @click="resolveFolderConflictChoice('merge')">
        {{ t('Merge') }}
      </craft-button>
      <craft-button
        variant="danger"
        @click="resolveFolderConflictChoice('replace')"
      >
        {{ t('Replace') }}
      </craft-button>
    </div>
  </craft-dialog>
</template>

<style scoped lang="scss"></style>
