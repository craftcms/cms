import {isCancel, isHttpError} from '@craftcms/ui';
import {createGlobalState} from '@vueuse/core';
import {usePage} from '@inertiajs/vue3';
import {computed} from 'vue';
import {screenPanels} from '@/common/composables/screenPanels';
import type {CraftData} from '@/common/composables/useCraftData';
import {useSessionStorage} from '@/common/composables/useStorage';

/** The id of the AI assistant’s panel among the shell’s details panels. */
export const ASSISTANT_PANEL_ID = 'ai-assistant';

/** The id of the header button that opens the AI assistant. */
export const ASSISTANT_TOGGLE_ID = 'cp-assistant-toggle';

/** An error response from the assistant’s endpoint, with the message the server gave. */
export class AssistantRequestError extends Error {}

/**
 * The AI assistant’s panel, one of the shell’s details panels. It’s there
 * while the chat is open, and beside a page’s own panels. The chat stays open
 * from page to page, and after a full reload, for the browser tab. One state
 * for the whole control panel, since the toggle and the panel are rendered
 * apart.
 */
export const useAssistantPanel = createGlobalState(() => {
  /** Whether it was opened in this browser tab, and hasn’t been closed. */
  const opened = useSessionStorage('aiAssistant.opened', false);
  const isOpen = computed(
    () => screenPanels.openPanelId.value === ASSISTANT_PANEL_ID
  );

  function show(): void {
    opened.value = true;
    screenPanels.open(ASSISTANT_PANEL_ID);
  }

  return {
    opened,
    isOpen,
    /** Opens the panel, adding it if needed, and moves focus to its message box. */
    show,
    /** Opens the panel, or folds it while keeping focus where it is. */
    toggle(): void {
      if (isOpen.value) {
        screenPanels.close(ASSISTANT_PANEL_ID, {returnFocus: false});
      } else {
        show();
      }
    },
  };
});

/** The current conversation’s id, kept for the browser tab, for each user. */
export const useAssistantThread = createGlobalState(() => {
  const userId =
    usePage<{craft?: CraftData}>().props.craft?.currentUser?.id ?? 0;

  return useSessionStorage(`aiAssistant.thread.${userId}`, crypto.randomUUID());
});

/**
 * An error from a request to one of the assistant’s actions, with the
 * server’s message, if it gave one.
 */
export function requestError(error: unknown): unknown {
  if (!isHttpError<{message?: string} | string>(error) || isCancel(error)) {
    return error;
  }

  const data = error.response?.data;

  return new AssistantRequestError(
    (typeof data === 'object' && data?.message) || error.message,
    {cause: error}
  );
}
