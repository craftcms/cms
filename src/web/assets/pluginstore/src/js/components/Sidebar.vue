<template>
  <div class="ps-sidebar">
    <plugin-search />
    <category-selector />
    <nav-items class="tw-hidden lg:tw-block" />
  </div>
</template>

<script>
  import CategorySelector from './CategorySelector';
  import PluginSearch from './PluginSearch';
  import NavItems from './NavItems';

  export default {
    components: {
      NavItems,
      CategorySelector,
      PluginSearch,
    },
  };
</script>
