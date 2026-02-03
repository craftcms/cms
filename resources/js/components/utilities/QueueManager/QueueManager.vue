<script setup lang="ts">
  import QueueManagerIndex from '@/components/utilities/QueueManager/QueueManagerIndex.vue';
  import QueueManagerShow from '@/components/utilities/QueueManager/QueueManagerShow.vue';

  withDefaults(
    defineProps<{
      view?: 'index' | 'show';
      initialData: Record<string, unknown>;
      totalJobs?: number;
      activeJob?: null;
      hasReservedJobs?: boolean;
      hasWaitingJobs?: boolean;
    }>(),
    {view: 'index', activeJob: null, totalJobs: 0}
  );
</script>

<template>
  <QueueManagerShow v-if="view === 'show'" :job="activeJob" />
  <QueueManagerIndex
    v-else
    :initialData="initialData"
    :totalJobs="totalJobs"
    :hasReservedJobs="hasReservedJobs"
    :hasWaitingJobs="hasWaitingJobs"
  />
</template>

<style scoped lang="scss"></style>
