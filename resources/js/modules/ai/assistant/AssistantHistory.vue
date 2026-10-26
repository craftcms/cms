<script setup lang="ts">
  /**
   * The current conversation’s title, as a menu of the user’s conversations,
   * grouped by day, which can switch to one of them or start a new one.
   */
  import {t} from '@craftcms/ui';
  import {computed, shallowRef, watch} from 'vue';
  import {
    fetchConversations,
    formatAge,
    type AssistantConversationGroup,
  } from './history';

  const props = defineProps<{
    threadId: string;
  }>();

  const emit = defineEmits<{
    select: [threadId: string];
    new: [];
  }>();

  const groups = shallowRef<AssistantConversationGroup[]>([]);
  /** When the ages were worked out, so they’re current each time the menu opens. */
  const now = shallowRef(new Date());

  /** The current conversation, once it’s stored. */
  const current = computed(
    () =>
      groups.value
        .flatMap((group) => group.conversations)
        .find(({id}) => id === props.threadId) ?? null
  );
  const title = computed(() => current.value?.title ?? t('New chat'));
  /** Whether the current conversation is stored, so it can be renamed or deleted. */
  const isStored = computed(() => current.value !== null);

  /** Fetches the conversations again. A failure keeps the ones from before. */
  async function refresh(): Promise<void> {
    try {
      groups.value = await fetchConversations();
    } catch {
      // The history is a convenience; the chat works without it.
    }

    now.value = new Date();
  }

  watch(
    () => props.threadId,
    () => void refresh()
  );

  defineExpose({refresh, title, stored: isStored});
</script>

<template>
  <craft-action-menu class="assistant-history" @craft-show="refresh()">
    <craft-button
      slot="invoker"
      type="button"
      variant="plain"
      size="small"
      icon="chevron-down"
      icon-position="suffix"
      class="assistant-history__invoker"
      :aria-label="t('{title}, chat history', {title})"
    >
      <span class="assistant-history__title">{{ title }}</span>
    </craft-button>
    <div slot="content">
      <craft-action-item icon="plus" @click="emit('new')">
        {{ t('New chat') }}
      </craft-action-item>
      <hr v-if="groups.length" />
      <template v-for="group in groups" :key="group.heading">
        <div role="presentation" class="assistant-history__heading">
          {{ group.heading }}
        </div>
        <craft-action-item
          v-for="conversation in group.conversations"
          :key="conversation.id"
          :current="conversation.id === threadId || undefined"
          :active="conversation.id === threadId || undefined"
          @click="emit('select', conversation.id)"
        >
          <span class="assistant-history__item-title">{{
            conversation.title
          }}</span>
          <span slot="suffix" class="assistant-history__meta">
            <span v-if="conversation.id === threadId">{{ t('Current') }}</span>
            <time :datetime="conversation.updatedAt">
              <span aria-hidden="true">{{
                formatAge(new Date(conversation.updatedAt), now).short
              }}</span>
              <span class="sr-only">{{
                formatAge(new Date(conversation.updatedAt), now).long
              }}</span>
            </time>
          </span>
        </craft-action-item>
      </template>
    </div>
  </craft-action-menu>
</template>

<style scoped>
  .assistant-history {
    min-inline-size: 0;
  }

  .assistant-history__invoker {
    max-inline-size: 100%;
  }

  .assistant-history__title {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-weight: 600;
  }

  .assistant-history__heading {
    margin-block-start: var(--c-spacing-md);
    padding-block: var(--c-spacing-xs);
    padding-inline: calc(var(--c-spacing-sm) * 2 + 1px) var(--c-spacing-md);
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
    font-weight: 600;
  }

  .assistant-history__item-title {
    display: block;
    max-inline-size: 18rem;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .assistant-history__meta {
    display: flex;
    flex-shrink: 0;
    white-space: nowrap;
    gap: var(--c-spacing-sm);
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
  }
</style>
