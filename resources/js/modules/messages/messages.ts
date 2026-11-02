/**
 * The one path every control panel message takes to the screen.
 *
 * Server-flashed messages (Inertia props and the Twig layout), JSON response
 * bodies, Vue code (`useMessages()`) and legacy `Craft.cp.display*()` calls all
 * end up in {@link showMessage}, which
 *
 * - drops a message it has already shown (by `id`, remembered for the rest of
 *   the browser session, so a history visit or a reload doesn't replay it),
 * - hands a message with a `target` to the inline outlet registered under
 *   that name ({@link registerMessageOutlet}), and
 * - shows everything else as a {@link Notification} in `<cp-messages>`.
 */

import {t} from '@craftcms/ui/utilities/translate';
import {Notification} from './notification';

export type MessageType = 'notice' | 'success' | 'error';

export interface MessageSettings {
  icon?: string;
  iconLabel?: string;
  details?: string | HTMLElement;
  persist?: boolean;
  /** The server-assigned id. JSON responses pass it here so the legacy `displaySuccess(message, settings)` idiom dedupes too. */
  id?: string;
  [key: string]: unknown;
}

export interface CpMessage {
  id?: string;
  type: MessageType;
  message: string;
  settings?: MessageSettings;
  /** Names the inline outlet the message belongs to. Without one, or when the outlet isn't on the page, it goes to the default display. */
  target?: string | null;
}

export type MessageOutlet = (message: CpMessage) => void;

/** A message shown in the default display. */
export interface CpNotification {
  type: string;
  message: string;
  closing: boolean;
  element: HTMLElement | null;
  closeButton: HTMLElement | null;
  close(): void;
  on(event: string, handler: () => void): void;
}

type DisplayNotification = (
  this: unknown,
  type: unknown,
  message?: string,
  settings?: MessageSettings
) => unknown;

const SEEN_STORAGE_KEY = 'Craft-messages.seen';
const CARRY_STORAGE_KEY = 'Craft-messages.carry';
const SEEN_TTL = 24 * 60 * 60 * 1000;
const SEEN_LIMIT = 200;
const CARRY_WINDOW = 3000;

const DEFAULT_ICONS: Record<MessageType, [string, string]> = {
  notice: ['info', 'Notice'],
  success: ['check', 'Success'],
  error: ['alert', 'Error'],
};

const outlets = new Map<string, MessageOutlet[]>();
const visible = new Set<{
  message: CpMessage;
  instance: CpNotification;
  shownAt: number;
}>();

let renderLegacy: DisplayNotification | null = null;

function readStorage<T>(key: string, fallback: T): T {
  try {
    const value = sessionStorage.getItem(key);
    return value ? (JSON.parse(value) as T) : fallback;
  } catch {
    return fallback;
  }
}

function writeStorage(key: string, value: unknown): void {
  try {
    sessionStorage.setItem(key, JSON.stringify(value));
  } catch {
    // Storage is unavailable or full; dedupe falls back to this page only.
  }
}

const seenInPage = new Set<string>();

function hasSeen(id: string): boolean {
  if (seenInPage.has(id)) {
    return true;
  }

  const seen = readStorage<Array<[string, number]>>(SEEN_STORAGE_KEY, []);
  return seen.some(([seenId]) => seenId === id);
}

function markSeen(id: string): void {
  seenInPage.add(id);

  const now = Date.now();
  const seen = readStorage<Array<[string, number]>>(SEEN_STORAGE_KEY, [])
    .filter(([seenId, at]) => seenId !== id && now - at < SEEN_TTL)
    .slice(-(SEEN_LIMIT - 1));
  seen.push([id, now]);
  writeStorage(SEEN_STORAGE_KEY, seen);
}

function idOf(message: CpMessage): string | undefined {
  return message.id ?? message.settings?.id;
}

function isOnScreen(instance: CpNotification): boolean {
  // Still being built counts: it'll be on screen in a moment
  return (
    !instance.closing && (!instance.element || instance.element.isConnected)
  );
}

function findVisibleDuplicate(message: CpMessage): CpNotification | null {
  for (const entry of visible) {
    if (!isOnScreen(entry.instance)) {
      visible.delete(entry);
      continue;
    }

    if (
      entry.message.type === message.type &&
      entry.message.message === message.message
    ) {
      return entry.instance;
    }
  }

  return null;
}

/**
 * Shows a message in the default display, unless the same message is
 * already on screen. Returns its notification, for callers that close it.
 */
function renderDefault(message: CpMessage): CpNotification | null {
  const duplicate = findVisibleDuplicate(message);

  if (duplicate) {
    return duplicate;
  }

  const [icon, iconLabel] = DEFAULT_ICONS[message.type] ?? DEFAULT_ICONS.notice;
  const instance = new Notification(message.type, message.message, {
    icon,
    iconLabel: t(iconLabel),
    ...message.settings,
  });

  visible.add({message, instance, shownAt: Date.now()});
  void instance.show();

  return instance;
}

/**
 * Shows a message: in its inline outlet when it names one that's on the page,
 * otherwise in the default display. A message with an `id` that has already
 * been shown is ignored.
 *
 * Returns the notification when the message went to the default display.
 */
