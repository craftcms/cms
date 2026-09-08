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
  import type {ActionItem} from '@/common/types';
  import type {ActivityEvent} from '@/modules/activity/composables/useActivityTimeline';
  import ActivityTimelineActor from './ActivityTimelineActor.vue';
  import '../../markdown-field/markdown-field';

  interface ActivityCommentResponse {
    event: ActivityEvent;
  }

  const props = defineProps<{
    event?: ActivityEvent;
    elementType: string;
    elementId: number | null;
    siteId: number | null;
  }>();

  const emit = defineEmits<{
    created: [event: ActivityEvent];
    updated: [event: ActivityEvent];
  }>();

  const editing = shallowRef(false);
  const draft = shallowRef('');
  const mutating = shallowRef(false);
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
  const toolbarButtons = [
    'bold',
    'italic',
    'code',
    'h3',
    'quote',
    'unordered-list',
    'ordered-list',
    'check-list',
    'link',
  ];

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

  function sentenceFragment(text: string | null): string {
    return text === null
      ? ''
      : text.charAt(0).toLocaleLowerCase() + text.slice(1);
  }

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
    if (draft.value.trim() === '') {
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
      <craft-markdown-field
        :id="editorId"
        class="markdown-field"
        :rows="creating ? 3 : 4"
        :placeholder="creating ? t('Add a comment…') : undefined"
        sanitize-html
        show-toolbar
        .toolbarButtons="toolbarButtons"
        .value="draft"
        @input="draft = ($event.target as HTMLTextAreaElement).value"
      />
      <craft-text-expander :for="editorId" .triggers="mentionTriggers" />
      <div class="activity-timeline__comment-actions">
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
          type="button"
          variant="primary"
          :size="creating ? 'medium' : 'small'"
          :disabled="draft.trim() === '' || mutating"
          @click="saveComment"
        >
          {{ creating ? t('Comment') : t('Save') }}
        </craft-button>
      </div>
    </div>
  </div>

  <craft-card
    v-else-if="event && comment && !comment.deleted"
    class="activity-timeline__comment"
    data-activity-comment
  >
    <div slot="label" class="activity-timeline__comment-heading">
      <ActivityTimelineActor
        :actor="event.actor"
        :impersonator="event.impersonator"
      />
      <span
        v-if="event?.description.html"
        class="activity-timeline__comment-description"
        v-html="event.description.html"
      />
      <span v-else class="activity-timeline__comment-description">
        {{ sentenceFragment(event?.description.text ?? null) }}
      </span>
    </div>

    <ActionMenu
      v-if="commentActions.length"
      slot="actions"
      :actions="commentActions"
      :label="t('Comment actions')"
    />

    <div class="activity-timeline__comment-body" v-html="comment.html" />

    <p v-if="error" class="error" role="alert">
      {{ t('Couldn’t update comment.') }}
    </p>

    <div v-if="event" slot="footer" class="activity-timeline__comment-footer">
      <span v-if="comment.edited">{{ t('Edited') }}</span>
      <time
        :datetime="event.occurredAt"
        :title="event.formattedOccurredAt.full"
      >
        {{ event.formattedOccurredAt.time }}
      </time>
    </div>
  </craft-card>
</template>

<style scoped>
  .activity-timeline__comment-description {
    margin-inline-start: 0.25em;
  }

  .activity-timeline__comment-footer {
    display: flex;
    width: 100%;
    color: var(--c-text-quiet);
    font-size: var(--c-text-xs);
  }

  .activity-timeline__comment-footer time {
    margin-inline-start: auto;
    white-space: nowrap;
  }

  .activity-timeline__comment-actions {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-xs);
  }

  .activity-timeline__comment-body :deep(> :last-child),
  .activity-timeline__comment .error {
    margin: 0;
  }

  .activity-timeline__comment-editor craft-markdown-field {
    contain: inline-size;
    display: block;
    width: 100%;
  }

  .activity-timeline__comment-editor .activity-timeline__comment-actions {
    justify-content: flex-end;
    margin-block-start: var(--c-spacing-xs);
  }

  .activity-timeline__comment-actions .error {
    margin-inline-end: auto;
  }
</style>
