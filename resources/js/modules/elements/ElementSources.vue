<script setup lang="ts">
  import {computed} from 'vue';
  import {index} from '@routes/cp/content/index.js';
  import {router} from '@inertiajs/vue3';
  import useCraftData from '@/common/composables/useCraftData';
  import type {Source, SourceHeading} from '@/modules/elements/types/sources';

  const props = withDefaults(
    defineProps<{
      sources: Array<Source>;
      activeSource?: string | null;
      viewMode?: string | null;
    }>(),
    {activeSource: null, viewMode: null}
  );

  const {site} = useCraftData();

  // Group the flat source list: each heading absorbs the items that follow it
  // (until the next heading) into its `children`. Items before the first
  // heading stay at the top level. Already-grouped headings keep their existing
  // children, so this is safe to run on a partially/fully normalized list.
  const normalizedSources = computed<Source[]>(() => {
    const result: Source[] = [];
    let currentHeading: SourceHeading | null = null;

    for (const source of props.sources) {
      if (source.type === 'heading') {
        currentHeading = {...source, children: [...(source.children || [])]};
        result.push(currentHeading);
      } else if (currentHeading) {
        currentHeading.children.push(source);
      } else {
        result.push(source);
      }
    }

    return result;
  });

  // `index.url()` returns the plain string URL (vs. `index()`, which returns a
  // `{url, method}` pair). craft-nav-item needs a real string href so it renders
  // an interactive link; we intercept the click for SPA navigation.
  // Carry the active view mode so the server renders data for the mode the page
  // is actually showing. Without it, the source visit would fall back to the
  // default `table` mode while the restored local view state still shows cards
  // (mirrors how `useElementIndexViewMode` pushes a `viewMode` query param).
  function sourceUrl(key: string) {
    return index.url(
      {page: 'entries'},
      {
        query: {
          source: key,
          site: site?.handle,
          ...(props.viewMode ? {viewMode: props.viewMode} : {}),
        },
      }
    );
  }

  function visitSource(key: string) {
    router.visit(sourceUrl(key), {preserveState: true});
  }
</script>

<template>
  <craft-nav-list>
    <template
      v-for="source in normalizedSources"
      :key="source.type === 'heading' ? source.heading : source.key"
    >
      <template v-if="source.type === 'heading'">
        <craft-nav-item initial-state="open">
          <span class="text-xs font-bold">
            {{ source.heading }}
          </span>
          <div slot="subnav">
            <craft-nav-item
              v-for="child in source.children"
              :key="child.key"
              :href="sourceUrl(child.key)"
              :active="child.key === activeSource"
              :data-group="source.heading"
              @click.prevent="visitSource(child.key)"
            >
              {{ child.label }}
            </craft-nav-item>
          </div>
        </craft-nav-item>
      </template>
      <template v-else>
        <craft-nav-item
          :href="sourceUrl(source.key)"
          :active="source.key === activeSource"
          @click.prevent="visitSource(source.key)"
        >
          {{ source.label }}
        </craft-nav-item>
      </template>
    </template>
  </craft-nav-list>
</template>

<style scoped lang="scss"></style>
