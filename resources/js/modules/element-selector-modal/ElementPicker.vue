<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {watch, computed} from 'vue';
  import ElementSources from '@/modules/elements/ElementSources.vue';
  import ElementIndex from '@/modules/elements/index/components/ElementIndex.vue';
  import type {ContentIndexData} from '@/modules/elements/index/composables/useContentIndexData';
  import type {SourceItem} from '@/modules/elements/types/sources';
  import {
    isModifiedClick,
    useModalElementIndex,
    type FolderCrumb,
    type SelectedElement,
  } from './useModalElementIndex';

  const props = defineProps<{
    /** Bare action path; `actionClient` expands it against the CP trigger. */
    action: string;
    initial: ContentIndexData;
    params: Record<string, unknown>;
    disabledElementIds?: number[];
    /** Picks folders rather than elements; see `useModalElementIndex`. */
    selectFolders?: boolean;
    disabledFolderIds?: number[];
  }>();

  const emit = defineEmits<{
    (event: 'selection-change', elements: SelectedElement[]): void;
    (event: 'choose', elements: SelectedElement[]): void;
    /** The source being viewed, so the chrome can act on the current folder. */
    (event: 'source-change', source: SourceItem | null): void;
    /** The trail from the volume root to the folder on screen. */
    (event: 'folder-path-change', path: FolderCrumb[]): void;
  }>();

  const index = useModalElementIndex({
    action: props.action,
    initial: props.initial,
    params: props.params,
    disabledElementIds: () => props.disabledElementIds ?? [],
    selectFolders: props.selectFolders,
    disabledFolderIds: () => props.disabledFolderIds ?? [],
  });

  const {selectedElements, hasSelection, clearSelection, folderBreadcrumbs} =
    index;
  const {elementIndex} = index.view;

  // Only once inside a subfolder: at a volume's root, the selected source
  // already says where the modal is.
  const showFolderBreadcrumbs = computed(
    () => folderBreadcrumbs.value.length > 1
  );

  function onCrumbClick(folderId: number, event: MouseEvent) {
    if (isModifiedClick(event)) {
      return;
    }

    event.preventDefault();
    index.openFolder(folderId);
  }

  function onIncludeSubfoldersChange(event: Event) {
    index.setIncludeSubfolders(
      Boolean((event.target as {checked?: boolean} | null)?.checked)
    );
  }

  // Reached only by a double-click the item behavior let through, after it
  // selected the double-clicked item; folders open instead.
  function onDblClick() {
    emit('choose', selectedElements.value);
  }

  // The modal's Select button and its enabled state live outside Vue, so the
  // selection is pushed out rather than read in.
  watch(selectedElements, (elements) => emit('selection-change', elements));

  // Published rather than read, for the same reason the selection is: what the
  // footer can offer depends on where the index is — uploading needs the folder
  // currently on screen. `immediate` because the first source arrives with the
  // payload, before anything switches.
  watch(
    () => elementIndex.source,
    (source) => emit('source-change', source ?? null),
    {immediate: true}
  );

  watch(folderBreadcrumbs, (path) => emit('folder-path-change', path), {
    immediate: true,
  });

  defineExpose({
    selectedElements,
    hasSelection,
    clearSelection,
    /** Re-requests the current query and selects an uploaded element. */
    refresh: (selectId?: number) => index.refresh({selectId}),
  });

  const showSidebar = computed(() => elementIndex.sources.length > 1);
</script>

