<script setup lang="ts">
  /**
   * The element index for a screen that declared itself one in Twig.
   *
   * `content/Index` is the entries index: it owns the fact that it lives at the
   * `content.index` route and fills the toolbar with its own New Entry button.
   * A Craft 5-era screen has neither — it's `{% extends "_layouts/elementindex"
   * %}` on whatever route its plugin registered — so its route comes from the
   * server as a plain URL, and whatever it added to the legacy toolbar comes
   * across as markup.
   *
   * Everything else is the shared pipeline, driven by the same
   * `ContentIndexViewModel` payload the entries index uses.
   *
   * @see CraftCms\Cms\Http\Responses\BridgedScreen
   */
  import ElementIndexPage from '@/modules/elements/index/components/ElementIndexPage.vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import useCraftData from '@/common/composables/useCraftData';
  import {t} from '@craftcms/ui/utilities/translate';
  import {
    appendIndexQuery,
    type ElementIndexRoute,
  } from '@/modules/elements/index/composables/useElementIndexVisits';
  import {computed, onBeforeUnmount, watch} from 'vue';

  const props = defineProps<{
    /** Where this index lives — the route the plugin registered for it. */
    indexUrl: string;
    /** Whatever a child template added to the legacy toolbar, if anything. */
    toolbarHtml?: string | null;
    /** Classes the screen put on `<body>`, which its CSS may rely on. */
    bodyClass?: Array<string> | string | null;
    /** The element type whose sources the nav is showing. */
    elementType?: string | null;
    /** The index's page, when the element type splits its sources across more than one. */
    page?: string | null;
    /** The source the index is on, so the editor opens on it. */
    sourceKey?: string | null;
  }>();

  /**
   * The sources editor, which a ported index reaches through the gear on its
   * nav item. This screen has no nav item of its own, so it offers the editor
   * from the secondary nav's own menu — where Craft 5 put it.
   *
   * The server refuses anyone else, so the entry is only offered to those it
   * would accept.
   */
  const {currentUser, allowAdminChanges} = useCraftData();
  const canCustomizeSources = computed(
    () =>
      Boolean(props.elementType) &&
      Boolean(currentUser.value?.admin) &&
      allowAdminChanges.value
  );

  function customizeSources(): void {
    window.Craft?.openCustomizeSourcesModal?.({
      elementType: props.elementType!,
      page: props.page ?? null,
      sourceKey: props.sourceKey ?? null,
    });
  }

  const route: ElementIndexRoute = {
    url: (query = {}) => appendIndexQuery(props.indexUrl, query),
  };

  const toolbarFragment = computed(() =>
    props.toolbarHtml
      ? {html: props.toolbarHtml, headHtml: '', bodyHtml: ''}
      : null
  );

  /**
   * `_layouts/cp` applied these to the document's own `<body>`; the shell owns
   * `<body>` here, so they're applied for as long as this index is on screen.
   * Commerce scopes its order index styles to `.commerceorders`, for one.
   */
  const bodyClasses = computed(() => {
    const value = props.bodyClass ?? [];
    const asked = Array.isArray(value) ? value : value.split(/\s+/);

    return [...new Set(asked.filter(Boolean))];
  });

  let applied: Array<string> = [];

  watch(
    bodyClasses,
    (classes) => {
      document.body.classList.remove(...applied);
      document.body.classList.add(...classes);
      applied = classes;
    },
    {immediate: true}
  );

  onBeforeUnmount(() => {
    document.body.classList.remove(...applied);
    applied = [];
  });
</script>

<template>
  <LayoutSlot v-if="canCustomizeSources" name="subnav-actions">
    <craft-action-item icon="gear" @click="customizeSources">
      {{ t('Customize sources') }}
    </craft-action-item>
  </LayoutSlot>

  <ElementIndexPage :route="route" :source-href="indexUrl">
    <template v-if="toolbarFragment" #toolbar-actions>
      <HtmlFragmentRenderer :fragment="toolbarFragment" />
    </template>
  </ElementIndexPage>
</template>
