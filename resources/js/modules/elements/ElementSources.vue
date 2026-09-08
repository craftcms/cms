<script setup lang="ts">
  /**
   * An element index's sources, as a plain list.
   *
   * Used where there's no page chrome to hang them on — the element selector
   * modal. A full index page renders the same descriptors through
   * `SecondaryNav`, so the list and the menu it collapses into stay in step.
   */
  import ActionList from '@/common/components/ActionList.vue';
  import {useElementSourceActions} from '@/modules/elements/composables/useElementSourceActions';
  import type {
    ElementIndexRoute,
    IndexVisitor,
  } from '@/modules/elements/composables/useElementIndexVisits';
  import type {Source} from '@/modules/elements/types/sources';

  const props = defineProps<{
    sources: Array<Source>;
    route: ElementIndexRoute;
    activeSource?: string | null;
    viewMode?: string | null;
    /**
     * Supplied by indexes that aren't a page — the element selector modal.
     *
     * Without it, picking a source runs an Inertia visit, which in a modal
     * navigates the page *behind* it.
     */
    indexVisitor?: IndexVisitor;
  }>();

  const {actions} = useElementSourceActions({
    sources: () => props.sources,
    route: () => props.route,
    activeSource: () => props.activeSource,
    viewMode: () => props.viewMode,
    indexVisitor: () => props.indexVisitor,
  });
</script>

<template>
  <craft-nav-list>
    <!-- `inline`: a source list is a list, not a nav you travel through, so
      every group stays open rather than waiting to be hovered. -->
    <ActionList :actions="actions" as="craft-nav-item" mode="inline" />
  </craft-nav-list>
</template>
