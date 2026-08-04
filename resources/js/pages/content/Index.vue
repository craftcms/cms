<script setup lang="ts">
    import NewEntryButton, {
        type PublishableSection,
    } from '@/modules/elements/components/NewEntryButton.vue';
    import ElementIndexPage from '@/modules/elements/components/ElementIndexPage.vue';
    import type {ElementIndexRoute} from '@/modules/elements/composables/useElementIndexVisits';
    import {index} from '@/routes/craft/cp/content/index.js';
    import {usePage} from '@inertiajs/vue3';

    // The shared index payload comes from the ElementIndexPage pipeline; only
    // the Entry-specific keys (EntryIndexViewModel) remain props.
    const props = defineProps<{
        sectionHandle?: string | number;
        publishableSections: Array<PublishableSection>;
    }>();

    const page = usePage<{page: string}>();

    // The pipeline is route-agnostic; this page owns the fact that its index
    // lives at `content.index` with `page`/`sectionHandle` route params.
    const route: ElementIndexRoute = {
        url: (query = {}) =>
            index.url(
                {
                    page: page.props.page ?? '',
                    sectionHandle: props.sectionHandle || undefined,
                },
                {query: query as Record<string, string>}
            ),
    };
</script>

<template>
    <ElementIndexPage :route="route">
        <template #actions="{elementIndex}">
            <NewEntryButton
                :sources="elementIndex.sources"
                :source="elementIndex.source ?? undefined"
                :publishable-sections="publishableSections"
                :element-display-name="elementIndex.elementDisplayName"
            />
        </template>
    </ElementIndexPage>
</template>

<style scoped lang="scss"></style>
