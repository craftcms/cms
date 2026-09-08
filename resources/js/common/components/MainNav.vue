<script setup lang="ts">
  import useCraftData from '@/common/composables/useCraftData';
  import NavTree from '@/common/components/NavTree.vue';
  import {
    withNavBadges,
    withNavSelection,
  } from '@/common/composables/navSelection';
  import {computed} from 'vue';
  import {usePage} from '@inertiajs/vue3';

  const page = usePage<{
    queue: {
      enabled: boolean;
      displayedJob: any;
      hasReservedJobs: boolean;
      hasWaitingJobs: boolean;
    };
  }>();

  const {nav: sharedNav, navBadges} = useCraftData();

  // The tree arrives once and then stays put, so neither the trail nor the
  // badge counts are in it — both are decided per page, here.
  const nav = computed(() =>
    withNavBadges(withNavSelection(sharedNav.value, page.url), navBadges.value)
  );

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
