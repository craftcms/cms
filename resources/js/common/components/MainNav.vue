<script setup lang="ts">
  import useCraftData from '@/common/composables/useCraftData';
  import CpLink from '@/common/components/CpLink.vue';
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
  const craftData = useCraftData();
  const nav = computed(() => craftData.nav);

  // Renders the nav as a rail: labels drop to tooltips, and subnavs move into
  // a flyout on hover or focus, since there's no room to indent them.
  const {iconOnly = false} = defineProps<{iconOnly?: boolean}>();
  const queue = computed(() => page.props.queue);
</script>

<template>
  <craft-nav-list>
    <CpLink
      v-for="item in nav"
      :key="item.url"
      as="craft-nav-item"
      :icon="item.icon || undefined"
      :icon-only="iconOnly || undefined"
      :href="item.url"
      :active.prop="item.selected"
      :indicator.prop="!!item.badgeCount"
      :external.prop="item.external"
      :inertia="!item.external"
    >
      {{ item.label }}

      <template v-if="item.subnav">
        <craft-nav-list slot="subnav">
          <CpLink
            v-for="subnavItem in item.subnav"
            :key="subnavItem.url"
            as="craft-nav-item"
            :active.prop="subnavItem.selected"
            :href="subnavItem.url"
            :indicator.prop="!!subnavItem.badgeCount"
            :external.prop="subnavItem.external"
            :inertia="!subnavItem.external"
          >
            <craft-icon
              :name="subnavItem.icon"
              v-if="subnavItem.icon"
              slot="icon"
            ></craft-icon>
            <span v-else class="nav-indicator" slot="icon"></span>
            {{ subnavItem.label }}
          </CpLink>
        </craft-nav-list>
      </template>
    </CpLink>
    <cp-queue-indicator
      :displayed-job.prop="queue.displayedJob"
      :has-reserved-jobs.prop="queue.hasReservedJobs"
      :has-waiting-jobs.prop="queue.hasWaitingJobs"
    ></cp-queue-indicator>
  </craft-nav-list>
</template>

<style scoped lang="scss">
  .nav-indicator {
    --nav-item-indicator-size: calc(4rem / 16);
    display: inline-flex;
    width: var(--nav-item-indicator-size);
    border-radius: var(--c-radius-full);
    aspect-ratio: 1;
    background-color: currentcolor;
  }

  .nav-indicator[active] {
    --nav-item-indicator-size: calc(6rem / 16);
  }
</style>
