<script setup lang="ts">
  /** The header button that opens and folds the AI assistant’s panel. */
  import {ButtonVariant, t} from '@craftcms/ui';
  import {useShortcut} from '@/common/shortcuts';
  import {isModifierKey, matchesKeyboardEvent} from '@tanstack/hotkeys';
  import {useEventListener} from '@vueuse/core';
  import {nextTick, onBeforeUnmount} from 'vue';
  import {registerActions} from '@/modules/command-palette/registry';
  import {firstFocusableWithin} from '@/common/utils/dom';
  import {commandShortcut} from '@/common/utils/keyShortcuts';
  import {
    ASSISTANT_PANEL_ID,
    ASSISTANT_TOGGLE_ID,
    assistantScreenPanels,
    useAssistantPanel,
  } from './assistant';

  const panel = useAssistantPanel();
  const shortcut = commandShortcut('I');

  // The palette's way in, wherever the button is. The panel itself is left
  // out of the palette's panels, since this focuses its message box.
  onBeforeUnmount(
    registerActions(() => [
      {
        id: 'ask-ai',
        label: t('Ask AI'),
        icon: 'sparkles',
        keywords: 'assistant chat',
        shortcut: 'Mod+I',
        run: panel.show,
      },
    ]).unregister
  );

  /** The panel's message box, while the panel is in the column. */
  function messageBox(): HTMLElement | null {
    return (
      assistantScreenPanels.value.sections
        .get(ASSISTANT_PANEL_ID)
        ?.querySelector<HTMLElement>('.assistant-chat__message-box') ?? null
    );
  }

  /**
   * Where Cmd+I last sent focus, to the message box, with its selection then,
   * and where focus was before, until the user types, clicks, or moves focus.
   * `pending` while the message box is on its way, such as when the chat
   * first loads.
   */
  let arrival: {
    input: HTMLElement | 'pending';
    selection?: string;
    returnTo: Element | null;
  } | null = null;

  /** The text cursor or selection in a text control, to tell whether it moved. */
  function selectionOf(element: Element | null): string {
    return element instanceof HTMLTextAreaElement ||
      element instanceof HTMLInputElement
      ? `${element.selectionStart}:${element.selectionEnd}`
      : '';
  }

  /**
   * Cmd+I (Ctrl+I elsewhere) shows the panel and moves focus to its message
   * box. A focused editor that has a use for the keys — a rich text field's
   * italic command — handles them first and cancels them, so they're left
   * alone then, as they are in the message box itself.
   */
  useShortcut(
    'Mod+I',
    () => {
      arrival = {input: 'pending', returnTo: document.activeElement};
      panel.show();
      // An open panel doesn't move focus again when it's shown.
      void nextTick(() => {
        const box = messageBox();
        if (box && !box.contains(document.activeElement)) {
          firstFocusableWithin(box)?.focus();
        }
      });
    },
    {description: () => t('Ask AI'), ignoreInputs: false}
  );

  useEventListener(document, 'focusin', (event) => {
    if (!arrival) {
      return;
    }

    if (arrival.input !== 'pending') {
      if (event.target !== arrival.input) {
        arrival = null;
      }
    } else if (
      event.target instanceof HTMLElement &&
      event.target.closest('.assistant-chat__message-box')
    ) {
      arrival.input = event.target;
      arrival.selection = selectionOf(event.target);
    }
  });

  for (const type of ['pointerdown', 'input'] as const) {
    useEventListener(window, type, () => (arrival = null), {capture: true});
  }

  /**
   * Pressing Cmd+I again, straight after it moved focus to the message box,
   * folds the panel and sends focus back where it was, ahead of the message
   * box's italic command. Once the user types or moves the cursor, the keys
   * are the italic command again.
   */
  useEventListener(
    window,
    'keydown',
    (event: KeyboardEvent) => {
      if (!arrival || isModifierKey(event.key)) {
        return;
      }

      const {input, selection, returnTo} = arrival;
      arrival = null;

      if (
        input !== document.activeElement ||
        selection !== selectionOf(input) ||
        !matchesKeyboardEvent(event, 'Mod+I')
      ) {
        return;
      }

      event.preventDefault();
      event.stopImmediatePropagation();

      const restore =
        returnTo instanceof HTMLElement &&
        returnTo !== document.body &&
        returnTo.isConnected;

      assistantScreenPanels.value.close(ASSISTANT_PANEL_ID, {
        returnFocus: !restore,
      });

      if (restore) {
        returnTo.focus();
      }
    },
    {capture: true}
  );
</script>

<template>
  <craft-button
    :id="ASSISTANT_TOGGLE_ID"
    class="assistant-toggle"
    icon
    :variant="ButtonVariant.Plain"
    type="button"
    size="small"
    :aria-expanded="panel.isOpen.value ? 'true' : 'false'"
    :aria-keyshortcuts="shortcut"
    @click="panel.toggle"
  >
    <craft-icon name="sparkles" class="assistant-toggle__icon"></craft-icon>
  </craft-button>
  <!-- Labels the icon-only button, as the Search button's tooltip does. The
    keys are left out of the name; `aria-keyshortcuts` announces them. -->
  <craft-tooltip :for="ASSISTANT_TOGGLE_ID" invoker-relation="label">
    <div class="flex gap-md items-center">
      {{ t('Ask AI') }}
      <craft-shortcut aria-hidden="true">I</craft-shortcut>
    </div>
  </craft-tooltip>
</template>

<style scoped>
  /* Matches the other icons around the content (see cp.css). */
  .assistant-toggle {
    margin-inline: -5.75px;
  }

  .assistant-toggle__icon {
    --c-icon-size: var(--cp-chrome-icon-size, 1em);
  }
</style>
