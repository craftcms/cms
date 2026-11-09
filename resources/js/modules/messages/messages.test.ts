import {afterEach, beforeEach, expect, it, vi} from 'vitest';
import {
  carryOverRecentMessages,
  installLegacyShim,
  showError,
  showMessage,
  showMessagesFromResponse,
  showNotice,
  registerMessageOutlet,
  resetMessages,
  restoreCarriedMessages,
} from './messages';

vi.mock('@craftcms/ui/utilities/translate', () => ({
  t: (message: string) => message,
}));

interface FakeNotification {
  type: string;
  message: string;
  settings: Record<string, unknown>;
  closing: boolean;
  element: HTMLElement | null;
  on(event: string, listener: () => void): void;
  emit(event: string): void;
}

const fakes = vi.hoisted(() => ({rendered: [] as FakeNotification[]}));

vi.mock('./notification', () => ({
  Notification: class {
    closing = false;
    element: HTMLElement | null = null;
    listeners = new Map<string, Set<() => void>>();

    constructor(
      public type: string,
      public message: string,
      public settings: Record<string, unknown>
    ) {}

    on(event: string, listener: () => void) {
      if (!this.listeners.has(event)) {
        this.listeners.set(event, new Set());
      }
      this.listeners.get(event)!.add(listener);
    }

    emit(event: string) {
      for (const listener of this.listeners.get(event) ?? []) {
        listener();
      }
    }

    show() {
      this.element = document.createElement('div');
      document.body.append(this.element);
      fakes.rendered.push(this);
    }
  },
}));

let rendered: FakeNotification[];

/** A legacy `Craft.CP`, for the `Craft.cp.display*()` shim. */
function installFakeCp() {
  function CP() {}
  CP.prototype.displayNotification = vi.fn();
  CP.prototype.displaySuccess = function (
    message: string,
    settings: Record<string, unknown>
  ) {
    return this.displayNotification('success', message, settings);
  };

  const cp = new (CP as unknown as new () => {
    displaySuccess: (m: string, s?: object) => FakeNotification;
  })();
  (window as unknown as {Craft: unknown}).Craft = {CP, cp};
  installLegacyShim();

  return cp;
}

beforeEach(() => {
  fakes.rendered.length = 0;
  rendered = fakes.rendered;
  resetMessages();
});

afterEach(() => {
  document.body.innerHTML = '';
  delete (window as {Craft?: unknown}).Craft;
});

it('shows a message in the default display with its type’s default icon', () => {
  installFakeCp();

  showMessage({type: 'error', message: 'Couldn’t save entry.'});

  expect(rendered).toHaveLength(1);
  expect(rendered[0]).toMatchObject({
    type: 'error',
    message: 'Couldn’t save entry.',
    settings: {icon: 'alert', iconLabel: 'Error'},
  });
});

it('shows a message with a given id only once, across page loads', () => {
  installFakeCp();

  showMessage({id: 'a', type: 'success', message: 'Entry saved.'});
  rendered[0]!.element!.remove();
  showMessage({id: 'a', type: 'success', message: 'Entry saved.'});

  expect(rendered).toHaveLength(1);
  expect(sessionStorage.getItem('Craft-messages.seen')).toContain('"a"');
});

it('doesn’t stack a message that’s already on screen', () => {
  installFakeCp();

  showMessage({type: 'success', message: 'Entry saved.'});
  showMessage({type: 'success', message: 'Entry saved.'});
  showMessage({type: 'error', message: 'Entry saved.'});

  expect(rendered.map((n) => n.type)).toEqual(['success', 'error']);
});

it('dedupes legacy calls that pass the id in their settings', () => {
  const cp = installFakeCp();

  showMessage({id: 'b', type: 'success', message: 'Saved.'});
  rendered[0]!.element!.remove();
  const returned = cp.displaySuccess('Saved.', {id: 'b'});

  expect(rendered).toHaveLength(1);
  expect(returned).toMatchObject({closing: true});
});

it('hands targeted messages to the latest outlet and falls back to the default display', () => {
  installFakeCp();
  const first = vi.fn();
  const second = vi.fn();

  registerMessageOutlet('email-test', first);
  const unregister = registerMessageOutlet('email-test', second);
  showMessage({type: 'success', message: 'Sent.', target: 'email-test'});

  expect(second).toHaveBeenCalledWith(
    expect.objectContaining({message: 'Sent.'})
  );
  expect(first).not.toHaveBeenCalled();

  unregister();
  showMessage({type: 'success', message: 'Sent again.', target: 'missing'});

  expect(rendered.map((n) => n.message)).toEqual(['Sent again.']);
});

it('shows the messages from a JSON response, or its plain message', () => {
  installFakeCp();

  expect(
    showMessagesFromResponse({
      message: 'Saved.',
      messages: [{id: 'c', type: 'success', message: 'Saved.'}],
    })
  ).toBe(true);
  expect(showMessagesFromResponse({message: 'Legacy.'})).toBe(true);
  expect(showMessagesFromResponse({})).toBe(false);

  expect(rendered.map((n) => n.message)).toEqual(['Saved.', 'Legacy.']);
});

it('carries messages shown just before the page unloads over to the next page', () => {
  installFakeCp();

  showMessage({id: 'd', type: 'success', message: 'Moved.'});
  carryOverRecentMessages();
  rendered.length = 0;
  document.body.innerHTML = '';
  restoreCarriedMessages();

  expect(rendered.map((n) => n.message)).toEqual(['Moved.']);
  expect(sessionStorage.getItem('Craft-messages.carry')).toBeNull();
});

it('returns the notification, and falls back to a generic error message', () => {
  installFakeCp();

  const notice = showNotice('Moved.', {persist: true});
  showError();

  expect(notice).toBe(rendered[0]);
  expect(rendered[0]!.settings).toMatchObject({persist: true, icon: 'info'});
  expect(rendered[1]).toMatchObject({
    type: 'error',
    message: 'A server error occurred.',
  });
});

it('returns null when the message goes to an outlet', () => {
  installFakeCp();
  registerMessageOutlet('form', () => {});

  expect(
    showMessage({type: 'error', message: 'Invalid.', target: 'form'})
  ).toBeNull();
});

it('re-fires the legacy Craft.cp notification events', () => {
  const events: Array<[string, unknown]> = [];

  function CP() {}
  CP.prototype.displayNotification = vi.fn();
  CP.prototype.trigger = function (name: string, data?: unknown) {
    events.push([name, data]);
  };

  const cp = new (CP as unknown as new () => {
    displayNotification: (t: string, m: string) => FakeNotification;
  })();
  (window as unknown as {Craft: unknown}).Craft = {CP, cp};
  installLegacyShim();

  const notification = cp.displayNotification('notice', 'Saved');
  notification.emit('show');

  expect(events).toEqual([
    [
      'displayNotification',
      {notificationType: 'notice', message: 'Saved', notification},
    ],
  ]);

  notification.emit('close');

  expect(events[1]).toEqual(['notificationClose', undefined]);
});
