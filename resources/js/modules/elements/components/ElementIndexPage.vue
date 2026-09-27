<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import BaseElementIndex from '@/modules/elements/components/BaseElementIndex.vue';
  import DataTable from '@/modules/elements/components/DataTable.vue';
  import ElementCards from '@/modules/elements/components/ElementCards.vue';
  import ElementIndexToolbar from '@/modules/elements/components/ElementIndexToolbar.vue';
  import {useElementIndexPage} from '@/modules/elements/composables/useElementIndexPage';
  import {useElementQuickEdit} from '@/modules/elements/composables/useElementQuickEdit';
  import {
    appendIndexQuery,
    type ElementIndexRoute,
  } from '@/modules/elements/composables/useElementIndexVisits';
  import {TableSpacing} from '@/common/types';
  import ElementThumbs from '@/modules/elements/components/ElementThumbs.vue';
  import {ref} from 'vue';
  import CustomizeSourcesModal from '@/modules/elements/components/customize-sources/CustomizeSourcesModal.vue';
  import type {ElementIndexItemBehavior} from '@/modules/elements/types/item-behavior';
  import type {IndexQueryParams} from '@/modules/elements/composables/useElementIndexVisits';
  import {useNavItemAction} from '@/common/composables/useNavItemActions';
  import CpContainer from '@/common/components/CpContainer.vue';
  import useCraftData from '@/common/composables/useCraftData';
  import {router} from '@inertiajs/vue3';

  const props = defineProps<{
    /** The page's index route — the one per-page piece of the pipeline. */
    route: ElementIndexRoute;
    /** Canonical URL used when switching sources, when it differs from route. */
    sourceHref?: string;
    /** Overrides the pinned first column (defaults to the element's title). */
    pinnedColumn?: {key: string; label: string};
    /** Type-specific item attributes and interaction handlers. */
    itemBehavior?: ElementIndexItemBehavior;
    /** Additional type-specific params included with filter submissions. */
    filterParams?: IndexQueryParams;
    /**
     * Offers Customize Sources from the nav item this index lives under. Opt-in
     * rather than automatic, since not every index that uses this page should
     * offer it.
     */
    customizableSources?: boolean;
  }>();

  const page = useElementIndexPage({
    route: props.route,
    pinnedColumn: props.pinnedColumn,
    filterParams: () => props.filterParams ?? {},
  });

  // Double-click an element to edit it in a slideout.
  const quickEdit = useElementQuickEdit();

  const {
    elementIndex,
    elementTable,
    viewState,
    conditions,
    filters,
    columnOptions,
    tableColumns,
    reorder,
    sortField,
    sortDirection,
    mode,
    structureView,
    toggleStructure,
    canReorderStructure,
    canMoveRow,
    moveStructureRow,
    loading,
    visibleViewModes,
    onActionPerformed,
  } = page;

  const customizeSourcesActive = ref(false);

  const {currentUser, allowAdminChanges} = useCraftData();

  // The sources are edited from the nav now rather than from a sidebar on the
  // page, so the page lends the nav the gear that opens the editor. Only for
  // admins, where admin changes are allowed — the server refuses anyone else.
  if (
    props.customizableSources &&
    currentUser.value?.admin &&
    allowAdminChanges.value
  ) {
    useNavItemAction(() => props.route.url(), {
      label: t('Customize sources'),
      icon: 'gear',
      onClick: () => (customizeSourcesActive.value = true),
    });
  }

  /**
   * Starts the index over after the sources are saved — everything it shows,
   * and the nav's list of them, comes from them — landing on the source the
   * modal picked. The server's link for it is the nav's own, so the nav
   * highlights it; without one, the source is named in the query.
   *
   * An Inertia visit rather than a reload, so the page stays on screen while
   * the new one loads. The nav tree is normally sent once and kept, so the
   * visit asks for it again.
   */
  function onSourcesSaved(landing: {
    sourceKey: string | null;
    url: string | null;
  }): void {
    router.visit(landingUrl(landing), {
      headers: {'X-Craft-Refresh-Nav': '1'},
    });
  }

  function landingUrl(landing: {
    sourceKey: string | null;
    url: string | null;
  }): string {
    if (landing.url) {
      return landing.url;
    }

    if (landing.sourceKey === null) {
      return window.location.href;
    }

    const site = new URLSearchParams(window.location.search).get('site');
    const query = {source: landing.sourceKey, site: site ?? undefined};

    return props.sourceHref
      ? appendIndexQuery(props.sourceHref, query)
      : props.route.url(query);
  }
