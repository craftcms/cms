<script setup lang="ts">
  import QueueManagerIndex from '@/modules/utilities/components/queue-manager/QueueManagerIndex.vue';
  import QueueManagerShow from '@/modules/utilities/components/queue-manager/QueueManagerShow.vue';
  import {inject, onMounted, ref} from 'vue';
  import type {JobInfo, JobUpdateDetail} from '@/modules/queue/types';
  import {Queue} from '@/common/types/keys';

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
    queue?.addEventListener('job-update', (event) => {
      if (!(event instanceof CustomEvent)) {
        return;
      }
      // SAFETY: Queue dispatches job-update with the JobUpdateDetail contract.
      const {detail} = event as CustomEvent<JobUpdateDetail>;
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
  <QueueManagerIndex v-else :jobs="jobs" :total-jobs="totalJobs" />
</template>

<style scoped lang="scss"></style>
