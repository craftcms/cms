<template>
  <div
    class="plugin-editions"
    :class="{
      'tw-py-6': true,
      'md:tw-flex md:tw--mx-4 md:tw-justify-center': true,
    }"
  >
    <plugin-edition
      v-for="(edition, key) in plugin.editions"
      class="md:tw-flex-1 md:tw-mx-4 md:tw-max-w-xs"
      :plugin="plugin"
      :edition="edition"
      :key="key"
    ></plugin-edition>
  </div>
</template>

<script>
  import PluginEdition from './PluginEdition';

  export default {
    props: ['plugin'],

    components: {
      PluginEdition,
    },
  };
</script>
