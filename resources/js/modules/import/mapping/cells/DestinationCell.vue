<script setup lang="ts">
  /** What a destination column is: its name and handle, and whether it can only be matched on. */
  import '@craftcms/ui/components/badge/badge';
  import '@craftcms/ui/components/info-icon/info-icon';
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import type {MappingColumn} from '../types';

  const {col} = defineProps<{col: MappingColumn}>();

  const isMatchOnly = computed(
    () => !col.isContainer && col.canBeSet === false
  );
</script>

<template>
  {{ col.label }}
  <template v-if="isMatchOnly">
    <craft-badge size="small" fill="gray">{{ t('Match only') }}</craft-badge>
    <craft-info-icon>{{
      t(
        'This value can’t be imported directly — it can only be used to match against an existing element.'
      )
    }}</craft-info-icon>
  </template>
  <br />
  <code>{{ col.handle }}</code>
</template>
