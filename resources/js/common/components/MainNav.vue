<script setup lang="ts">
  import type {CraftData} from '@/common/composables/useCraftData';
  import NavTree from '@/common/components/NavTree.vue';
  import {computed} from 'vue';
  import {usePage} from '@inertiajs/vue3';

  const page = usePage<{
    craft: CraftData;
    queue: {
      enabled: boolean;
      displayedJob: any;
      hasReservedJobs: boolean;
      hasWaitingJobs: boolean;
    };
  }>();

  // Read the nav off the page rather than through `useCraftData()`, which
  // hands back `page.props.craft` as it stood at setup — a plain object, so a
  // computed over it has no reactive dependency at all and can never update.
  // This component lives in the sidebar and never remounts, so it would keep
  // highlighting whichever section you first landed on.
  const nav = computed(() => page.props.craft.nav);

  // `NavTree` draws the levels: the branch you're in expands in place, and
  // everything else opens in a flyout on hover. Collapsed to a rail there's no
  // room to indent at all, so every branch flyouts.
  const {iconOnly = false} = defineProps<{iconOnly?: boolean}>();
  const queue = computed(() => page.props.queue);
</script>

<template>
  <craft-nav-list>
    <NavTree :items="nav" :icon-only="iconOnly" />
    <cp-queue-indicator
      :displayed-job.prop="queue.displayedJob"
      :has-reserved-jobs.prop="queue.hasReservedJobs"
      :has-waiting-jobs.prop="queue.hasWaitingJobs"
    ></cp-queue-indicator>
  </craft-nav-list>
</template>
