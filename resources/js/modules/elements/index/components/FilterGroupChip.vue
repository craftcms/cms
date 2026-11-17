<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import type {GroupConfig} from '@/modules/conditions/types';
  import FilterChip from './FilterChip.vue';

  /** A nested group as one chip, which opens the group to edit. */
  const props = defineProps<{
    group: GroupConfig;
    expanded?: boolean;
    disabled?: boolean;
  }>();

  const emit = defineEmits<{
    edit: [anchor: HTMLElement];
    remove: [];
  }>();

  const label = computed(() =>
    props.group.operator === 'or'
      ? t('Any of {count} filters', {count: props.group.rules.length})
      : t('All of {count} filters', {count: props.group.rules.length})
  );
</script>

<template>
  <FilterChip
    :label="label"
    :parts="[]"
    :expanded="expanded"
    :disabled="disabled"
    @edit="(anchor) => emit('edit', anchor)"
    @remove="emit('remove')"
  >
    <span>{{ group.operator === 'or' ? t('Any of') : t('All of') }}</span>
    <!-- The chip's label already says how many. -->
    <craft-badge-indicator
      variant="inverse"
      :badge-count="group.rules.length"
      :max="Infinity"
      aria-hidden="true"
    ></craft-badge-indicator>
  </FilterChip>
</template>
