<script setup lang="ts">
  import {computed, ref} from 'vue';
  import {router} from '@inertiajs/vue3';
  import useCraftData from '@/common/composables/useCraftData';
  import type {ElementIndexRoute} from '@/modules/elements/composables/useElementIndexVisits';
  import type {Source, SourceHeading} from '@/modules/elements/types/sources';

  const props = defineProps<{
    sources: Array<Source>;
    route: ElementIndexRoute;
    activeSource?: string | null;
    viewMode?: string | null;
  }>();

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

  // craft-nav-item needs a real string href so it renders an interactive link;
  // we intercept the click for SPA navigation.
  // Carry the active view mode so the server renders data for the mode the page
  // is actually showing. Without it, the source visit would fall back to the
  // default `table` mode while the restored local view state still shows cards
  // (mirrors how `useElementIndexViewMode` pushes a `viewMode` query param).
  function sourceUrl(key: string) {
    return props.route.url({
      source: key,
      site: site?.handle,
      viewMode: props.viewMode || undefined,
    });
  }

  // The source the user just clicked. It's activated immediately instead of
  // waiting for the round-trip; once the visit settles, the server-provided
  // `activeSource` becomes authoritative again.
  const pendingSource = ref<string | null>(null);

  // Optimistic active source: a pending click wins until it resolves, otherwise
  // fall back to the source the server says is active.
  const activeKey = computed(() => pendingSource.value ?? props.activeSource);

  const visitOptions = {
    except: ['sources', 'publishableSections'],
    preserveState: true,
    preserveScroll: true,
  };

  function prefetchSource(key: string) {
    router.prefetch(sourceUrl(key), visitOptions, {cacheFor: 0});
  }

  function visitSource(key: string) {
    if (key === activeKey.value) {
      return;
    }

    // Reflect the selection right away, before the request goes out.
    pendingSource.value = key;

    router.visit(sourceUrl(key), {
      // Switching sources rebuilds the list view (data, columns, sort, actions,
      // pagination…), but the source nav itself and the publishable sections
      // behind the New-entry button don't change — so skip re-sending those two
      // rather than re-fetching the entire page. Mirrors the partial-reload
      // approach the sort/pagination/view-mode composables already use.
      ...visitOptions,
      onFinish: () => {
        // Hand control back to the server prop once this visit settles. The
        // key guard means a superseded (cancelled) visit from rapid switching
        // won't clear the highlight for a newer selection.
        if (pendingSource.value === key) {
          pendingSource.value = null;
        }
      },
    });
  }

  // Folder/volume sources double as drag-and-drop move targets: their `data`
  // carries the backend `folder-id` / `can-move-to` flags. Inert unless an
  // asset-move drag is set up on the page.
  function sourceMoveAttrs(source: Source) {
    const data = 'data' in source ? source.data : undefined;
    const folderId = data?.['folder-id'];
    if (folderId == null || folderId === false) {
      return {};
    }

    return {
      'data-folder-drop-target': '',
      'data-folder-id': String(folderId),
      'data-can-move-to': data?.['can-move-to'] ? '' : undefined,
    };
  }
</script>

<template>
  <craft-nav-list>
    <template
      v-for="source in normalizedSources"
      :key="source.type === 'heading' ? source.heading : source.key"
    >
      <template v-if="source.type === 'heading'">
        <template v-if="!!source.heading">
          <craft-nav-item initial-state="open">
            <span class="text-xs font-bold" v-if="source.heading">
              {{ source.heading }}
            </span>
            <craft-nav-list slot="subnav">
              <craft-nav-item
                v-for="child in source.children"
                :key="child.key"
                :href="sourceUrl(child.key)"
                :active="child.key === activeKey"
                :data-group="source.heading"
                v-bind="sourceMoveAttrs(child)"
                @mousedown.exact="prefetchSource(child.key)"
                @click.exact.prevent="visitSource(child.key)"
              >
                {{ child.label }}
              </craft-nav-item>
            </craft-nav-list>
          </craft-nav-item>
        </template>
        <template v-else>
          <span>&nbsp;</span>
          <craft-nav-item
            v-for="child in source.children"
            :key="child.key"
            :href="sourceUrl(child.key)"
            :active="child.key === activeKey"
            :data-group="source.heading"
            v-bind="sourceMoveAttrs(child)"
            @mousedown.exact="prefetchSource(child.key)"
            @click.exact.prevent="visitSource(child.key)"
          >
            {{ child.label }}
          </craft-nav-item>
        </template>
      </template>
      <template v-else>
        <craft-nav-item
          :href="sourceUrl(source.key)"
          :active="source.key === activeKey"
          v-bind="sourceMoveAttrs(source)"
          @mousedown.exact="prefetchSource(source.key)"
          @click.exact.prevent="visitSource(source.key)"
        >
          {{ source.label }}
        </craft-nav-item>
      </template>
    </template>
  </craft-nav-list>
</template>