</script>

<template>
  <LayoutSlot v-if="$slots.actions" name="content-actions">
    <!-- Type-specific page actions (e.g. a New Entry or Upload button). -->
    <slot name="actions" :element-index="elementIndex" />
  </LayoutSlot>

  <BaseElementIndex
    :table="elementTable"
    :selectable="true"
    :loading="loading"
    :from="elementIndex.pagination.from"
    :to="elementIndex.pagination.to"
    :total="elementIndex.pagination.total"
    :enable-adjust-page-size="true"
    :actions="elementIndex.actions"
    :element-type="elementIndex.elementType"
    :source="elementIndex.source?.key"
    :context="elementIndex.context"
    @action-performed="onActionPerformed"
  >
    <template #header>
      <ElementIndexToolbar
        v-model:search="filters.form.search"
        v-model:status="filters.form.status"
        v-model:conditions="conditions"
        :processing="filters.form.processing"
        :status-options="elementIndex.statusOptions"
        :view-modes="visibleViewModes"
        :column-options="columnOptions"
        :sort-options="elementIndex.sortOptions"
        v-model:mode="mode"
        v-model:sort-field="sortField"
        v-model:sort-direction="sortDirection"
        v-model:table-columns="tableColumns"
        @submit="filters.submit"
        @reorder="reorder"
      >
        <template #actions>
          <!-- Type-specific actions that belong with the list itself, such
              as the entries index's New Entry button. -->
          <slot name="toolbar-actions" :element-index="elementIndex" />
        </template>
        <template #search-options>
          <slot name="search-options"></slot>
        </template>
      </ElementIndexToolbar>
    </template>
    <template #navbar>
      <slot name="navbar"></slot>
    </template>
    <template #body="{selection}">
      <!-- Delegated so every view mode gets double-click-to-edit without
            any of them knowing about it, matching Craft 5's element container
            listener. -->
      <div @dblclick="quickEdit.onDblClick">
        <ElementCards
          v-if="mode === 'cards'"
          :selection="selection"
          :data="elementIndex.data"
          :selectable="true"
          :loading="loading"
          :item-behavior="itemBehavior"
        />
        <ElementThumbs
          v-else-if="mode === 'thumbs'"
          :selection="selection"
          :data="elementIndex.data"
          :selectable="true"
          :loading="loading"
          :item-behavior="itemBehavior"
        />
        <DataTable
          v-else
          :table="elementTable"
          :selectable="true"
          :loading="loading"
          :spacing="TableSpacing.Spacious"
          :item-behavior="itemBehavior"
          :with-bottom-border="false"
          :structure="mode === 'structure'"
          :is-row-collapsed="structureView.isCollapsed"
          :is-row-pending="structureView.isPending"
          :reorderable="canReorderStructure"
          :can-move-row="canMoveRow"
          @toggle-structure="toggleStructure"
          @move-structure-row="moveStructureRow"
        />
      </div>
    </template>
  </BaseElementIndex>

  <!-- A nested source (an asset subfolder, say) opens the modal on the source
    it belongs to, which is what the modal lists. -->
  <CustomizeSourcesModal
    :is-active="customizeSourcesActive"
    :element-type="elementIndex.elementType"
    :page="elementIndex.page"
    :source-key="elementIndex.source?.key?.split('/')[0]"
    @close="customizeSourcesActive = false"
    @saved="onSourcesSaved"
  />
</template>

<style scoped lang="scss"></style>
