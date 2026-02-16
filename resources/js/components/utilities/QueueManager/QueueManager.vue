<script setup lang="ts">
  import QueueManagerIndex from '@/components/utilities/QueueManager/QueueManagerIndex.vue';
  import QueueManagerShow from '@/components/utilities/QueueManager/QueueManagerShow.vue';
  import {inject, onMounted, ref} from 'vue';
  import type {JobInfo} from '@craftcms/cp/src/types/index.js';
  import {Queue} from '@/types/keys';

  const props = withDefaults(
    defineProps<{
      initialData: Array<JobInfo>;
      totalJobs?: number;
      activeJob?: JobInfo | null;
      hasReservedJobs?: boolean;
      hasWaitingJobs?: boolean;
    }>(),
    {
      activeJob: null,
      totalJobs: 0,
      hasReservedJobs: false,
      hasWaitingJobs: false,
    }
  );

  const queue = inject(Queue);

  const jobs = ref<Array<JobInfo>>(props.initialData ?? []);
  const totalJobs = ref(props.totalJobs);

  onMounted(async () => {
    queue?.addEventListener('job-update', ({detail}) => {
      jobs.value = detail.jobInfo;
      totalJobs.value = detail.totalJobs;
    });
    if (props.hasReservedJobs) {
      queue?.startTracking(true);
    } else if (props.hasWaitingJobs) {
      await queue?.runQueue();
    }
  });
</script>

<template>
  <QueueManagerShow v-if="activeJob" :job="activeJob" />
  <QueueManagerIndex v-else :jobs="jobs" :totalJobs="totalJobs" />
</template>

<style scoped lang="scss"></style>
