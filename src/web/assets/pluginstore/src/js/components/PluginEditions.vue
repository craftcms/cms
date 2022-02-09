<template>
  <div class="plugin-editions tw-mb-4">
    <plugin-edition
      v-for="(edition, key) in plugin.editions"
      :plugin="plugin"
      :edition="edition"
      :key="key"></plugin-edition>
  </div>
</template>

<script>
import PluginEdition from './PluginEdition'

export default {
  props: ['plugin'],

  components: {
    PluginEdition
  }
}
</script>

<style lang="scss">
.plugin-editions {
  @apply tw-py-6;

  .plugin-editions-edition {
    &:not(:last-child) {
      @apply tw-mb-6;
    }
  }
}

@media (min-width: 992px) {
  .plugin-editions {
    @apply tw-flex tw--mx-4 tw-justify-center;

    .plugin-editions-edition {
      @apply tw-w-1/3 tw-mx-4;

      &:not(:last-child) {
        @apply tw-mb-0;
      }
    }
  }
}
</style>
