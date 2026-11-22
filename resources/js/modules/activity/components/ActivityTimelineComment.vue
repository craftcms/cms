<script setup lang="ts">
  import {actionClient, t} from '@craftcms/ui';
  import {computed, nextTick, shallowRef, useId, useTemplateRef} from 'vue';
  import {
    destroy,
    store,
    update,
  } from '@actions/Elements/ActivityCommentsController';
  import ActivityMentionSuggestionsController from '@actions/Elements/ActivityMentionSuggestionsController';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import CommentField from '@/common/components/CommentField.vue';
  import MarkdownHtml from '@/common/components/MarkdownHtml.vue';
  import MessageBubble from '@/common/components/MessageBubble.vue';
  import {
    COMMENT_MAX_LENGTH,
    useCharacterLimit,
  } from '@/common/composables/useCharacterLimit';
  import {usePhotoColors} from '@/common/composables/usePhotoColors';
  import type {ActionItem} from '@/common/types';
  import type {ActivityEvent} from '@/modules/activity/composables/useActivityTimeline';
  import ActivityTimelineEvent from './ActivityTimelineEvent.vue';
  import {commandShortcut} from '@/common/utils/keyShortcuts';

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

  const photoColors = usePhotoColors(() => props.event?.actor.photoColors);
  const eventItem =
    useTemplateRef<InstanceType<typeof ActivityTimelineEvent>>('eventItem');
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
  const mentionHint = t('Type {user} to mention someone.', {
    user: '<kbd>@</kbd>',
  });
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

  /** Focus moves into the editor, and back to the actions menu after it. */
  async function focusIn(selector: string): Promise<void> {
    await nextTick();
    (eventItem.value?.$el as HTMLElement | undefined)
      ?.querySelector<HTMLElement>(selector)
      ?.focus();
  }

  function startEditing(): void {
    editing.value = true;
    draft.value = comment.value!.markdown ?? '';
    error.value = false;
    void focusIn('textarea');
  }

  /**
   * Esc in the text cancels, unless it's closing the mention suggestions.
   * Checked while capturing, before the suggestions close themselves.
   */
  function onEditorEscape(event: KeyboardEvent): void {
    const target = event.target;
    if (
      !(target instanceof HTMLTextAreaElement) ||
      target.hasAttribute('aria-activedescendant')
    ) {
      return;
    }

    event.preventDefault();
    cancelEditing();
  }

  function cancelEditing(): void {
    editing.value = false;
    draft.value = '';
    error.value = false;
    void focusIn('[slot="invoker"]');
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

  const postShortcut = commandShortcut('Enter');

  function onDraftSubmit(): void {
    if (!mutating.value && overage.value <= 0) {
      void saveComment();
    }
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
  <div v-if="creating" data-activity-comment-draft>
    <CommentField
      :id="editorId"
      v-model="draft"
      :label="t('Add a comment')"
      :overage-message="overageMessage"
      :submit-label="t('Comment')"
      :submit-disabled="mutating || overage > 0"
      @submit="onDraftSubmit"
    >
      <template #info>
        <!-- A translated string with fixed markup for the key. -->
        <span v-html="mentionHint"></span>
      </template>
    </CommentField>
    <craft-text-expander :for="editorId" .triggers="mentionTriggers" />
    <p v-if="error" class="error" role="alert">
      {{ t('Couldn’t post comment.') }}
    </p>
  </div>

  <ActivityTimelineEvent
    v-else-if="event && comment && !comment.deleted"
    ref="eventItem"
    data-activity-comment
    :event="event"
    :element-type="elementType"
    :element-id="elementId"
    :site-id="siteId"
    :last="last ?? false"
  >
    <template
      v-if="comment.edited || (commentActions.length && !editing)"
      #meta-start
    >
      <span v-if="comment.edited">{{ t('Edited') }}</span>
      <ActionMenu
        v-if="commentActions.length && !editing"
        :actions="commentActions"
        :label="t('Comment actions')"
      />
    </template>

    <div v-if="editing" @keydown.esc.capture="onEditorEscape">
      <CommentField
        :id="editorId"
        v-model="draft"
        :label="t('Edit comment')"
        :overage-message="overageMessage"
        :submit-button="false"
        @submit="onDraftSubmit"
      >
        <template #info>
          <!-- A translated string with fixed markup for the key. -->
          <span v-html="mentionHint"></span>
        </template>
        <template #footer>
          <div class="activity-timeline__comment-actions">
            <craft-button
              class="activity-timeline__comment-button"
              type="button"
              @click="cancelEditing"
            >
              {{ t('Cancel') }}
            </craft-button>
            <craft-button
              class="activity-timeline__comment-button"
              type="button"
              variant="primary"
              .disabled="draft.trim() === '' || mutating || overage > 0"
              :aria-keyshortcuts="postShortcut"
              @click="saveComment"
            >
              {{ t('Save') }}
            </craft-button>
          </div>
        </template>
      </CommentField>
      <craft-text-expander :for="editorId" .triggers="mentionTriggers" />
    </div>

    <MessageBubble
      v-else
      class="activity-timeline__comment"
      :style="photoColors"
    >
      <MarkdownHtml :html="comment.html" />
    </MessageBubble>

    <p v-if="error" class="error" role="alert">
      {{ t('Couldn’t update comment.') }}
    </p>
  </ActivityTimelineEvent>

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
  /* Pills as tall as the comment form's round send button. */
  .activity-timeline__comment-button {
    --c-button-height: var(--c-size-control-sm);
    --c-button-radius: 9999px;
    --c-button-spacing-inline: var(--c-spacing-md);
  }

  .activity-timeline__comment-actions {
    display: flex;
    gap: var(--c-spacing-sm);
  }

  .error {
    margin-block: var(--c-spacing-sm) 0;
    margin-inline: 0;
    color: var(--c-color-danger-on-quiet);
    font-size: var(--c-text-sm);
  }
</style>
