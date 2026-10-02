import {expect, it, vi} from 'vite-plus/test';
import {createApp, h} from 'vue';
import Feed from './Feed.vue';

vi.mock('@craftcms/ui', () => ({
  actionClient: {post: vi.fn()},
  t: (message: string) => message,
}));

it.each([
  ['en-US', '9/5/2026'],
  ['en-GB', '05/09/2026'],
  ['de-DE', '05.09.2026'],
])(
  'formats feed dates for %s with four-digit years',
  (formattingLocale, expected) => {
    const container = document.createElement('div');
    const app = createApp({
      render: () =>
        h(Feed, {
          data: {
            url: 'https://example.com/feed',
            limit: 5,
            formattingLocale,
            feed: {
              items: [
                {
                  title: 'Example',
                  permalink: 'https://example.com',
                  date: '2026-09-05T12:00:00',
                },
              ],
            },
          },
        }),
    });
    app.mount(container);

    expect(container.querySelector('li span')?.textContent?.trim()).toBe(
      expected
    );

    app.unmount();
  }
);
