<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import AvatarStack from './AvatarStack.vue';
  import EntryList from './EntryList.vue';
  import type {DashboardWidget} from './types';
  import {useWidgetColor} from './useWidgetColor';

  const color = useWidgetColor();

  const props = defineProps<{widget: DashboardWidget}>();

  type Author = {
    id: number | null;
    name: string;
    thumbHtml?: string | null;
  };

  type Entry = {
    id: number;
    url: string | null;
    title: string;
    thumbHtml?: string | null;
    section?: string | null;
    dateCreated?: string | null;
    dateLabel?: string | null;
    authors?: Author[];
  };

  const entries = computed<Entry[]>(() => props.widget.data?.entries ?? []);
</script>

<template>
  <craft-card :data-color="color">
    <slot name="header" />
    <div class="body">
      <EntryList v-if="entries.length" :items="entries">
        <template #details="{item}">
          <time
            v-if="item.dateLabel"
            :datetime="item.dateCreated ?? undefined"
            >{{ item.dateLabel }}</time
          ><template v-if="item.dateLabel && item.section">
            <span class="recent-entries__separator" aria-hidden="true">·</span
            ><span class="sr-only">, </span></template
          >{{ item.section }}
        </template>
        <template #aside="{item}">
          <AvatarStack :people="item.authors ?? []" />
        </template>
      </EntryList>
      <craft-empty v-else :label="t('No entries exist yet.')"></craft-empty>
    </div>
  </craft-card>
</template>

<style scoped>
  .recent-entries__separator {
    margin-inline: var(--c-spacing-sm);
  }
</style>
