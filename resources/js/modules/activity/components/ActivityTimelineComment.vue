<script setup lang="ts">
  import {actionClient, t} from '@craftcms/ui';
  import {computed, shallowRef, useId} from 'vue';
  import {
    destroy,
    store,
    update,
  } from '@actions/Elements/ActivityCommentsController';
  import ActivityMentionSuggestionsController from '@actions/Elements/ActivityMentionSuggestionsController';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import {
    COMMENT_MAX_LENGTH,
    useCharacterLimit,
  } from '@/common/composables/useCharacterLimit';
  import type {ActionItem} from '@/common/types';
  import type {ActivityEvent} from '@/modules/activity/composables/useActivityTimeline';
  import {commentToolbarButtons} from '@/modules/markdown-field/commentToolbarButtons';
  import ActivityCommentCard from './ActivityCommentCard.vue';
  import ActivityTimelineEvent from './ActivityTimelineEvent.vue';
  import '../../markdown-field/markdown-field';

  interface ActivityCommentResponse {
    event: ActivityEvent;
  }

  const props = defineProps<{
    event?: ActivityEvent;
    elementType: string;
    elementId: number | null;
    siteId: number | null;
    last?: boolean;
  }>();

  const emit = defineEmits<{
    created: [event: ActivityEvent];
    updated: [event: ActivityEvent];
  }>();

  const editing = shallowRef(false);
  const draft = shallowRef('');
  const mutating = shallowRef(false);
  const {overage, overageMessage} = useCharacterLimit(
    draft,
    COMMENT_MAX_LENGTH
  );
  const error = shallowRef(false);
  const creating = computed(() => props.event === undefined);
  const comment = computed(() => props.event?.comment);
  const commentActions = computed<ActionItem[]>(() => [
    ...(comment.value?.canEdit
      ? [{label: t('Edit'), icon: 'pencil', onClick: startEditing}]
      : []),
    ...(comment.value?.canDelete
      ? [
          {
            label: t('Remove'),
            icon: 'trash',
            variant: 'danger',
            disabled: mutating.value,
            onClick: deleteComment,
          },
        ]
      : []),
  ]);
  const editorId = `activity-comment-${useId()}`;
  function requestData() {
    return {
      elementType: props.elementType,
      elementId: props.elementId,
      siteId: props.siteId,
    };
  }

  const mentionTriggers = computed(() => [
    {
      trigger: '@',
      boundary: 'whitespace' as const,
      label: t('Users'),
      source: ActivityMentionSuggestionsController.url(undefined, {
        query: requestData(),
      }),
      limit: 10,
    },
  ]);

  function startEditing(): void {
    editing.value = true;
    draft.value = comment.value!.markdown ?? '';
    error.value = false;
  }

  function cancelEditing(): void {
    editing.value = false;
    draft.value = '';
    error.value = false;
  }

  async function saveComment(): Promise<void> {
    if (draft.value.trim() === '' || overage.value > 0) {
      return;
    }

    mutating.value = true;
    error.value = false;

    try {
      const event = props.event;
      const {data} =
        event === undefined
          ? await actionClient.post<ActivityCommentResponse>(store.url(), {
              ...requestData(),
              markdown: draft.value,
            })
          : await actionClient.patch<ActivityCommentResponse>(update.url(), {
              ...requestData(),
              commentId: event.id,
              markdown: draft.value,
            });

      if (event === undefined) {
        draft.value = '';
        emit('created', data.event);
      } else {
        emit('updated', data.event);
        cancelEditing();
      }
    } catch {
      error.value = true;
    } finally {
      mutating.value = false;
    }
  }

  /** Ctrl/Command + Enter posts (or saves) the comment from the editor. */
  function onDraftKeydown(event: KeyboardEvent): void {
    if (
      event.key !== 'Enter' ||
      (!event.metaKey && !event.ctrlKey) ||
      event.isComposing ||
      mutating.value ||
      overage.value > 0
    ) {
      return;
    }

    event.preventDefault();
    void saveComment();
  }

  async function deleteComment(): Promise<void> {
    if (!window.confirm(t('Remove this comment?'))) {
      return;
    }

    mutating.value = true;
    error.value = false;

    try {
      const {data} = await actionClient.delete<ActivityCommentResponse>(
        destroy.url(),
        {
          data: {
            ...requestData(),
            commentId: props.event!.id,
          },
        }
      );

      emit('updated', data.event);
      cancelEditing();
    } catch {
      error.value = true;
    } finally {
      mutating.value = false;
    }
  }
