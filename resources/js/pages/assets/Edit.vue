<script setup lang="ts">
    import ElementEditor from '@/modules/elements/components/ElementEditor.vue';
    import ElementEditScreen from '@/modules/elements/components/ElementEditScreen.vue';
    import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
    import {useIsSlideout} from '@/common/composables/screen';

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
    }>();

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
            <HtmlFragmentRenderer :fragment="previewFragment" class="mb-4" />
        </template>
    </component>
</template>
