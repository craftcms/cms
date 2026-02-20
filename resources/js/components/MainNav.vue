<script setup lang="ts">
  import useCraftData from '@/composables/useCraftData';
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
  const {nav} = useCraftData();
  const queue = computed(() => page.props.queue);
</script>

<template>
  <craft-nav-list>
    <craft-nav-item
      v-for="item in nav"
      :key="item.url"
      :icon="item.icon"
      :href="item.url"
      :active="item.sel"
      :indicator="!!item.badgeCount"
    >
      {{ item.label }}

      <template v-if="item.subnav">
        <craft-nav-list slot="subnav" v-if="item.subnav">
          <craft-nav-item
            v-for="subnavItem in item.subnav"
            :key="subnavItem.url"
            :active="subnavItem.sel"
            :href="subnavItem.url"
            :indicator="!!subnavItem.badgeCount"
          >
            <craft-icon
              :name="subnavItem.icon"
              v-if="subnavItem.icon"
              slot="icon"
            ></craft-icon>
            <span v-else class="nav-indicator" slot="icon"></span>
            {{ subnavItem.label }}
          </craft-nav-item>
        </craft-nav-list>
      </template>
    </craft-nav-item>
    <cp-queue-indicator
      :displayedJob.prop="queue.displayedJob"
      :hasReservedJobs.prop="queue.hasReservedJobs"
      :hasWaitingJobs.prop="queue.hasWaitingJobs"
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