export function showMessage(message: CpMessage): CpNotification | null {
  const id = idOf(message);

  if (id) {
    if (hasSeen(id)) {
      return findVisibleDuplicate(message);
    }

    markSeen(id);
  }

  const handlers = message.target ? outlets.get(message.target) : undefined;

  if (handlers?.length) {
    handlers[handlers.length - 1]!(message);
    return null;
  }

  return renderDefault(message);
}

/** Shows a notice. Callers that close it later should handle a `null` result. */
export function showNotice(
  message: string,
  settings?: MessageSettings
): CpNotification | null {
  return showMessage({type: 'notice', message, settings});
}

export function showSuccess(
  message: string,
  settings?: MessageSettings
): CpNotification | null {
  return showMessage({type: 'success', message, settings});
}

/** Shows an error, or a generic server error when there's no message. */
export function showError(
  message?: string | null,
  settings?: MessageSettings
): CpNotification | null {
  return showMessage({
    type: 'error',
    message: message || t('A server error occurred.'),
    settings,
  });
}

/**
 * Shows the messages in a JSON response from `asSuccess()` or `asFailure()`:
 * its `messages`, or failing that its plain `message` (older responses).
 * Returns whether there were any.
 */
export function showMessagesFromResponse(data: unknown): boolean {
  const body = (data ?? {}) as {
    messages?: CpMessage[];
    message?: unknown;
    notificationSettings?: MessageSettings;
  };

  if (Array.isArray(body.messages) && body.messages.length) {
    body.messages.forEach(showMessage);
    return true;
  }

  if (typeof body.message === 'string' && body.message) {
    showMessage({
      type: 'success',
      message: body.message,
      settings: body.notificationSettings,
    });
    return true;
  }

  return false;
}

/**
 * Registers an inline outlet for messages that target `name`. The most
 * recently registered outlet for a name wins. Returns the unregister function.
 */
export function registerMessageOutlet(
  name: string,
  outlet: MessageOutlet
): () => void {
  const handlers = outlets.get(name) ?? [];
  handlers.push(outlet);
  outlets.set(name, handlers);

  return () => {
    const remaining = (outlets.get(name) ?? []).filter((h) => h !== outlet);

    if (remaining.length) {
      outlets.set(name, remaining);
    } else {
      outlets.delete(name);
    }
  };
}

/**
 * Routes `Craft.cp.displayNotification()` — and with it `displayNotice()`,
 * `displaySuccess()` and `displayError()`, which call it — through
 * {@link showMessage}. Only a prebuilt `Craft.CP.Notification` still goes to
 * the legacy renderer.
 */
export function installLegacyShim(): boolean {
  const CP = (
    window as {Craft?: {CP?: {prototype: {displayNotification?: unknown}}}}
  ).Craft?.CP;
  const proto = CP?.prototype as
    | {displayNotification: DisplayNotification; __messageShim?: true}
    | undefined;

  if (!proto?.displayNotification) {
    return false;
  }

  if (proto.__messageShim) {
    return true;
  }

  renderLegacy = proto.displayNotification;
  proto.__messageShim = true;

  proto.displayNotification = function (type, text, settings) {
    if (typeof type !== 'string') {
      return renderLegacy!.call(this, type, text, settings);
    }

    const message: CpMessage = {
      type: type as MessageType,
      message: text ?? '',
      settings,
    };
    const id = idOf(message);

    if (id) {
      if (hasSeen(id)) {
        return findVisibleDuplicate(message) ?? stub(type, text);
      }

      markSeen(id);
    }

    return renderDefault(message) ?? stub(type, text);
  } as DisplayNotification;

  return true;
}

/** A stand-in for a skipped duplicate, so callers can still call `.on()`/`.close()`. */
function stub(type: string, message = ''): CpNotification {
  return {
    type,
    message,
    closing: true,
    element: null,
    closeButton: null,
    on() {},
    close() {},
  };
}

/**
 * Messages that appeared moments before the page unloads (a save that shows
 * one and then navigates) would vanish unread, so they're shown again on the
 * next page.
 */
export function carryOverRecentMessages(): void {
  const now = Date.now();
  const recent = [...visible]
    .filter(
      (entry) =>
        isOnScreen(entry.instance) && now - entry.shownAt < CARRY_WINDOW
    )
    .map((entry) => entry.message);

  if (recent.length) {
    writeStorage(CARRY_STORAGE_KEY, recent);
  }
}

export function restoreCarriedMessages(): void {
  const carried = readStorage<CpMessage[]>(CARRY_STORAGE_KEY, []);

  try {
    sessionStorage.removeItem(CARRY_STORAGE_KEY);
  } catch {
    // Ignore; nothing was carried.
  }

  carried.forEach((message) => renderDefault(message));
}

/** @internal For tests. */
export function resetMessages(): void {
  outlets.clear();
  visible.clear();
  seenInPage.clear();
  renderLegacy = null;

  try {
    sessionStorage.removeItem(SEEN_STORAGE_KEY);
    sessionStorage.removeItem(CARRY_STORAGE_KEY);
  } catch {
    // Ignore.
  }
}
