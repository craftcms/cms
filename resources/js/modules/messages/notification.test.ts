import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import './components/cp-messages';
import {Notification} from './notification';

vi.mock('@/common/craft/icon-svg', () => ({iconSvg: async () => null}));
vi.mock('@/modules/element-thumb-loader/globals', () => ({
  loadElementThumbs: () => {},
}));

async function shown(
  type: string,
  message: string,
  settings = {}
): Promise<Notification> {
  const notification = new Notification(type, message, {
    animate: false,
    ...settings,
  });
  await notification.show();

  return notification;
}

const region = (role: string) =>
  document.querySelector(`cp-messages .message-region[role="${role}"]`)!;

beforeEach(() => {
  vi.stubGlobal('Craft', {notificationDuration: 1000});
  document.body.innerHTML = '<cp-messages></cp-messages>';
});

afterEach(() => {
  vi.useRealTimers();
  vi.unstubAllGlobals();
  document.body.innerHTML = '';
});

describe('Notification', () => {
  it('announces errors assertively and everything else politely', async () => {
    await shown('error', 'Couldn’t save.');
    await shown('success', 'Saved.');

    expect(region('alert').textContent).toContain('Couldn’t save.');
    expect(region('status').textContent).toContain('Saved.');
  });

  it('closes itself after the notification duration, but not errors', async () => {
    vi.useFakeTimers();
    const success = await shown('success', 'Saved.');
    const error = await shown('error', 'Couldn’t save.');

    await vi.advanceTimersByTimeAsync(1000);

    expect(success.element!.isConnected).toBe(false);
    expect(error.element!.isConnected).toBe(true);
  });

  it('closes from its close button, and says so', async () => {
    const notification = await shown('notice', 'Moved.', {persist: true});
    const closed = vi.fn();
    notification.on('close', closed);

    notification.closeButton!.click();
    await vi.waitFor(() => expect(closed).toHaveBeenCalled());

    expect(notification.element!.isConnected).toBe(false);
  });

  it('stays off screen when it’s closed while still opening', async () => {
    const notification = new Notification('notice', 'Moved.', {animate: false});
    const closed = vi.fn();
    notification.on('close', closed);

    // `close()` lands while `show()` is still awaiting its icon.
    const showing = notification.show();
    const closing = notification.close();
    await Promise.all([showing, closing]);

    expect(document.querySelector('cp-messages .notification')).toBeNull();
    expect(closed).toHaveBeenCalled();
  });

  it.each([
    ['an element', (button: HTMLElement) => button],
    ['a jQuery-style collection', (button: HTMLElement) => [button]],
  ])('takes details as %s', async (_label, wrap) => {
    const button = document.createElement('craft-button');
    button.textContent = 'Undo';

    const notification = await shown('notice', 'Moved.', {
      details: wrap(button),
    });

    const details = notification.element!.querySelector(
      '.notification-details'
    );

    expect(details!.textContent).toBe('Undo');
    expect(details!.firstElementChild).toBe(button);
  });
});

describe('the <cp-messages> stack', () => {
  it('shows the five newest, keeping older ones out of reach', async () => {
    const notifications: Notification[] = [];
    for (let i = 0; i < 6; i++) {
      notifications.push(await shown('error', `Error ${i}`));
      // Each needs its own shown-at time to sort by
      await new Promise((resolve) => setTimeout(resolve, 2));
    }

    expect(notifications[0]!.element!.inert).toBe(true);
    expect(notifications[5]!.element!.inert).toBe(false);
    expect(notifications[5]!.element!.hasAttribute('data-stack-behind')).toBe(
      false
    );
  });
});
