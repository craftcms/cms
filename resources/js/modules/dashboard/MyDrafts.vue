<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import type {DashboardWidget} from './types';

  const props = defineProps<{widget: DashboardWidget}>();
  const drafts = computed(() => props.widget.data?.drafts ?? []);
</script>

<template>
  <craft-pane appearance="raised" padding="lg">
    <slot name="header" />
    <div class="body">
      <ul v-if="drafts.length" class="space-y-3" role="list">
        <li v-for="draft in drafts" :key="draft.id">
          <DynamicHtmlRenderer :html="draft.html" />
        </li>
      </ul>
      <craft-empty
        v-else
        :label="t('You don’t have any active drafts.')"
      ></craft-empty>
    </div>
  </craft-pane>
</template>
