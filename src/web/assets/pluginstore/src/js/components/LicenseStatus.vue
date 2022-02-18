<template>
  <c-btn
    icon="check"
    :disabled="true"
    :class="'license-status ' + status"
    block
    large
    outline>
    {{ description }}
  </c-btn>
</template>

<script>
export default {
  props: ['status', 'description']
}
</script>

<style
  lang="scss"
  scoped>
@import "@craftcms/sass/mixins";

.c-btn {
  &.license-status {
    &:hover {
      cursor: default;
    }

    &.installed {
      color: $successColor;
      border-color: $successColor;
    }

    &.licensed {
      color: $noticeColor;
      border-color: $noticeColor;
    }
  }
}
</style>
