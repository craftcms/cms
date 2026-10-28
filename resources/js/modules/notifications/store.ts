import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {t} from '@craftcms/ui';
import {markRead} from '@actions/NotificationsController';

type NotificationData = CraftCms.Cms.Cp.Data.NotificationData;

/**
 * The current user's notifications, shared by everything that shows them —
 * the global header's notification center and the command palette — so one
 * marked as read in either shows as read in both. Each page brings the
 * server's latest; until an Inertia page sets them, they come from
 * `Craft.notifications`.
 */
let notifications: NotificationData[] | null = null;

const listeners = new Set<() => void>();

export function getNotifications(): NotificationData[] {
  return notifications ?? window.Craft?.notifications ?? [];
}

export function setNotifications(value: NotificationData[]): void {
  if (value === notifications) {
    return;
  }

  notifications = value;
  listeners.forEach((listener) => listener());
}

/** Calls `listener` whenever the notifications change, until the returned function is called. */
export function onNotificationsChange(listener: () => void): () => void {
  listeners.add(listener);

  return () => listeners.delete(listener);
}

function setUnread(ids: string[], unread: boolean): void {
  setNotifications(
    getNotifications().map((notification) =>
      ids.includes(notification.id) ? {...notification, unread} : notification
    )
  );
}

/** Marks notifications as read right away, and puts them back if the server can't. */
export async function markNotificationsRead(ids: string[]): Promise<void> {
  const changedIds = getNotifications()
    .filter(
      (notification) => notification.unread && ids.includes(notification.id)
    )
    .map((notification) => notification.id);

  if (!changedIds.length) {
    return;
  }

  setUnread(changedIds, false);

  try {
    await actionClient.post(markRead().url, {ids: changedIds});
  } catch {
    setUnread(changedIds, true);
    Craft.cp?.displayError?.(t('Couldn’t mark notifications as read.'));
  }
}

/** Goes to a notification's URL, through Inertia when it's a control panel page. */
export function navigateToNotification(url: string): void {
  if (new URL(url).origin !== window.location.origin) {
    window.location.assign(url);

    return;
  }

  const proceed = window.dispatchEvent(
    new CustomEvent('action:redirect', {
      cancelable: true,
      detail: {url},
    })
  );

  if (proceed) {
    window.location.assign(url);
  }
}