</script>

<template>
  <div
    v-if="creating || editing"
    :data-activity-comment="editing ? '' : undefined"
    :data-activity-comment-draft="creating ? '' : undefined"
  >
    <div
      class="activity-timeline__comment-editor"
      :class="{'activity-timeline__comment-edit': !creating}"
    >
      <label class="visually-hidden" :for="editorId">
        {{ creating ? t('Add a comment') : t('Edit comment') }}
      </label>
      <div
        class="activity-timeline__comment-field"
        :class="{
          'activity-timeline__comment-field--footer':
            creating || overageMessage,
        }"
      >
        <craft-markdown-field
          :id="editorId"
          class="markdown-field"
          :max-height="200"
          :rows="1"
          :placeholder="creating ? t('Add a comment…') : undefined"
          sanitize-html
          show-toolbar
          .toolbarButtons="commentToolbarButtons"
          .value="draft"
          @input="draft = ($event.target as HTMLTextAreaElement).value"
          @keydown="onDraftKeydown"
        />
        <div class="activity-timeline__comment-footer">
          <span class="activity-timeline__comment-overage" aria-live="polite">
            {{ overageMessage }}
          </span>
          <craft-button
            v-if="creating"
            type="button"
            variant="primary"
            size="small"
            :disabled="draft.trim() === '' || mutating || overage > 0"
            @click="saveComment"
          >
            {{ t('Comment') }}
          </craft-button>
        </div>
      </div>
      <craft-text-expander :for="editorId" .triggers="mentionTriggers" />
      <div v-if="error || !creating" class="activity-timeline__comment-actions">
        <p v-if="error" class="error" role="alert">
          {{
            creating
              ? t('Couldn’t post comment.')
              : t('Couldn’t update comment.')
          }}
        </p>
        <craft-button
          v-if="!creating"
          type="button"
          size="small"
          @click="cancelEditing"
        >
          {{ t('Cancel') }}
        </craft-button>
        <craft-button
          v-if="!creating"
          type="button"
          variant="primary"
          size="small"
          :disabled="draft.trim() === '' || mutating || overage > 0"
          @click="saveComment"
        >
          {{ t('Save') }}
        </craft-button>
      </div>
    </div>
  </div>

  <ActivityCommentCard
    v-else-if="event && comment && !comment.deleted"
    class="activity-timeline__comment"
    data-activity-comment
    :actor="event.actor"
    :impersonator="event.impersonator"
    :origin="event.origin"
    :description-html="event.description.html"
    :description-text="event.description.text"
    :html="comment.html"
    :occurred-at="event.occurredAt"
    :formatted-occurred-at="event.formattedOccurredAt"
    :edited="comment.edited"
  >
    <template v-if="commentActions.length" #actions>
      <ActionMenu :actions="commentActions" :label="t('Comment actions')" />
    </template>

    <p v-if="error" class="error" role="alert">
      {{ t('Couldn’t update comment.') }}
    </p>
  </ActivityCommentCard>

  <ActivityTimelineEvent
    v-else-if="event"
    :event="event"
    :element-type="elementType"
    :element-id="elementId"
    :site-id="siteId"
    :last="last ?? false"
  />
</template>

<style scoped>
  .activity-timeline__comment:not(:last-child) {
    margin-block-end: var(--c-spacing-md);
  }

  .activity-timeline__comment-actions {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-xs);
  }

  .activity-timeline__comment .error {
    margin: 0;
  }

  .activity-timeline__comment-editor craft-markdown-field {
    contain: inline-size;
    display: block;
    width: 100%;
  }

  .activity-timeline__comment-field {
    position: relative;
  }

  .activity-timeline__comment-field--footer craft-markdown-field {
    --markdown-field-footer-height: calc(
      var(--c-size-control-sm) + var(--c-spacing-sm) * 2
    );
  }

  .activity-timeline__comment-footer {
    position: absolute;
    inset-block-end: var(--c-spacing-sm);
    inset-inline-end: var(--c-spacing-sm);
    display: flex;
    align-items: center;
    gap: var(--c-spacing-md);
  }

  .activity-timeline__comment-overage {
    color: var(--c-color-danger-on-quiet);
    font-size: var(--c-text-sm);
  }

  .activity-timeline__comment-editor .activity-timeline__comment-actions {
    margin-block-start: var(--c-spacing-md);
  }

  .activity-timeline__comment-actions .error {
    margin-inline-end: auto;
  }
</style>
