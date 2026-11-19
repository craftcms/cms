<script setup lang="ts">
  import type {RuleSummaryPart} from '@/modules/conditions/summarizeRule';

  /** A view setting that differs from the source's, opening its menu. */
  defineProps<{
    label: string;
    icon: string;
    parts: RuleSummaryPart[];
    disabled?: boolean;
  }>();
</script>

<template>
  <!-- The icon stands in for the label, which screen readers still get. -->
  <button
    type="button"
    class="filter-pill__segment"
    :disabled="disabled"
    :aria-label="`${label}: ${parts.map((part) => part.text).join(' ')}`"
  >
    <craft-icon :name="icon"></craft-icon>
    <span
      v-for="(part, index) in parts"
      :key="index"
      class="view-change__value"
      >{{ part.text }}</span
    >
  </button>
</template>

<style scoped>
  .view-change__value {
    font-weight: 500;
  }
</style>
