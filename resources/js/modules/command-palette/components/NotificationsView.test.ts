import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {getNotifications, setNotifications} from '../../notifications/store';
import NotificationsView from './NotificationsView.vue';
import type {CommandPaletteViewResult} from '../types';

// The list renders itself; these tests are about the view around it.
vi.mock('../../notifications/components/cp-notification-list', () => ({}));

type NotificationData = CraftCms.Cms.Cp.Data.NotificationData;

function notification(id: string, unread: boolean): NotificationData {
  return {
    id,
    kind: 'test',
    title: id,
    messageHtml: '',
    byline: null,
    icon: 'bell',
    image: null,
    imageAlt: null,
    url: `${window.location.origin}/admin/${id}`,
    buttons: [],
    createdAt: '2026-10-26T09:00:00-07:00',
    createdAtLabel: 'now',
    unread,
  } as NotificationData;
}

describe('NotificationsView', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;
  const done = vi.fn<(result: CommandPaletteViewResult) => void>();

  async function mount(notifications: NotificationData[]): Promise<void> {
    setNotifications(notifications);
    container = document.createElement('div');
    document.body.append(container);
    app = createApp(() => h(NotificationsView, {onDone: done}));
    app.mount(container);
    await nextTick();
  }

  afterEach(() => {
    app.unmount();
    container.remove();
    done.mockReset();
  });

  it('closes the palette before going to a notification', async () => {
    await mount([notification('a', true)]);
    const navigate = new CustomEvent('cp-notification-navigate', {
      cancelable: true,
      detail: {url: `${window.location.origin}/admin/a`},
    });

    container.querySelector('cp-notification-list')!.dispatchEvent(navigate);

    expect(navigate.defaultPrevented).toBe(true);
    expect(done).toHaveBeenCalledWith({run: expect.any(Function)});
  });

  it('marks all of the unread notifications as read, then moves focus to the heading', async () => {
    const post = vi
      .spyOn(actionClient, 'post')
      .mockResolvedValue({data: {}} as never);
    await mount([
      notification('a', true),
      notification('b', false),
      notification('c', true),
    ]);

    container.classList.add('command-palette__view');
    const heading = document.createElement('h2');
    heading.tabIndex = -1;
    container.prepend(heading);

    container.querySelector<HTMLElement>('craft-button')!.click();
    await nextTick();
    await nextTick();

    expect(post).toHaveBeenCalledWith(expect.any(String), {ids: ['a', 'c']});
    expect(getNotifications().some(({unread}) => unread)).toBe(false);
    expect(container.querySelector('craft-button')).toBeNull();
    expect(document.activeElement).toBe(heading);
  });

  it('says when there are none', async () => {
    await mount([]);

    const empty = container.querySelector<HTMLElement & {label?: string}>(
      'craft-empty'
    );
    expect(empty?.label ?? empty?.getAttribute('label')).toBe(
      'No notifications'
    );
    expect(container.querySelector('cp-notification-list')).toBeNull();
  });
});
