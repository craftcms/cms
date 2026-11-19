<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {rootGroup} from '@/modules/conditions/conditionTree';
  import type {ConditionConfig} from '@/modules/conditions/types';
  import ConditionBuilderPopover from './ConditionBuilderPopover.vue';

  /** The index's whole filter condition, edited from the Filter button. */
  defineProps<{
    anchor: HTMLElement;
    align?: 'start' | 'end';
  }>();

  const emit = defineEmits<{close: []; apply: []}>();

  const conditions = defineModel<ConditionConfig | null>();

  function clear(): void {
    conditions.value = null;
    emit('apply');
  }

  function apply(draft: ConditionConfig): void {
    conditions.value = rootGroup(draft).rules.length ? draft : null;
    emit('apply');
  }
</script>

<template>
  <ConditionBuilderPopover
    :anchor="anchor"
    :align="align"
    :condition="conditions ?? null"
    builder-id="filters"
    :label="t('Filters')"
    :clear-label="conditions ? t('Clear') : undefined"
    @apply="apply"
    @clear="clear"
    @close="emit('close')"
  />
</template>
