<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import ElementIndex from '@/modules/elements/index/components/ElementIndex.vue';
  import {useElementIndexPage} from '@/modules/elements/index/composables/useElementIndexPage';
  import {elementIndexQueryParams} from '@/modules/elements/index/composables/useContentIndexData';
  import {useElementRowActions} from '@/modules/elements/index/composables/useElementRowActions';
  import type {ElementIndexRoute} from '@/modules/elements/index/composables/useElementIndexVisits';
  import {
    sourcesLandingUrl,
    type SourcesLanding,
  } from '@/modules/elements/index/composables/useCustomizeSources';
  import ElementBulkActionsBar from './ElementBulkActionsBar.vue';
  import {indexQueryCriteria} from '@/modules/elements/index/index-criteria';
  import {computed, ref} from 'vue';
  import CustomizeSourcesModal from '@/modules/elements/index/components/customize-sources/CustomizeSourcesModal.vue';
  import type {ElementIndexItemBehavior} from '@/modules/elements/types/item-behavior';
  import type {IndexQueryParams} from '@/modules/elements/index/composables/useElementIndexVisits';
  import {useNavItemAction} from '@/common/composables/useNavItemActions';
  import useCraftData from '@/common/composables/useCraftData';
  import {router} from '@inertiajs/vue3';
  import type {ElementIndexModel} from '@/modules/elements/index/types/model';
  import type {ActionItems} from '@/common/types';

  const props = defineProps<{
    /** The page's index route — the one per-page piece of the pipeline. */
    route: ElementIndexRoute;
    index?: ElementIndexModel;
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
    /** Items for the menu beside the `primary-action` slot's button. */
    menuActions?: ActionItems;
  }>();

  const page =
    props.index ??
    useElementIndexPage({
      route: props.route,
      pinnedColumn: props.pinnedColumn,
      filterParams: () => props.filterParams ?? {},
    });

  const {elementIndex, selection, selectAll} = page.view;
  const {quickEdit, editElement, viewElement} = useElementRowActions({
    rows: () => elementIndex.data,
    refresh: () => {
      void page.refresh();
    },
  });
  const footerActive = computed(
    () => Boolean(elementIndex.actions?.length) && selectAll.count.value > 0
  );
  /**
   * With every result selected, the server rebuilds the index's query rather
   * than taking the IDs, since most of the rows were never loaded.
   */
  const actionParams = computed(() =>
    selectAll.active.value
      ? {
          ...elementIndexQueryParams(elementIndex),
          selectAll: true,
          exceptIds: selectAll.exceptIds.value,
        }
      : indexQueryCriteria(elementIndex)
  );

  function onActionPerformed(): void {
    page.clearSelection();
    void page.refresh();
  }

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
   * modal picked.
   *
   * An Inertia visit rather than a reload, so the page stays on screen while
   * the new one loads.
   */
  function onSourcesSaved(landing: SourcesLanding): void {
    router.visit(sourcesLandingUrl(landing, props.route, props.sourceHref));
  }
</script>

<template>
  <div class="contents">
    <LayoutSlot v-if="$slots.actions" name="content-actions">
      <slot name="actions" :element-index="elementIndex" />
    </LayoutSlot>

    <ElementIndex
      :view="page.view"
      :item-behavior="itemBehavior"
      :footer-active="footerActive"
      offers-select-all
      :enable-adjust-page-size="true"
      :with-bottom-border="false"
      :quick-edit="quickEdit"
      :menu-actions="menuActions"
    >
      <template #toolbar-actions v-if="$slots['toolbar-actions']">
        <slot name="toolbar-actions" :element-index="elementIndex" />
      </template>
      <template #primary-action v-if="$slots['primary-action']">
        <slot name="primary-action" :element-index="elementIndex" />
      </template>
      <template #search-options v-if="$slots['search-options']">
        <slot name="search-options" />
      </template>
      <template #navbar v-if="$slots.navbar">
        <slot name="navbar" />
      </template>
      <template #footer="{pagination}">
        <ElementBulkActionsBar
          :selected-ids="selection.selectedIds.value"
          :selected-count="selectAll.count.value"
          :selects-all="selectAll.active.value"
          :selected-elements="selection.selectedRows.value"
          :actions="elementIndex.actions"
          :element-type="elementIndex.elementType"
          :source="elementIndex.source?.keyPath ?? elementIndex.source?.key"
          :context="elementIndex.context"
          :params="actionParams"
          :site-id="elementIndex.siteId"
          @edit="editElement"
          @view="viewElement"
          @performed="onActionPerformed"
          @clear="page.clearSelection"
        >
          <template #center>
            <component :is="pagination" />
          </template>
        </ElementBulkActionsBar>
      </template>
    </ElementIndex>

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
  </div>
</template>
