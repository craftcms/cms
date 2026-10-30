<script setup lang="ts">
  /** The header button that opens and folds the AI assistant’s panel. */
  import {ButtonVariant, t} from '@craftcms/ui';
  import {useEventListener} from '@vueuse/core';
  import {nextTick, onBeforeUnmount} from 'vue';
  import {registerActions} from '@/modules/command-palette/registry';
  import {screenPanels} from '@/common/composables/screenPanels';
  import {firstFocusableWithin} from '@/common/utils/dom';
  import {commandShortcut} from '@/common/utils/keyShortcuts';
  import {
    ASSISTANT_PANEL_ID,
    ASSISTANT_TOGGLE_ID,
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
        run: panel.show,
      },
    ]).unregister
  );

  /** The panel's message box, while the panel is in the column. */
  function messageBox(): HTMLElement | null {
    return (
      screenPanels.sections
        .get(ASSISTANT_PANEL_ID)
        ?.querySelector<HTMLElement>('.assistant-chat__message-box') ?? null
    );
  }

  /**
   * Cmd+I (Ctrl+I elsewhere) shows the panel and moves focus to its message
   * box, or folds the panel when focus is already there, sending it back to
   * the button. A focused editor that has a use for the keys — a rich text
   * field's italic command — handles them first and cancels them, so they're
   * left alone then.
   */
  useEventListener(window, 'keydown', (event: KeyboardEvent) => {
    if (
      event.key.toLowerCase() !== 'i' ||
      event.shiftKey ||
      event.altKey ||
      !(event.metaKey || event.ctrlKey) ||
      event.defaultPrevented
    ) {
      return;
    }

    event.preventDefault();

    if (panel.isOpen.value && messageBox()?.contains(document.activeElement)) {
      screenPanels.close(ASSISTANT_PANEL_ID);
      return;
    }

    panel.show();
    // An open panel doesn't move focus again when it's shown.
    void nextTick(() => {
      const box = messageBox();
      if (box && !box.contains(document.activeElement)) {
        firstFocusableWithin(box)?.focus();
      }
    });
  });
</script>

<template>
  <craft-button
    :id="ASSISTANT_TOGGLE_ID"
    icon
    :variant="ButtonVariant.Plain"
    type="button"
    size="small"
    :aria-expanded="panel.isOpen.value ? 'true' : 'false'"
    :aria-keyshortcuts="shortcut"
    @click="panel.toggle"
  >
    <craft-icon name="sparkles"></craft-icon>
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
