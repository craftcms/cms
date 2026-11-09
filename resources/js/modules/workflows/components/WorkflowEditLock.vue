<script setup lang="ts">
  /**
   * The callout for a draft locked while it's in review, and the question it
   * asks first when the draft is approved for a scheduled release: editing it
   * undoes its approval, which will stop the release from publishing. A draft
   * that's already blocking the release isn't asked about.
   */
  import {t} from '@craftcms/ui';
  import {computed, shallowRef} from 'vue';
  import type {
    ElementEditPayload,
    ElementEditPayloadUpdater,
  } from '@/modules/elements/composables/useElementEditor';
  import WorkflowEditLockCallout from './WorkflowEditLockCallout.vue';

  const props = defineProps<{
    payload: ElementEditPayload;
    updatePayload: ElementEditPayloadUpdater;
  }>();

  const emit = defineEmits<{
    startEditing: [];
  }>();

  /** Settles the pending question, while the dialog asking it is open. */
  const settleScheduledEdit = shallowRef<((confirmed: boolean) => void) | null>(
    null
  );

  const confirmEditing = computed(() =>
    props.payload.release?.status === 'scheduled' &&
    !props.payload.release.blockers.length
      ? () =>
          new Promise<boolean>((resolve) => {
            settleScheduledEdit.value = resolve;
          })
      : undefined
  );

  function answerScheduledEdit(confirmed: boolean): void {
    settleScheduledEdit.value?.(confirmed);
    settleScheduledEdit.value = null;
  }
</script>

<template>
  <div>
    <WorkflowEditLockCallout
      :payload="payload"
      :update-payload="updatePayload"
      :confirm-editing="confirmEditing"
      @start-editing="emit('startEditing')"
    />

    <craft-dialog
      :label="t('Edit a draft in a scheduled release?')"
      .opened="settleScheduledEdit !== null"
      @craft-hide="answerScheduledEdit(false)"
    >
      <p class="m-0">
        {{
          t(
            'Editing this draft will undo its approval. Unless it’s approved again before “{name}” publishes, the release will fail to publish.',
            {name: payload.release?.name ?? ''}
          )
        }}
      </p>
      <craft-button
        slot="footer"
        type="button"
        autofocus
        @click="answerScheduledEdit(false)"
      >
        {{ t('Cancel') }}
      </craft-button>
      <craft-button
        slot="footer"
        type="button"
        variant="primary"
        @click="answerScheduledEdit(true)"
      >
        {{ t('Edit anyway') }}
      </craft-button>
    </craft-dialog>
  </div>
</template>
