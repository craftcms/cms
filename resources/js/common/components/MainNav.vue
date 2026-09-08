<script setup lang="ts">
  import useCraftData from '@/common/composables/useCraftData';
  import ActionList from '@/common/components/ActionList.vue';
  import {navItemActions} from '@/common/composables/navActions';
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
  // Server shape in, descriptors out: the trail and the badges are decided
  // against the nav's own fields, then the whole tree becomes the same
  // descriptors a menu would draw.
  const nav = computed(() =>
    navItemActions(
      withNavBadges(
        withNavSelection(sharedNav.value, page.url),
        navBadges.value
      )
    )
  );

  // `ActionList` draws the levels: the branch you're in expands in place, and
  // everything else opens in a flyout on hover. Collapsed to a rail there's no
  // room to indent at all, so every branch flyouts.
  const {iconOnly = false} = defineProps<{iconOnly?: boolean}>();
  const queue = computed(() => page.props.queue);
</script>

<template>
  <craft-nav-list>
    <ActionList :actions="nav" as="craft-nav-item" :icon-only="iconOnly" />
    <cp-queue-indicator
      :displayed-job.prop="queue.displayedJob"
      :has-reserved-jobs.prop="queue.hasReservedJobs"
      :has-waiting-jobs.prop="queue.hasWaitingJobs"
    ></cp-queue-indicator>
  </craft-nav-list>
</template>
