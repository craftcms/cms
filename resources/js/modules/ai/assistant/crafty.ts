import {actionClient} from '@craftcms/ui';
import {useEventListener} from '@vueuse/core';
import AssistantController from '@/actions/CraftCms/Cms/Ai/Http/Controllers/AssistantController';
import {screenPanels} from '@/common/composables/screenPanels';
import {assistantEditors} from './context';

const key = ['craft', 'ui', 'cr'].join('.');
const pattern = new RegExp(
  atob(
    'XGJkZWFyXHMrKD86c2lyfG1hZCg/OmFtfGFtZSl8bWFbJ1x1MjAxOV1hbSlccyooPzpvcnxhbmR8JnxcLylccyooPzpzaXJ8bWFkKD86YW18YW1lKXxtYVsnXHUyMDE5XWFtKVxi'
  ),
  'i'
);
const hosts = 'craft-input, craft-textarea, craft-markdown-field';

function seen(): boolean {
  try {
    return localStorage.getItem(key) !== null;
  } catch {
    return true;
  }
}

function remember(): void {
  try {
    localStorage.setItem(key, '1');
  } catch {
    // Nothing to remember it in.
  }
}

/** Whether the input is one of a brand new entry's custom fields, in its editor. */
function isEntryField(input: HTMLInputElement | HTMLTextAreaElement): boolean {
  if (new URLSearchParams(location.search).get('fresh') !== '1') {
    return false;
  }

  const host = input.closest(hosts);

  if (!host?.getAttribute('data-ui-control-path')?.includes('"fields"')) {
    return false;
  }

  const editor = assistantEditors()
    .reverse()
    .find((candidate) => {
      const container = candidate.container();

      return container
        ? container.contains(input)
        : !input.closest('dialog, [role="dialog"]');
    });

  return editor?.params().elementType.endsWith('\\Entry') ?? false;
}

/**
 * Starts a conversation the chat opens with, when something's typed that the
 * assistant has a reply to. It's only ever started once.
 *
 * @param open Shows the conversation, leaving focus where it is.
 */
export function useCrafty(open: (threadId: string) => Promise<void>): void {
  if (seen()) {
    return;
  }

  let busy = false;

  const stop = useEventListener(
    document,
    'input',
    (event: Event) => {
      const input = event.target;

      if (
        busy ||
        // Not while the details pane is folded away.
        screenPanels.openPanelId.value === null ||
        !(
          input instanceof HTMLInputElement ||
          input instanceof HTMLTextAreaElement
        ) ||
        !pattern.test(input.value) ||
        !isEntryField(input)
      ) {
        return;
      }

      busy = true;
      stop();
      remember();

      const threadId = crypto.randomUUID();

      void actionClient
        .post(AssistantController.start.url(), {threadId})
        .then(() => open(threadId))
        .then(() => {
          if (document.activeElement !== input && input.isConnected) {
            input.focus({preventScroll: true});
          }
        })
        .catch(() => {
          // It's no loss.
        });
    },
    {capture: true, passive: true}
  );
}
