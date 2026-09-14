<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import CpLink from '@/common/components/CpLink.vue';
  import type {DashboardWidget} from './types';

  const props = defineProps<{widget: DashboardWidget}>();

  type Entry = {
    url: string;
    title: string;
    dateCreated?: string;
    dateLabel?: string;
    author?: string;
  };

  const entries = computed<Entry[]>(() => props.widget.data?.entries ?? []);
</script>

<template>
  <craft-pane appearance="raised" padding="lg">
    <slot name="header" />
    <div class="body">
      <ul v-if="entries.length" class="space-y-3" role="list">
        <li v-for="entry in entries" :key="entry.url">
          <CpLink :href="entry.url">{{ entry.title }}</CpLink>
          <span v-if="entry.dateCreated" class="light nowrap ms-1">
            {{ entry.dateLabel
            }}{{ entry.author ? `, ${entry.author}` : '' }}</span
          >
        </li>
      </ul>
      <craft-empty v-else :label="t('No entries exist yet.')"></craft-empty>
    </div>
  </craft-pane>
</template>
