<template>
  <div
    class="edition-badge"
    :class="cssClass">
    <div class="edition-badge-name">{{ name }}</div>
  </div>
</template>

<script>
export default {
  props: ['name', 'block', 'big'],

  computed: {
    cssClass() {
      const cssClasses = {}

      if (typeof this.block !== 'undefined') {
        cssClasses['is-block'] = true
      }

      if (typeof this.big !== 'undefined') {
        cssClasses['is-big'] = true
      }

      return cssClasses
    }
  }
}
</script>

<style
  lang="scss"
  scoped>
@import "@craftcms/sass/mixins";

.edition-badge {
  @apply tw-inline-block;

  &.is-block {
    @apply tw-block;
  }

  &:not(.is-block) {
    @apply tw-relative;
    top: -1px;
  }

  &.is-big {
    .edition-badge-name {
      @apply tw-text-base tw-px-3 tw-py-1;
    }
  }

  .edition-badge-name {
    @apply tw-inline-block tw-uppercase tw-border tw-border-solid tw-border-gray-400 tw-px-2 tw-py-0 tw-text-gray-500 tw-rounded tw-text-xs;
    letter-spacing: 1.5px;
  }
}
</style>
