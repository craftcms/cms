import {afterEach, beforeEach, expect, it, vi} from 'vitest';
import {
  carryOverRecentMessages,
  installLegacyShim,
  showMessage,
  showMessagesFromResponse,
  registerMessageOutlet,
  resetMessages,
  restoreCarriedMessages,
} from './messages';

vi.mock('@craftcms/ui', () => ({t: (message: string) => message}));

interface FakeNotification {
  type: string;
  message: string;
  settings: Record<string, unknown>;
  closing: boolean;
  $container: [HTMLElement];
}

let rendered: FakeNotification[];

function installFakeCp() {
  function CP() {}
  CP.prototype.displayNotification = function (
    type: string,
    message: string,
    settings: Record<string, unknown>
  ): FakeNotification {
    const element = document.createElement('div');
    document.body.append(element);
    const notification = {
      type,
      message,
      settings,
      closing: false,
      $container: [element] as [HTMLElement],
    };
    rendered.push(notification);
    return notification;
  };
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
  rendered = [];
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
  rendered[0]!.$container[0].remove();
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
  rendered[0]!.$container[0].remove();
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
  rendered = [];
  document.body.innerHTML = '';
  restoreCarriedMessages();

  expect(rendered.map((n) => n.message)).toEqual(['Moved.']);
  expect(sessionStorage.getItem('Craft-messages.carry')).toBeNull();
});
