<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, watch} from 'vue';
  import controller from '@/actions/CraftCms/Cms/Http/Controllers/Workflows/WorkflowTransitionsController';
  import {useMessages} from '@/modules/messages/useMessages';
  import type {
    ElementEditPayload,
    ElementEditPayloadUpdater,
  } from '@/modules/elements/composables/useElementEditor';
  import {useWorkflowTransition} from '../composables/useWorkflowTransition';

  const props = defineProps<{
    payload: ElementEditPayload;
    updatePayload: ElementEditPayloadUpdater;
  }>();

  const emit = defineEmits<{
    startEditing: [];
  }>();

  const review = computed(() => props.payload.workflow.current);
  const approved = computed(() => review.value?.status === 'approved');

  /**
   * Reviewers of the current stage keep the draft in review while they edit
   * it. Anyone else sends the review back to the earlier stage they review, or
   * takes the draft out of review, before editing, so nobody can approve or
   * publish it while they're editing.
   */
  const withdraws = computed(
    () =>
      !review.value?.isReviewer || !(approved.value || review.value.hasApproved)
  );

  const {error, processing, transition} = useWorkflowTransition(
    () => ({
      elementType: props.payload.elementType,
      elementId: props.payload.canonicalId,
      draftId: props.payload.draftId,
      siteId: props.payload.siteId,
    }),
    (workflowReview, editorActions, release) => {
      // Emitted first, as the new review status unmounts the callout.
      emit('startEditing');
      props.updatePayload((payload) => ({
        workflow: {
          ...payload.workflow,
          current: workflowReview,
        },
        editorActions,
        ...release,
      }));
    }
  );
  const messages = useMessages();

  watch(error, (message) => {
    if (message) {
      messages.error(message);
    }
  });

  async function startEditing(): Promise<void> {
    if (processing.value) {
      return;
    }

    if (withdraws.value) {
      const runId = review.value?.runId;

      if (runId == null) {
        error.value = t(
          'This review is no longer current. Refresh and try again.'
        );
        return;
      }

      await transition(controller.prepareForEditing.url({workflowRun: runId}));
      return;
    }

    emit('startEditing');
  }
</script>

<template>
  <craft-callout
    :variant="withdraws ? 'warning' : 'info'"
    icon="lock"
    appearance="fill"
    rounded="none"
  >
    <template v-if="withdraws && review?.rollbackStage">
      {{
        t('Editing this draft will send it back to the “{stage}” stage.', {
          stage: review.rollbackStage,
        })
      }}
    </template>
    <template v-else-if="withdraws">
      {{ t('Editing this draft will withdraw it from review.') }}
    </template>
    <template v-else-if="approved">
      {{ t('This draft is approved and ready to be published.') }}
    </template>
    <template v-else>
      {{ t('You’ve approved this draft.') }}
    </template>

    <craft-button
      slot="action"
      type="button"
      variant="outline"
      size="small"
      inherit
      :loading="processing"
      @click="startEditing"
    >
      {{ t('Start editing') }}
    </craft-button>
  </craft-callout>
</template>

<style scoped lang="scss">
  craft-callout {
    --c-callout-padding-inline: calc(var(--cp-container-padding) - 1px);
    --c-callout-icon-padding-inline: 0 var(--c-spacing-sm);
  }
</style>