<template>
  <div
    :class="{
      'modal-element-index': true,
      'modal-element-index--sidebar': showSidebar,
      'modal-element-index--folders': selectFolders,
    }"
  >
    <nav
      v-if="showSidebar"
      class="modal-element-index__sidebar"
      :aria-label="t('Sources')"
    >
      <ElementSources
        :sources="elementIndex.sources"
        :active-source="index.activeSourceKey.value"
        @select="index.changeSource"
      />
    </nav>

    <div class="modal-element-index__main">
      <ElementIndex
        :view="index.view"
        :item-behavior="index.itemBehavior"
        contained
        :show-sites="!selectFolders"
        @site-change="index.changeSite"
        @dblclick="onDblClick"
      >
        <template v-if="index.canSearchSubfolders.value" #search-options>
          <craft-checkbox
            class="modal-element-index__subfolders mt-sm"
            .checked="index.includeSubfolders.value"
            @model-value-changed="onIncludeSubfoldersChange"
          >
            <label slot="label">{{ t('Search in subfolders') }}</label>
          </craft-checkbox>
        </template>
        <template v-if="showFolderBreadcrumbs" #navbar>
          <div
            class="folder-breadcrumbs -mx-(--cp-container-padding) px-(--cp-container-padding) py-sm border-b border-b-(--c-color-neutral-border-quiet)"
          >
            <craft-breadcrumbs :label="t('Breadcrumbs')" class="text-xs">
              <craft-breadcrumb-item
                v-for="(crumb, idx) in folderBreadcrumbs"
                :key="crumb.folderId"
              >
                <a
                  v-if="idx < folderBreadcrumbs.length - 1 && crumb.url"
                  :href="crumb.url"
                  class="text-box-trim"
                  @click="onCrumbClick(crumb.folderId, $event)"
                  >{{ crumb.label }}</a
                >
                <span v-else class="text-box-trim">{{ crumb.label }}</span>
              </craft-breadcrumb-item>
            </craft-breadcrumbs>
          </div>
        </template>
      </ElementIndex>
    </div>
  </div>
</template>

<style lang="scss" scoped>
  .modal-element-index {
    display: grid;
    height: 100%;
  }

  .modal-element-index--sidebar {
    grid-template-columns: clamp(12rem, 15%, 14rem) 1fr;
  }

  // Only beside the sidebar: without one, the pane meets the modal's own edge.
  .modal-element-index--sidebar .modal-element-index__main {
    border-inline-start: 1px solid var(--c-color-neutral-border-quiet);
  }

  .modal-element-index__sidebar {
    padding: var(--c-spacing-md);
    // Long source lists scroll on their own rather than stretching the modal.
    min-height: 0;
    overflow-y: auto;
  }

  .modal-element-index__main {
    // The index lays itself out by `cp-content-view` container queries, as it
    // would on a page; without this, it falls back to its narrowest layout.
    container: cp-content-view / inline-size;
    background: var(--c-surface-overlay);

    // Grid items don't shrink below their content by default, so a wide table
    // widens the column and a long one grows the row — pushing the pane out of
    // the modal. Zeroing the minimums hands sizing back to the grid track.
    min-width: 0;
    min-height: 0;

    // Everything past the track is the index's to scroll, not the modal's to
    // spill. Kept here rather than on the container so menus anchored outside
    // the pane aren't clipped.
    display: grid;
    overflow: hidden;
  }

  // Even on both sides of each separator, matching the header's breadcrumbs:
  // the crumb's own gap already sits before it, so the end margin makes up for
  // it.
  .folder-breadcrumbs :deep(craft-breadcrumb-item > [slot='separator']) {
    margin-inline: var(--c-spacing-sm) var(--c-spacing-md);
  }

  // A folder listing has no columns besides the folder's own, so it takes the
  // width the title column would otherwise leave to the checkbox column.
  .modal-element-index--folders :deep(.cp-table-cell--title) {
    width: 100%;
  }

  // The modal has no header of its own, so the toolbar takes its place.
  .modal-element-index__main :deep(.element-index__header) {
    display: flex;
    flex-direction: column;
    justify-content: center;
    box-sizing: border-box;
    min-block-size: var(--c-modal-header-height);
    margin-block-end: 0;
    padding-block: var(--c-spacing-sm);
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }

  .modal-element-index__main
    :deep(.element-index__header:has(.modal-element-index__subfolders)) {
    padding-block: var(--c-spacing-md);
  }

  .modal-element-index__main :deep(.cp-table > thead th) {
    height: auto;
    padding-block: var(--c-spacing-md);
    font-size: var(--c-text-sm);
  }
</style>
