<script setup lang="ts">
  import {useAnnouncer} from '@/common/composables/useAnnouncer';

  const {announcement} = useAnnouncer();
</script>

<template>
  <div
    id="global-live-region"
    class="sr-only"
    role="status"
  >
    {{ announcement }}
  </div>
</template>

<style scoped lang="scss"></style>
