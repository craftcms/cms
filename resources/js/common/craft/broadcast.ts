import {ConfigService} from '@craftcms/ui/services/Config';

export interface BroadcastMessage {
  event: string;
  [key: string]: unknown;
}

export interface OnBroadcastOptions {
  /**
   * Also hear messages this tab sends with {@link broadcast}, for listeners
   * that should react the same way wherever the change happened.
   */
  includeSelf?: boolean;
}

let channel: BroadcastChannel | null | undefined;
const thisTab = new EventTarget();

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
 * Tells other control panel tabs, listeners on `Craft.messageReceiver` in
 * this one, and this tab's {@link onBroadcast} listeners that pass
 * `includeSelf`, that something changed.
 */
export function broadcast(message: BroadcastMessage): void {
  getChannel()?.postMessage(message);
  thisTab.dispatchEvent(new MessageEvent('message', {data: message}));
}

/**
 * Listens for an event broadcast from other tabs. Messages this tab sends
 * with {@link broadcast} are only heard with `includeSelf`.
 */
export function onBroadcast(
  event: string,
  handler: (message: BroadcastMessage) => void,
  options: OnBroadcastOptions = {}
): () => void {
  const listener = (ev: Event) => {
    const data = (ev as MessageEvent<BroadcastMessage>).data;
    if (data?.event === event) {
      handler(data);
    }
  };

  getChannel()?.addEventListener('message', listener);
  if (options.includeSelf) {
    thisTab.addEventListener('message', listener);
  }

  return () => {
    getChannel()?.removeEventListener('message', listener);
    thisTab.removeEventListener('message', listener);
  };
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
