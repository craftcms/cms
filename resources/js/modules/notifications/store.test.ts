import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {
  getNotifications,
  markNotificationsRead,
  onNotificationsChange,
  setNotifications,
} from './store';

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
    url: null,
    buttons: [],
    createdAt: '2026-10-26T09:00:00-07:00',
    createdAtLabel: 'now',
    unread,
  } as NotificationData;
}

describe('notifications store', () => {
  const post = vi.spyOn(actionClient, 'post');

  beforeEach(() => {
    post.mockReset();
    setNotifications([
      notification('a', true),
      notification('b', true),
      notification('c', false),
    ]);
  });

  it('marks notifications as read for everything showing them', async () => {
    post.mockResolvedValue({data: {}} as never);
    const listener = vi.fn();
    const stop = onNotificationsChange(listener);

    await markNotificationsRead(['a', 'c']);
    stop();

    expect(getNotifications().map(({id, unread}) => [id, unread])).toEqual([
      ['a', false],
      ['b', true],
      ['c', false],
    ]);
    expect(listener).toHaveBeenCalled();
    expect(post).toHaveBeenCalledWith(expect.any(String), {ids: ['a']});
  });

  it('puts them back when the server can’t mark them', async () => {
    post.mockRejectedValue(new Error('Offline'));
    const displayError = vi.fn();
    vi.stubGlobal('Craft', {cp: {displayError}});

    await markNotificationsRead(['a', 'b']);
    vi.unstubAllGlobals();

    expect(getNotifications().filter(({unread}) => unread)).toHaveLength(2);
    expect(displayError).toHaveBeenCalledOnce();
  });
});
