<script setup lang="ts">
  import {useAnnouncer} from '@/composables/useAnnouncer';
  import TransitionFade from '@/components/TransitionFade.vue';

  const {announcement} = useAnnouncer();
</script>

<template>
  <TransitionFade>
    <div
      id="global-live-region"
      class="sr-only"
      role="status"
      v-if="announcement"
    >
      {{ announcement }}
    </div>
  </TransitionFade>
</template>

<style scoped lang="scss"></style>
