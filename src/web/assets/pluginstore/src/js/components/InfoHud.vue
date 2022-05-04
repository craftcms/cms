<template>
  <div class="info-hud">
    <v-popover placement="right">
      <c-icon icon="info-circle" />

      <template slot="popover">
        <slot></slot>
      </template>
    </v-popover>
  </div>
</template>

<script>
  import Vue from 'vue';
  import VTooltip from 'v-tooltip';

  Vue.use(VTooltip);
  VTooltip.options.autoHide = false;

  export default {};
</script>

<style lang="scss">
  @import '@craftcms/sass/mixins';

  .info-hud {
    @apply tw-inline-block;

    .v-popover {
      @apply tw-text-gray-400;
      display: inline-block;
      line-height: 0;

      &:hover,
      &.open {
        @apply tw-cursor-pointer tw-text-blue-600;
      }

      & > span {
        outline: none;
      }
    }
  }

  .tooltip {
    max-width: 250px;
  }
</style>
