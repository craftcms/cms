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
    type SelectedElement,
  } from './useModalElementIndex';

  const props = defineProps<{
    /** Bare action path; `actionClient` expands it against the CP trigger. */
    action: string;
    initial: ContentIndexData;
    params: Record<string, unknown>;
    disabledElementIds?: number[];
  }>();

  const emit = defineEmits<{
    (event: 'selection-change', elements: SelectedElement[]): void;
    (event: 'choose', elements: SelectedElement[]): void;
    /** The source being viewed, so the chrome can act on the current folder. */
    (event: 'source-change', source: SourceItem | null): void;
  }>();

  const index = useModalElementIndex({
    action: props.action,
    initial: props.initial,
    params: props.params,
    disabledElementIds: () => props.disabledElementIds ?? [],
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

  // A folder's first click has already opened it, so a double-click on one
  // isn't a choice.
  function onDblClick(event: MouseEvent) {
    if (
      event.target instanceof Element &&
      event.target.closest('[data-is-folder]')
    ) {
      return;
    }

    emit('choose', selectedElements.value);
  }

  // The modal's Select button and its enabled state live outside Vue, so the
  // selection is pushed out rather than read in.
  watch(selectedElements, (elements) => emit('selection-change', elements), {
    deep: true,
  });

  // Published rather than read, for the same reason the selection is: what the
  // footer can offer depends on where the index is — uploading needs the folder
  // currently on screen. `immediate` because the first source arrives with the
  // payload, before anything switches.
  watch(
    () => elementIndex.source,
    (source) => emit('source-change', source ?? null),
    {immediate: true}
  );

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
    }"
  >
    <nav
      v-if="showSidebar"
      class="modal-element-index__sidebar"
      :aria-label="t('Sources')"
    >
      <ElementSources
        :sources="elementIndex.sources"
        :active-source="elementIndex.source?.key"
        @select="index.changeSource"
      />
    </nav>

    <div class="modal-element-index__main">
      <ElementIndex
        :view="index.view"
        :item-behavior="index.itemBehavior"
        contained
        :show-sites="true"
        @site-change="index.changeSite"
        @dblclick="onDblClick"
      >
        <template v-if="showFolderBreadcrumbs" #navbar>
          <div class="border-b border-b-quiet py-sm">
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
    background-color: var(--c-color-neutral-fill-quiet);
    height: 100%;
  }

  .modal-element-index--sidebar {
    grid-template-columns: clamp(12rem, 15%, 14rem) 1fr;
  }

  .modal-element-index__sidebar {
    padding-block: var(--c-spacing-lg);
    padding-inline: var(--c-spacing-sm);
    // Long source lists scroll on their own rather than stretching the modal.
    min-height: 0;
    overflow-y: auto;
  }

  .modal-element-index__main {
    background: var(--c-surface-overlay);
    border-inline-start: 1px solid var(--c-color-neutral-border-quiet);

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
</style>
