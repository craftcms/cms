<script setup lang="ts">
  import {ref, watch} from 'vue';
  import {router} from '@inertiajs/vue3';
  import ElementEditor from '@/modules/elements/components/ElementEditor.vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import ImageEditorDialog from '@/modules/image-editor/components/ImageEditorDialog.vue';
  import type {SaveResult} from '@/modules/image-editor/useImageEditor';
  import type {RelativeFocalPoint} from '@/modules/image-editor/types';

  interface ImageEditorProps {
    assetId: number;
    filename: string;
    focalPoint: RelativeFocalPoint | null;
    imageWidth: number | null;
    imageHeight: number | null;
    imageEditorRatios: Record<string, string | number>;
    allowDegreeFractions: boolean;
    orientation: 'ltr' | 'rtl';
  }

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
    /** Whether `?editing` asked for the image editor to open on load. */
    editingImage: boolean;
  }>();

  const imageEditorOpen = ref(props.editingImage);

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
   * Mirrors the editor's open state in `?editing`, so it can be linked to and
   * survives a refresh. `replaceState` rather than an Inertia visit: it's UI
   * state.
   */
  watch(imageEditorOpen, (editing) => {
    const url = new URL(window.location.href);

    if (editing) {
      url.searchParams.set('editing', 'true');
    } else {
      url.searchParams.delete('editing');
    }

    if (url.href !== window.location.href) {
      window.history.replaceState(window.history.state, '', url.href);
    }
  });

  /** Saving in place refetches for the new thumbnail; a copy navigates to it. */
  function onImageSaved(result: SaveResult): void {
    if (result.newAssetUrl) {
      router.visit(result.newAssetUrl);
      return;
    }

    // Drop `editing` now: the watcher flushes after this handler, and
    // `reload()` refetches the current URL.
    const url = new URL(window.location.href);
    url.searchParams.delete('editing');

    router.visit(url.href, {
      replace: true,
      preserveScroll: true,
      preserveState: true,
    });
  }

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
      <div class="asset-preview">
        <HtmlFragmentRenderer
          :fragment="previewFragment"
          class="mb-4"
          @ready="onPreviewReady"
        />
      </div>
    </template>
  </ElementEditor>

  <ImageEditorDialog
    v-if="imageEditor"
    v-bind="imageEditor"
    v-model:open="imageEditorOpen"
    @saved="onImageSaved"
  />
</template>

<style scoped>
  /*
   * craft-thumbnail's own fixed --c-thumbnail-size square box doesn't fit
   * this preview, which is fluid-width and capped at 190px tall — override
   * its exposed `thumbnail` part so it shrinks to the (already
   * server-scaled) image's natural size instead, matching how the plain
   * <img> this preview used before craft-thumbnail was sized.
   */
  .asset-preview :deep(.preview-thumb craft-thumbnail::part(thumbnail)) {
    width: auto;
    height: auto;
    max-width: 100%;
    max-height: 190px;
  }
</style>
