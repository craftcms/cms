<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import EntryList from './EntryList.vue';
  import type {DashboardWidget} from './types';
  import {useWidgetColor} from './useWidgetColor';

  const color = useWidgetColor();

  const props = defineProps<{widget: DashboardWidget}>();

  type Draft = {
    id: number;
    url: string | null;
    title: string;
    thumbHtml?: string | null;
    draftName?: string | null;
    section?: string | null;
  };

  const drafts = computed<Draft[]>(() => props.widget.data?.drafts ?? []);
</script>

<template>
  <craft-card :data-color="color">
    <slot name="header" />
    <div class="body">
      <EntryList v-if="drafts.length" :items="drafts">
        <template #details="{item}">
          {{ item.section
          }}<template v-if="item.section && item.draftName">
            <span class="my-drafts__separator" aria-hidden="true">·</span
            ><span class="sr-only">, </span></template
          ><template v-if="item.draftName"
            ><craft-icon
              name="scribble"
              class="my-drafts__draft-icon"
            ></craft-icon
            >{{ item.draftName }}</template
          >
        </template>
      </EntryList>
      <craft-empty
        v-else
        :label="t('You don’t have any active drafts.')"
      ></craft-empty>
    </div>
  </craft-card>
</template>

<style scoped>
  .my-drafts__separator {
    margin-inline: var(--c-spacing-sm);
  }

  .my-drafts__draft-icon {
    --c-icon-size: var(--c-size-icon-xs);
    margin-inline-end: var(--c-spacing-xs);
    color: var(--c-text-quiet);
    opacity: 0.6;
  }
</style>
