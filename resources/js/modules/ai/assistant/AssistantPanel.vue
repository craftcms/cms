<script setup lang="ts">
  /**
   * The AI assistant, mounted once in the page shell. While a chat is active,
   * it's a panel of the page's details column that stays from page to page,
   * so a reply on its way carries on as the user moves between pages.
   */
  import {t} from '@craftcms/ui';
  import {computed, nextTick, useTemplateRef, watch} from 'vue';
  import ScreenPanel from '@/common/components/ScreenPanel.vue';
  import {screenPanels} from '@/common/composables/screenPanels';
  import AssistantChat from './AssistantChat.vue';
  import AssistantConversationMenu from './AssistantConversationMenu.vue';
  import AssistantHistory from './AssistantHistory.vue';
  import {
    ASSISTANT_PANEL_ID,
    ASSISTANT_TOGGLE_ID,
    useAssistantPanel,
    useAssistantThread,
  } from './assistant';

  const panel = useAssistantPanel();
  const threadId = useAssistantThread();
  const chat = useTemplateRef<InstanceType<typeof AssistantChat>>('chat');
  const history =
    useTemplateRef<InstanceType<typeof AssistantHistory>>('history');

  // The title shows as soon as the panel does, and a new conversation’s
  // comes from its first message.
  watch(history, (component) => void component?.refresh());

  /** Whether the page has panels of its own, which the chat's can stay beside. */
  const hasPagePanels = computed(() =>
    screenPanels.panels.value.some((panel) => !panel.persistent)
  );

  // The panel is beside the page's own panels, and in the column while the
  // chat is open. On a page without panels of its own, closing the chat takes
  // it away, rail and all; the header button brings it back with the same
  // conversation.
  /** Whether the chat's panel is in the column. */
  const showsPanel = computed(() => panel.opened.value || hasPagePanels.value);

  // Closing the chat, or opening another panel instead, leaves it unopened.
  watch(screenPanels.openPanelId, (openPanelId, previous) => {
    if (previous === ASSISTANT_PANEL_ID && openPanelId !== ASSISTANT_PANEL_ID) {
      panel.opened.value = false;
    }
  });

  // Closing the chat on a page without panels of its own takes its trigger
  // away, so focus that would have gone back to it goes to the header button
  // instead.
  watch(showsPanel, async (shows) => {
    if (shows) {
      return;
    }

    await nextTick();

    if (!document.activeElement || document.activeElement === document.body) {
      document.getElementById(ASSISTANT_TOGGLE_ID)?.focus();
    }
  });

  // A chat left open before the page reloaded opens again, leaving focus
  // where the page puts it.
  if (panel.opened.value) {
    screenPanels.open(ASSISTANT_PANEL_ID, {withFocus: false});
  }

  /** Switches to a conversation, or a new one, and moves focus to its message box. */
  async function switchTo(id: string): Promise<void> {
    threadId.value = id;
    await nextTick();
    chat.value?.focus();
  }

  function newChat(): Promise<void> {
    return switchTo(crypto.randomUUID());
  }

  /** Starts a new conversation in place of the deleted one. */
  async function onDeleted(): Promise<void> {
    await newChat();
    await history.value?.refresh();
  }
</script>

<template>
  <ScreenPanel
    v-if="showsPanel"
    :id="ASSISTANT_PANEL_ID"
    :label="t('Ask AI')"
    icon="sparkles"
    :order="50"
    persistent
    scrolls-itself
    :initial-focus="() => chat?.focusTarget()"
  >
    <AssistantChat
      ref="chat"
      :key="threadId"
      :thread-id="threadId"
      @finish="history?.refresh()"
    />
    <template #header-actions>
      <AssistantHistory
        ref="history"
        :thread-id="threadId"
        @select="switchTo"
        @new="newChat"
      />
      <AssistantConversationMenu
        v-if="history?.stored"
        :thread-id="threadId"
        :title="history.title"
        @renamed="history?.refresh()"
        @deleted="onDeleted"
      />
    </template>
  </ScreenPanel>
</template>
