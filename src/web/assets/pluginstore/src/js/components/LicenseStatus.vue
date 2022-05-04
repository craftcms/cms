<template>
  <div
    class="license-status"
    :class="{
      'tw-border tw-border-solid tw-px-4 tw-py-2 tw-rounded-md tw-text-base tw-opacity-100 tw-flex tw-items-center tw-justify-center tw-cursor-pointer': true,
      [status]: true,
    }"
  >
    <c-icon icon="check" class="tw-mr-2" />
    {{ description }}
  </div>
</template>

<script>
  export default {
    props: ['status', 'description'],
  };
</script>

<style lang="scss" scoped>
  @import '@craftcms/sass/mixins';

  .license-status {
    &.license-status {
      &:hover {
        cursor: default;
      }

      &.installed {
        @apply tw-text-green-700 tw-border-green-700;
      }

      &.licensed {
        @apply tw-text-blue-600 tw-border-blue-600;
      }
    }
  }
</style>
