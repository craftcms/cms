import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {setNotifications} from '../store';
import './cp-notification-list';

type NotificationData = CraftCms.Cms.Cp.Data.NotificationData;

function notification(id: string, url: string | null): NotificationData {
  return {
    id,
    kind: 'test',
    title: id,
    messageHtml: '',
    byline: null,
    icon: 'bell',
    image: null,
    imageAlt: null,
    url,
    buttons: [],
    createdAt: '2026-10-26T09:00:00-07:00',
    createdAtLabel: 'now',
    unread: true,
  } as NotificationData;
}

describe('cp-notification-list', () => {
  afterEach(() => {
    document.body.innerHTML = '';
    vi.restoreAllMocks();
  });

  async function markRead(
    item: NotificationData
  ): Promise<{list: HTMLElement & {updateComplete: Promise<unknown>}}> {
    vi.spyOn(actionClient, 'post').mockResolvedValue({data: {}} as never);
    setNotifications([item]);
    const list = document.createElement(
      'cp-notification-list'
    ) as HTMLElement & {
      updateComplete: Promise<unknown>;
    };
    document.body.append(list);
    await list.updateComplete;

    const button = list.shadowRoot!.querySelector<HTMLElement>(
      '.notification-mark-read'
    )!;
    button.focus();
    button.click();
    await list.updateComplete;
    await Promise.resolve();

    return {list};
  }

  it('lists the notifications as list items', async () => {
    setNotifications([notification('a', null), notification('b', null)]);
    const list = document.createElement(
      'cp-notification-list'
    ) as HTMLElement & {updateComplete: Promise<unknown>};
    document.body.append(list);
    await list.updateComplete;

    const items = [...list.shadowRoot!.querySelectorAll('[part~="item"]')];
    expect(items).toHaveLength(2);
    expect(
      items.every(
        (item) =>
          item.localName === 'li' &&
          !item.hasAttribute('role') &&
          item.parentElement?.localName === 'ul'
      )
    ).toBe(true);
  });

  it('moves focus to the notification’s link once Mark as read has gone', async () => {
    const {list} = await markRead(
      notification('a', `${window.location.origin}/admin/a`)
    );

    expect(
      list.shadowRoot!.querySelector('.notification-mark-read')
    ).toBeNull();
    expect(list.shadowRoot!.activeElement).toBe(
      list.shadowRoot!.querySelector('.notification-card-link')
    );
  });

  it('moves focus to the notification itself when it has no link', async () => {
    const {list} = await markRead(notification('a', null));

    expect(list.shadowRoot!.activeElement).toBe(
      list.shadowRoot!.querySelector('.notification-item')
    );
  });
});
