<script setup lang="ts">
  /**
   * A top-level live region that should only have one instance per layer.
   *
   */
  import {useAnnouncer} from '@/common/composables/useAnnouncer';

  const {announcement} = useAnnouncer();
</script>

<template>
  <div class="sr-only" role="status">
    {{ announcement }}
  </div>
</template>

<style scoped lang="scss"></style>
