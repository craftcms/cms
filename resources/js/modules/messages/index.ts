import './components/cp-messages';
import {
  carryOverRecentMessages,
  installLegacyShim,
  showMessage,
  restoreCarriedMessages,
  type CpMessage,
} from './messages';

export * from './messages';

/**
 * Dispatch this on `window` with a message as the `detail` to show it
 * from code that can't import this module (web components, plugins):
 *
 *     window.dispatchEvent(new CustomEvent('craft-message', {
 *       detail: {type: 'success', message: 'Saved.'},
 *     }));
 */
export const MESSAGE_EVENT = 'craft-message';

/**
 * Names the inline outlet that messages flashed while handling a request
 * belong to — `Flash::TARGET_HEADER` on the server. Spread the result into an
 * Inertia visit's `headers`.
 */
export function messageTargetHeaders(target: string): Record<string, string> {
  return {'X-Craft-Message-Target': target};
}

interface MessageQueue {
  push(...messages: CpMessage[]): number;
}

declare global {
  interface Window {
    /**
     * Messages the Twig layout flashed before this module loaded. Once it has,
     * pushing to it shows them straight away.
     */
    CraftMessageQueue?: CpMessage[] | MessageQueue;
  }
}

let installed = false;

/**
 * Starts showing messages: installs the legacy shim, re-shows messages carried
 * over from the previous page, and drains `window.CraftMessageQueue`.
 * Call once `<cp-messages>` exists.
 */
export function installMessages(): void {
  if (installed) {
    return;
  }

  installed = true;
  installLegacyShim();
  restoreCarriedMessages();

  const pending = Array.isArray(window.CraftMessageQueue)
    ? window.CraftMessageQueue
    : [];

  window.CraftMessageQueue = {
    push(...messages) {
      messages.forEach(showMessage);
      return messages.length;
    },
  };

  pending.forEach(showMessage);

  window.addEventListener(MESSAGE_EVENT, (event) => {
    showMessage((event as CustomEvent<CpMessage>).detail);
  });
  window.addEventListener('pagehide', carryOverRecentMessages);
}
