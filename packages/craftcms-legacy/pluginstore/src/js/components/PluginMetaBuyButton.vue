<template>
  <div
    :class="{
      'tw-space-y-2 tw-mb-2': isPluginFree(plugin),
      'tw-space-y-4 tw-mb-4': !isPluginFree(plugin),
    }"
  >
    <template v-for="(edition, key) in plugin.editions">
      <div :key="key">
        <plugin-edition :plugin="plugin" :edition="edition" context="meta" />
      </div>
    </template>
  </div>
</template>

<script>
  import {isPluginFree} from '../utils/plugins';
  import PluginEdition from './PluginEdition';

  export default {
    components: {PluginEdition},
    props: {
      plugin: {
        type: Object,
        required: true,
      },
    },

    methods: {
      isPluginFree,
    },
  };
</script>
