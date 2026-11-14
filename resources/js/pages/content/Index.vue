<script setup lang="ts">
  import NewEntryButton from '@/modules/elements/components/NewEntryButton.vue';
  import ElementIndexPage from '@/modules/elements/index/components/ElementIndexPage.vue';
  import {useElementIndexPage} from '@/modules/elements/index/composables/useElementIndexPage';
  import EntryMoverModal from '@/modules/entry-mover/EntryMoverModal.vue';
  import {useEntryMover} from '@/modules/entry-mover/useEntryMover';
  import {
    appendIndexQuery,
    type ElementIndexRoute,
  } from '@/modules/elements/index/composables/useElementIndexVisits';
  import {index} from '@/routes/craft/cp/content/index.js';
  import {usePage} from '@inertiajs/vue3';
  import useCraftData from '@/common/composables/useCraftData';

  // The shared index payload comes from the ElementIndexPage pipeline; only
  // the Entry-specific keys (EntryIndexViewModel) remain props.
  const props = defineProps<{
    sectionHandle?: string | number;
  }>();

  const page = usePage<{page: string}>();
  // The shared once prop, rather than a page prop of its own sent with every visit
  const {general} = useCraftData();

  // The pipeline is route-agnostic; this page owns the fact that its index
  // lives at `content.index` with `page`/`sectionHandle` route params.
  const route: ElementIndexRoute = {
    url: (query = {}) =>
      appendIndexQuery(
        index.url({
          page: page.props.page ?? '',
          sectionHandle: props.sectionHandle || undefined,
        }),
        query
      ),
  };

  const sourceHref = index.url({page: page.props.page ?? ''});
  const elementIndex = useElementIndexPage({route});
  const entryMover = useEntryMover(elementIndex);
</script>

<template>
  <div class="contents">
    <ElementIndexPage
      :route="route"
      :index="elementIndex"
      :source-href="sourceHref"
      customizable-sources
    >
      <template #toolbar-actions="{elementIndex: indexState}">
        <NewEntryButton
          :sources="indexState.sources"
          :source="indexState.source ?? undefined"
          :publishable-sections="general.publishableSections"
          :element-display-name="indexState.elementLowerDisplayName"
        />
      </template>
    </ElementIndexPage>

    <EntryMoverModal
      :request="entryMover.request.value"
      @close="entryMover.close"
      @moved="entryMover.moved"
    />
  </div>
</template>

<style scoped lang="scss"></style>
