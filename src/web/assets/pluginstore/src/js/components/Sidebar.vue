<template>
  <div class="ps-sidebar">
    <plugin-search />
    <category-selector />
    <nav-items />
  </div>
</template>

<script>
import CategorySelector from './CategorySelector'
import PluginSearch from './PluginSearch'
import NavItems from './NavItems';

export default {
  components: {
    NavItems,
    CategorySelector,
    PluginSearch,
  },
}
</script>

<style
  lang="scss"
  >
.ps-sidebar {
  & > .nav-items {
    @apply tw-hidden lg:tw-block;
  }
}
</style>