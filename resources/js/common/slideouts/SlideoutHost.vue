<script setup lang="ts">
    /**
     * Renders the open slideout stack.
     *
     * Mounted once at the app root, outside the Inertia page tree, so panels
     * survive a navigation underneath them. Teleported to `<body>` so a panel's
     * form never nests inside the base page's `<form>`.
     *
     * The chrome shared with the legacy jQuery slideouts — the shade, the body
     * scroll lock, the stacking offsets, Escape ordering — belongs to
     * `panel-stack.ts`, and each `SlideoutPanel` registers itself there. This
     * component only decides which panels exist.
     */
    import {watch} from 'vue';
    import {usePage} from '@inertiajs/vue3';
    import SlideoutPanel from './SlideoutPanel.vue';
    import {slideoutPanels} from './store';
    import {setAssetVersion} from './request';

    const panels = slideoutPanels();
    const page = usePage();

    // Keep the version a slideout fetch sends in step with the loaded page.
    watch(
        () => page.version,
        (version) => setAssetVersion(version ?? ''),
        {immediate: true}
    );
</script>

<template>
    <Teleport v-if="panels.length" to="body">
        <SlideoutPanel
            v-for="panel in panels"
            :key="panel.id"
            :instance="panel"
        />
    </Teleport>
</template>
