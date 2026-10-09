<script setup lang="ts">
  /**
   * Everything a full page layers over itself: the debug panel, the elevated
   * session prompt, and the slideout panels.
   */
  import DebugPanel from '@/common/components/DebugPanel.vue';
  import SlideoutHost from '@/common/slideouts/SlideoutHost.vue';
  import ElevatedSessionHost from '@/modules/auth/elevated-session/ElevatedSessionHost.vue';

  defineProps<{
    debug?: any;
  }>();
</script>

<template>
  <DebugPanel v-if="debug" :data="debug" />
  <ElevatedSessionHost />
  <!-- Hosted by the full-page shell, not `AppLayout`: there's one per page, so
    an inline `<AppLayout>` can't double-render the panels. -->
  <SlideoutHost />
</template>
