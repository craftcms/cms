const STORAGE_KEY = 'dismissedTips';

/**
 * Tips a user has dismissed, by layout element UID.
 *
 * Kept in local storage under the same key the legacy editor used, so a tip
 * dismissed on either editor stays dismissed on both.
 */
export function dismissedTipUids(): Array<string> {
  try {
    const stored = window.localStorage.getItem(STORAGE_KEY);
    const parsed = stored ? JSON.parse(stored) : [];

    return Array.isArray(parsed)
      ? parsed.filter(
          (uid): uid is string => Object(uid).constructor === String
        )
      : [];
  } catch {
    // Private browsing, quota, or a malformed value — treat as nothing dismissed.
    return [];
  }
}

export function isTipDismissed(uid: string | null | undefined): boolean {
  return uid ? dismissedTipUids().includes(uid) : false;
}

export function dismissTip(uid: string): void {
  const uids = dismissedTipUids();

  if (uids.includes(uid)) {
    return;
  }

  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify([...uids, uid]));
  } catch {
    // Dismissal is a convenience; failing to persist it isn't worth surfacing.
  }
}
