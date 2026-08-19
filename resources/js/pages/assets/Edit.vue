<script setup lang="ts">
  import ElementEditor from '@/modules/elements/components/ElementEditor.vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';

  // The shared edit payload comes from the ElementEditor pipeline; only the
  // Asset-specific keys (AssetEditViewModel) remain props, alongside the
  // identity the generic element save resolves the asset from.
  const props = defineProps<{
    elementType: string;
    elementId: number | null;
    siteId: number | null;
    previewFragment: CraftCms.Cms.View.HtmlFragment | null;
  }>();

  // Assets have no store action of their own, so the generic element save
  // reads the identity attributes every element carries.
  const saveData = () => ({
    elementType: props.elementType,
    elementId: props.elementId,
    siteId: props.siteId,
  });
</script>

<template>
  <ElementEditor :save-data="saveData">
    <!-- The file preview sits above the meta fields, as in the legacy
      editor's sidebar. -->
    <template v-if="previewFragment" #details-header>
      <HtmlFragmentRenderer :fragment="previewFragment" class="mb-4" />
    </template>
  </ElementEditor>
</template>
