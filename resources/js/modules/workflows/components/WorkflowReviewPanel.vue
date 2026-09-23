<script setup lang="ts">
  import {computed, shallowRef, watch} from 'vue';
  import type {ElementEditorActions} from '@/modules/elements/composables/useElementEditor';
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
    ];
  }>();

  const review = shallowRef(props.review);
  const actionComponent = computed(() => review.value.actionComponent ?? null);
  const actionProps = computed(() => review.value.actionProps ?? {});

  watch(
    () => props.review,
    (value) => {
      review.value = value;
    }
  );

  function updateReview(
    value: WorkflowReviewData,
    editorActions: ElementEditorActions
  ): void {
    review.value = value;
    emit('reviewUpdated', value, editorActions);
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
    gap: var(--c-spacing-md);
  }
</style>
