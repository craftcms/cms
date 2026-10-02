<script setup lang="ts">
  import {computed} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import {editSettings} from '@actions/PluginsController';
  import type {PluginInfo} from '@/modules/plugin-manager/types/plugins';
  import CpLink from '@/common/components/CpLink.vue';

  const props = defineProps<{
    plugin: PluginInfo;
  }>();

  const page = usePage<{readOnly: boolean}>();
  const readOnly = computed(() => page.props.readOnly);

  const links = computed(() => {
    if (props.plugin.links) {
      return props.plugin.links;
    }

    const links = [];
    if (props.plugin.developer) {
      links.push({
        icon: 'building',
        href: props.plugin.developerUrl,
        text: props.plugin.developer,
      });
    }

    if (props.plugin.documentationUrl) {
      links.push({
        icon: 'book',
        href: props.plugin.documentationUrl,
        text: 'Documentation',
      });
    }

    if (
      props.plugin.hasCpSettings &&
      (!readOnly.value || props.plugin.hasReadOnlyCpSettings)
    ) {
      links.push({
        icon: 'gear',
        href: editSettings({handle: props.plugin.handle}).url,
        text: 'Settings',
        internal: true,
      });
    }

    return links;
  });
</script>

<template>
  <ul v-if="links?.length > 0" class="flex gap-3 items-base">
    <li v-for="link in links" :key="link.href">
      <CpLink
        v-if="link.internal"
        :href="link.href"
        :icon="link.icon"
        class="flex gap-1 items-center"
      >
        {{ link.text }}
      </CpLink>
      <a
        v-else
        :href="link.href"
        target="_blank"
        rel="noopener"
        class="flex gap-1 items-center"
      >
        <craft-icon v-if="link.icon" :name="link.icon"></craft-icon>
        {{ link.text }}
      </a>
    </li>
  </ul>
</template>

<style scoped lang="scss"></style>
