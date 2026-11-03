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
  import {useCrafty} from './crafty';

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

  useCrafty(async (id) => {
    threadId.value = id;
    panel.opened.value = true;
    screenPanels.open(ASSISTANT_PANEL_ID, {withFocus: false});
    await nextTick();
    await history.value?.refresh();
  });

  function newChat(): Promise<void> {
    return switchTo(crypto.randomUUID());
  }

  /**
   * Shows the conversation’s title as it is once a reply has finished. A new
   * conversation’s generated title is saved by a queued job, so it’s looked
   * for again a few times, until its title changes.
   */
  async function onFinish(first: boolean): Promise<void> {
    await history.value?.refresh();

    const id = threadId.value;
    const title = history.value?.title;

    for (const delay of first ? [1000, 2000, 4000, 8000] : []) {
      await new Promise((resolve) => setTimeout(resolve, delay));

      if (threadId.value !== id || history.value?.title !== title) {
        return;
      }

      await history.value?.refresh();
    }
  }

  /** Starts a new conversation in place of the deleted one. */
  async function onDeleted(): Promise<void> {
    await newChat();
    await history.value?.refresh();
  }
</script>

<template>
  <!-- Always the last panel, after any a page or plugin adds. -->
  <ScreenPanel
    v-if="showsPanel"
    :id="ASSISTANT_PANEL_ID"
    :label="t('Ask AI')"
    icon="sparkles"
    :order="Infinity"
    persistent
    scrolls-itself
    unlisted-in-palette
    :initial-focus="() => chat?.focusTarget()"
  >
    <AssistantChat
      ref="chat"
      :key="threadId"
      :thread-id="threadId"
      @finish="onFinish"
    />
    <template #header-actions>
      <AssistantHistory
        ref="history"
        :thread-id="threadId"
        @select="switchTo"
        @new="newChat"
      />
      <AssistantConversationMenu
        :thread-id="threadId"
        :title="history?.title ?? ''"
        :stored="!!history?.stored"
        @renamed="history?.refresh()"
        @deleted="onDeleted"
      />
    </template>
  </ScreenPanel>
</template>
