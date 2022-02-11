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
@import "../../sass/variables";

.ps-sidebar {
  & > .nav-items {
    @apply tw-hidden;
  }
}

@media only screen and (min-width: 975px) {
  .ps-sidebar {
    & > .nav-items {
      @apply tw-block;
    }
  }
}
</style>