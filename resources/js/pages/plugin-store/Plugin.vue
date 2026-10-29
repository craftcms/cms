<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {router} from '@inertiajs/vue3';
  import {computed, onScopeDispose, shallowRef, watch} from 'vue';
  import {plugin as pluginRoute} from '@actions/PluginStore/PluginStoreController';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import PluginChangelog from '@/modules/plugin-store/components/PluginChangelog.vue';
  import PluginEdition from '@/modules/plugin-store/components/PluginEdition.vue';
  import PluginHero from '@/modules/plugin-store/components/PluginHero.vue';
  import PluginOverview from '@/modules/plugin-store/components/PluginOverview.vue';
  import PluginReviews from '@/modules/plugin-store/components/PluginReviews.vue';
  import StoreContainer from '@/modules/plugin-store/components/StoreContainer.vue';
  import {getPlugin} from '@/modules/plugin-store/api';
  import {usePluginStoreLayout} from '@/modules/plugin-store/composables/usePluginStoreLayout';
  import {isPluginFree} from '@/modules/plugin-store/pricing';
  import type {Plugin} from '@/modules/plugin-store/types';

  type Tab = 'overview' | 'reviews' | 'editions' | 'changelog';

  const props = defineProps<{handle: string; tab: Tab}>();

  const plugin = shallowRef<Plugin | null>(null);
  const notFound = shallowRef(false);
  let controller: AbortController | null = null;

  // Switching tabs keeps this page mounted, so the plugin only loads when the handle changes.
  watch(
    () => props.handle,
    async (handle) => {
      controller?.abort();
      controller = new AbortController();
      plugin.value = null;
      notFound.value = false;

      try {
        plugin.value = await getPlugin(handle, controller.signal);
      } catch {
        notFound.value = !controller.signal.aborted;
      }
    },
    {immediate: true}
  );

  onScopeDispose(() => controller?.abort());

  usePluginStoreLayout({title: () => plugin.value?.name});

  const tabs = computed(() => {
    const all: Array<{id: Tab; label: string}> = [
      {id: 'overview', label: t('Overview')},
      {id: 'reviews', label: t('Reviews')},
    ];

    if (
      plugin.value &&
      !isPluginFree(plugin.value) &&
      plugin.value.editions.length > 1
    ) {
      all.push({id: 'editions', label: t('Editions')});
    }

    all.push({id: 'changelog', label: t('Changelog')});
    return all;
  });

  const selectedIndex = computed(() =>
    Math.max(
      0,
      tabs.value.findIndex((tab) => tab.id === props.tab)
    )
  );

  // A tab is a view of the same plugin rather than a new page, so it replaces the history entry.
  function showTab(tab: Tab): void {
    if (tab === props.tab) {
      return;
    }

    router.replace({
      url: pluginRoute({
        handle: props.handle,
        ...(tab === 'overview' ? {} : {tab}),
      }).url,
      props: (current) => ({...current, tab}),
      preserveState: true,
      preserveScroll: true,
    });
  }

  function onTabShow(event: Event): void {
    const index = (event.target as HTMLElement & {selectedIndex: number})
      .selectedIndex;
    const tab = tabs.value[index];

    if (tab) {
      showTab(tab.id);
    }
  }
</script>

<template>
  <StoreContainer>
    <craft-empty
      v-if="notFound"
      icon="plug"
      :label="t('Couldn’t find that plugin.')"
    ></craft-empty>

    <div v-else-if="!plugin" class="flex justify-center p-lg">
      <craft-spinner></craft-spinner>
    </div>

    <div v-else class="grid gap-lg">
      <LayoutSlot name="title">
        <PluginHero :plugin="plugin" @show-reviews="showTab('reviews')" />
      </LayoutSlot>

      <craft-tabs
        :label="plugin.name"
        .selectedIndex="selectedIndex"
        @craft-tab-show="onTabShow"
      >
        <template v-for="tab in tabs" :key="tab.id">
          <craft-tab slot="tab">{{ tab.label }}</craft-tab>
          <div slot="panel">
            <template v-if="props.tab === tab.id">
              <PluginOverview v-if="tab.id === 'overview'" :plugin="plugin" />
              <PluginReviews
                v-else-if="tab.id === 'reviews'"
                :plugin="plugin"
              />
              <div v-else-if="tab.id === 'editions'" class="plugin-editions">
                <PluginEdition
                  v-for="edition in plugin.editions"
                  :key="edition.handle"
                  :plugin="plugin"
                  :edition="edition"
                />
              </div>
              <PluginChangelog v-else :plugin="plugin" />
            </template>
          </div>
        </template>
      </craft-tabs>
    </div>
  </StoreContainer>
</template>

<style scoped>
  .plugin-editions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(14rem, 20rem));
    gap: var(--c-spacing-lg);
    justify-content: center;
  }
</style>
