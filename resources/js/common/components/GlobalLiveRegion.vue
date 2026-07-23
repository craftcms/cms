<script setup lang="ts">
  import {useAnnouncementStore} from '@/common/composables/useAnnouncementStore';

  const {announcement} = useAnnouncementStore();
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
