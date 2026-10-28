<script setup lang="ts">
  import {computed} from 'vue';
  import type {ElementEditorActions} from '@/modules/elements/composables/useElementEditor';
  import type {WorkflowReleaseState} from '../composables/useWorkflowTransition';
  import WorkflowDefaultActions from './WorkflowDefaultActions.vue';
  import Timeline from './Timeline.vue';

  type WorkflowReviewData = CraftCms.Cms.Workflow.Data.WorkflowReviewData;

  const props = defineProps<{
    review: WorkflowReviewData;
    elementType: string;
    elementId: number | null;
    draftId: number | null;
    siteId: number | null;
  }>();

  const emit = defineEmits<{
    reviewUpdated: [
      review: WorkflowReviewData,
      editorActions: ElementEditorActions,
      release: WorkflowReleaseState | null | undefined,
    ];
  }>();

  const actionComponent = computed(() => props.review.actionComponent ?? null);
  const actionProps = computed(() => props.review.actionProps ?? {});

  function updateReview(
    value: WorkflowReviewData,
    editorActions: ElementEditorActions,
    release: WorkflowReleaseState | null | undefined
  ): void {
    emit('reviewUpdated', value, editorActions, release);
  }
</script>

<template>
  <div class="workflow-review">
    <section class="workflow-review__body">
      <component
        :is="actionComponent"
        v-if="actionComponent"
        v-bind="actionProps"
        :review="review"
        :element-type="elementType"
        :element-id="elementId"
        :draft-id="draftId"
        :site-id="siteId"
        @review-updated="updateReview"
      />

      <WorkflowDefaultActions
        v-if="review.showDefaultActions"
        :review="review"
        :element-type="elementType"
        :element-id="elementId"
        :draft-id="draftId"
        :site-id="siteId"
        @review-updated="updateReview"
      />

      <Timeline v-if="review.runs.length" :review="review" />

      <slot />
    </section>
  </div>
</template>

<style scoped>
  .workflow-review {
    display: grid;
    gap: var(--c-spacing-md);
  }

  .workflow-review__body {
    display: grid;
    gap: var(--c-spacing-lg);
  }
</style>
