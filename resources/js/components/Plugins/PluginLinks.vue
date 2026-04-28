<script setup lang="ts">
  import {computed} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import {editSettings} from '@actions/PluginsController';
  import type {PluginInfo} from '@/types/plugins';

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
        href: editSettings(props.plugin.handle).url,
        text: 'Settings',
      });
    }

    return links;
  });
</script>

<template>
  <ul v-if="links?.length > 0" class="flex gap-3 items-base">
    <li v-for="link in links">
      <a
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
