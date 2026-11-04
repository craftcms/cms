<script setup lang="ts">
  import QueueManagerIndex from '@/modules/utilities/components/queue-manager/QueueManagerIndex.vue';
  import QueueManagerShow from '@/modules/utilities/components/queue-manager/QueueManagerShow.vue';
  import {inject, onMounted, onUnmounted, useTemplateRef} from 'vue';
  import type {JobInfo} from '@/modules/queue/types';
  import {Queue} from '@/common/types/keys';

  const props = withDefaults(
    defineProps<{
      activeJob?: JobInfo | null;
      hasReservedJobs?: boolean;
      hasWaitingJobs?: boolean;
    }>(),
    {
      activeJob: null,
      hasReservedJobs: false,
      hasWaitingJobs: false,
    }
  );

  const queue = inject(Queue);
  const index = useTemplateRef('index');

  function refreshIndex(): void {
    void index.value?.refresh({quiet: true});
  }

  onMounted(async () => {
    queue?.addEventListener('job-update', refreshIndex);
    if (props.hasReservedJobs) {
      queue?.startTracking(true);
    } else if (props.hasWaitingJobs) {
      await queue?.runQueue();
    }
  });

  onUnmounted(() => queue?.removeEventListener('job-update', refreshIndex));
</script>

<template>
  <QueueManagerShow v-if="activeJob" :job="activeJob" />
  <QueueManagerIndex v-else ref="index" />
</template>

<style scoped lang="scss"></style>
