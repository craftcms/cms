<script setup lang="ts">
  /**
   * The index of an element type registered on a route of its own.
   *
   * Its sources are listed under its nav item, which this page lends the
   * Customize Sources gear, and its route comes from the server as a plain URL.
   *
   * @see CraftCms\Cms\Http\Controllers\Elements\ElementTypeIndexController
   */
  import CpButtonLink from '@/common/components/CpButtonLink.vue';
  import ElementIndexPage from '@/modules/elements/index/components/ElementIndexPage.vue';
  import {
    appendIndexQuery,
    type ElementIndexRoute,
    withIndexReturnUrl,
  } from '@/modules/elements/index/composables/useElementIndexVisits';
  import {usePage} from '@inertiajs/vue3';
  import {computed} from 'vue';

  const props = defineProps<{
    /** Where this index lives. */
    indexUrl: string;
    /** The link to create an element, when the user can create one. */
    newElement?: {label: string; href: string} | null;
  }>();

  const route: ElementIndexRoute = {
    url: (query = {}) => appendIndexQuery(props.indexUrl, query),
  };

  const page = usePage();
  const newElementHref = computed(() =>
    props.newElement
      ? withIndexReturnUrl(props.newElement.href, page.url)
      : undefined
  );
</script>

<template>
  <ElementIndexPage :route="route" :source-href="indexUrl" customizable-sources>
    <template v-if="newElement" #toolbar-actions>
      <CpButtonLink :href="newElementHref" icon="plus" variant="primary">{{
        newElement.label
      }}</CpButtonLink>
    </template>
  </ElementIndexPage>
</template>
