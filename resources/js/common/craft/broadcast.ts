import {ConfigService} from '@craftcms/ui/services/Config';

export interface BroadcastMessage {
  event: string;
  [key: string]: unknown;
}

let channel: BroadcastChannel | null | undefined;

/**
 * Tells other control panel tabs, and listeners on `Craft.messageReceiver` in
 * this one, that something changed.
 */
export function broadcast(message: BroadcastMessage): void {
  if (channel === undefined) {
    channel =
      typeof BroadcastChannel === 'undefined'
        ? null
        : new BroadcastChannel(
            `CraftCMS:${ConfigService.getInstance().get('appId', '')}`
          );
  }

  channel?.postMessage(message);
}
