<script setup lang="ts">
  import {ref} from 'vue';
  import {router} from '@inertiajs/vue3';
  import ElementEditor from '@/modules/elements/components/ElementEditor.vue';
  import ElementEditScreen from '@/modules/elements/components/ElementEditScreen.vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import ImageEditorDialog from '@/modules/image-editor/components/ImageEditorDialog.vue';
  import {useIsSlideout} from '@/common/composables/screen';
  import type {RelativeFocalPoint} from '@/modules/image-editor/types';

  interface ImageEditorProps {
    assetId: number;
    filename: string;
    focalPoint: RelativeFocalPoint | null;
    imageEditorRatios: Record<string, string | number>;
    allowDegreeFractions: boolean;
    orientation: 'ltr' | 'rtl';
  }

  // Full pages render `ElementEditScreen`, which fills the shell's `main` slot
  // and so owns the whole main region. A slideout panel brings its own header,
  // form and footer, so this stays on the layout-slot editor there.
  //
  // Inline `<AppLayout>` (inside `ElementEditScreen`), so no ambient layout.
  defineOptions({layout: []});

  // The shared edit payload comes from the ElementEditor pipeline; only the
  // Asset-specific keys (AssetEditViewModel) remain props, alongside the
  // identity the generic element save resolves the asset from.
  const props = defineProps<{
    elementType: string;
    elementId: number | null;
    siteId: number | null;
    previewFragment: CraftCms.Cms.View.HtmlFragment | null;
    /** Null when the asset isn't an editable image. */
    imageEditor: ImageEditorProps | null;
  }>();

  const imageEditorOpen = ref(false);

  /**
   * The preview is server-rendered HTML, so its Edit Image button is wired by
   * delegation once the fragment lands rather than by a template listener.
   */
  function onPreviewReady(element: HTMLElement): void {
    element.addEventListener('click', (event) => {
      const target = event.target as HTMLElement | null;

      if (target?.closest('[data-image-editor]')) {
        event.preventDefault();
        imageEditorOpen.value = true;
      }
    });
  }

  /**
   * Saving in place changes the file behind the same asset, so the screen has
   * to refetch to pick up the new thumbnail; saving a copy leaves this asset
   * untouched and needs nothing.
   */
  function onImageSaved(result: {newAssetId?: number}): void {
    if (!result.newAssetId) {
      router.reload();
    }
  }

  const editor = useIsSlideout() ? ElementEditor : ElementEditScreen;

  // Assets have no store action of their own, so the generic element save
  // reads the identity attributes every element carries.
  const saveData = () => ({
    elementType: props.elementType,
    elementId: props.elementId,
    siteId: props.siteId,
  });
</script>

<template>
  <component :is="editor" :save-data="saveData">
    <!-- The file preview sits above the meta fields, as in the legacy
      editor's sidebar. -->
    <template v-if="previewFragment" #details-header>
      <HtmlFragmentRenderer
        :fragment="previewFragment"
        class="mb-4"
        @ready="onPreviewReady"
      />
    </template>
  </component>

  <ImageEditorDialog
    v-if="imageEditor"
    v-bind="imageEditor"
    v-model:open="imageEditorOpen"
    @saved="onImageSaved"
  />
</template>
