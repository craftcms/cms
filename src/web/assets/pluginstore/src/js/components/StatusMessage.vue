<template>
  <div class="status-message">
    <div>
      <template v-if="error">
        <icon icon="exclamation-triangle"></icon>
      </template>
      <template v-else>
        <spinner size="lg"></spinner>
      </template>
      <div class="message">{{ message }}</div>
    </div>
  </div>
</template>

<script>
export default {
  props: ['message', 'error']
}
</script>

<style lang="scss">
.status-message {
  @apply tw-flex-1 tw-flex tw-items-center tw-justify-center tw-text-center;

  div {
    .c-spinner {
      @apply tw-mb-6;
    }

    .c-icon {
      @apply tw-mb-6;

      width: 48px;
      height: 48px;
    }

    .message {
      @apply tw-text-center;
    }
  }
}
</style>
