<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import ElementIndexPage from '@/modules/elements/components/ElementIndexPage.vue';
  import type {ElementIndexRoute} from '@/modules/elements/composables/useElementIndexVisits';
  import {usePage} from '@inertiajs/vue3';
  import {index} from '@routes/cp/assets';
  import Breadcrumbs from '@/common/components/Breadcrumbs.vue';
  import Modal from '@/common/components/Modal.vue';
  import {useFolderNavigation} from '@/modules/elements/composables/useFolderNavigation';
  import {useAssetMoveDrag} from '@/modules/elements/composables/useAssetMoveDrag';
  import {useElementIndexTable} from '@/modules/elements/composables/useElementIndexTable';

  const page = usePage<CraftCms.Cms.Http.ViewModels.AssetIndexViewModel>();

  // Breadcrumb clicks navigate the same way folder rows do, so the current view
  // (mode, columns, sort) carries across when moving up the folder tree.
  const {navigateToFolder} = useFolderNavigation();

  // Keep the current volume/folder segment in the URL so index reloads (sort,
  // filter, pagination) stay in the same folder instead of bouncing to the root.
  const route: ElementIndexRoute = {
    url: (query = {}) =>
      index.url(
        {defaultSource: page.props.defaultSource ?? undefined},
        {query: query as Record<string, string>}
      ),
  };

  /** One step of the volume-root → current-folder chain. */
  interface SourcePathStep {
    uri: string;
    label: string;
    icon?: string | null;
    folderId?: number;
    canMoveTo?: boolean;
  }

  // The folder trail for the breadcrumb bar: the full chain when in a subfolder,
  // otherwise just the volume root (from the source). Every ancestor links to
  // its own folder index; the current folder (last step) is plain text.
  //
  // Ancestor crumbs also double as drag-and-drop move targets (drag a file up to
  // a parent folder), matching the folder rows and sidebar sources. The current
  // folder is skipped — the dragged assets already live there.
  const breadcrumbs = computed(() => {
    const steps: SourcePathStep[] =
      (page.props.defaultSourcePath as SourcePathStep[] | null) ??
      (page.props.source as {defaultSourcePath?: SourcePathStep[]} | null)
        ?.defaultSourcePath ??
      [];

    return steps.map((step, i) => {
      const isCurrent = i === steps.length - 1;

      const attrs: Record<string, string> = {};
      if (!isCurrent && step.canMoveTo && step.folderId != null) {
        attrs['data-folder-drop-target'] = '';
        attrs['data-folder-id'] = String(step.folderId);
        attrs['data-can-move-to'] = '';
      }

      return {
        label: step.label,
        icon: step.icon ?? undefined,
        url: isCurrent
          ? null
          : // `uri` is e.g. `assets/local/general`; the route's defaultSource is
            // the path after `assets/`.
            index.url({
              defaultSource: step.uri.replace(/^assets\/?/, '') || undefined,
            }),
        attrs,
      };
    });
  });

  // Drag-and-drop moving of assets into folders. The selection + refresh live in
  // the shared index pipeline, which we grab from its composable rather than
  // reaching through a component ref.
  const {table, onActionPerformed} = useElementIndexTable();

  const {conflictPrompt, resolveConflictChoice} = useAssetMoveDrag({
    enabled: () => true,
    selectedAssetIds: () => {
      const selection = table.value?.getState().rowSelection ?? {};
      return Object.entries(selection)
        .filter(([id, selected]) => selected && !id.startsWith('folder:'))
        .map(([id]) => Number(id))
        .filter((id) => Number.isFinite(id));
    },
    ensureSelected: (rowId) => {
      table.value?.setRowSelection((prev) => ({...prev, [rowId]: true}));
    },
    onMoved: () => onActionPerformed(),
  });

  useAppLayout({fullWidth: true});
</script>

<template>
  <ElementIndexPage :route="route">
    <template #navbar>
      <Breadcrumbs :items="breadcrumbs" @navigate="navigateToFolder" />
    </template>
    <template #actions>
      <form action="">
        <craft-button icon="upload">
          {{ t('Upload files') }}
        </craft-button>
      </form>
    </template>
  </ElementIndexPage>

  <!-- Filename conflict prompt shown while moving assets into a folder. -->
  <Modal
    :is-active="conflictPrompt !== null"
    width="sm"
    @close="resolveConflictChoice('cancel')"
  >
    <div class="p-lg flex flex-col gap-4">
      <p>
        {{
          t(
            'A file named “{filename}” already exists in the destination folder.',
            {filename: conflictPrompt?.conflict.filename ?? ''}
          )
        }}
      </p>
      <div class="flex gap-2 justify-end">
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
    </div>
  </Modal>
</template>

<style scoped lang="scss"></style>
