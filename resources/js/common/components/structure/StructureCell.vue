<script setup lang="ts">
  /** A structure row's first cell, indented by its level, which it tells screen readers. */
  import {t} from '@craftcms/ui';

  defineProps<{
    level: number;
  }>();
</script>

<template>
  <div class="cp-table-structure">
    <span class="sr-only">{{ t('Level {level}', {level}) }} </span>
    <slot></slot>
  </div>
</template>
