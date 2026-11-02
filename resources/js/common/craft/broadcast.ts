import {ConfigService} from '@craftcms/ui/services/Config';

export interface BroadcastMessage {
  event: string;
  [key: string]: unknown;
}

let channel: BroadcastChannel | null | undefined;

function getChannel(): BroadcastChannel | null {
  if (channel === undefined) {
    channel =
      typeof BroadcastChannel === 'undefined'
        ? null
        : new BroadcastChannel(
            `CraftCMS:${ConfigService.getInstance().get('appId', '')}`
          );
  }

  return channel;
}

/**
 * Tells other control panel tabs, and listeners on `Craft.messageReceiver` in
 * this one, that something changed.
 */
export function broadcast(message: BroadcastMessage): void {
  getChannel()?.postMessage(message);
}

/**
 * Listens for an event broadcast from other tabs. Messages this tab sends
 * with {@link broadcast} aren't heard.
 */
export function onBroadcast(
  event: string,
  handler: (message: BroadcastMessage) => void
): () => void {
  const listener = (ev: MessageEvent<BroadcastMessage>) => {
    if (ev.data?.event === event) {
      handler(ev.data);
    }
  };

  getChannel()?.addEventListener('message', listener);

  return () => getChannel()?.removeEventListener('message', listener);
}

/**
 * Broadcasts the `detail` of each `craft:broadcast` event, for code that
 * can't import this module, such as JS the server flashes to the next page.
 */
export function relayBroadcastEvents(): void {
  window.addEventListener('craft:broadcast', (event) => {
    const message = (event as CustomEvent<BroadcastMessage>).detail;
    if (message?.event) {
      broadcast(message);
    }
  });
}
