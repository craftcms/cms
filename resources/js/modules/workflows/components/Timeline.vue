<script setup lang="ts">
  import Run from './Run.vue';

  type WorkflowReviewData = CraftCms.Cms.Workflow.Data.WorkflowReviewData;

  const props = defineProps<{
    review: WorkflowReviewData;
  }>();
</script>

<template>
  <ol class="workflow-review-runs">
    <li v-for="(run, runIndex) in review.runs" :key="run.id">
      <Run
        :run="run"
        :number="review.runs.length - runIndex"
        :collapsible="review.runs.length > 1"
      />
    </li>
  </ol>
</template>

<style scoped>
  .workflow-review-runs {
    display: grid;
    gap: var(--c-spacing-sm);
    margin: 0;
    padding: 0;
    list-style: none;
  }
</style>
