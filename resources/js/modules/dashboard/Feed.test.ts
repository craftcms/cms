import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, nextTick} from 'vue';
import {actionClient} from '@craftcms/ui';
import Feed from './Feed.vue';

vi.mock('@craftcms/ui', () => ({
  actionClient: {post: vi.fn()},
  t: (message: string) => message,
}));

vi.mock('@actions/Dashboard/Widgets/FeedController', () => ({
  cacheData: {url: () => '/actions/dashboard/widgets/feed/cache-data'},
}));

const fetchMock = vi.fn();

beforeEach(() => {
  fetchMock.mockReset();
  fetchMock.mockRejectedValue(new Error('No network in tests.'));
  vi.stubGlobal('fetch', fetchMock);
  vi.mocked(actionClient.post).mockReset();
});

afterEach(() => {
  vi.unstubAllGlobals();
});

function mountFeed(feed: Record<string, unknown> | null) {
  const container = document.createElement('div');
  const app = createApp({
    render: () =>
      h(Feed, {
        data: {
          url: 'https://example.com/feed',
          limit: 5,
          formattingLocale: 'en-US',
          feed,
        },
      }),
  });
  app.mount(container);

  return {container, app};
}

async function settle() {
  for (let i = 0; i < 5; i++) {
    await Promise.resolve();
    await nextTick();
  }
}

it.each([
  ['en-US', 'Sep 5, 2026'],
  ['en-GB', '5 Sept 2026'],
  ['de-DE', '05.09.2026'],
])(
  'formats feed dates for %s at the medium length',
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

    expect(container.querySelector('li time')?.textContent?.trim()).toBe(
      expected
    );

    app.unmount();
  }
);

it('shows each item’s summary and http(s) image', () => {
  const container = document.createElement('div');
  const app = createApp({
    render: () =>
      h(Feed, {
        data: {
          url: 'https://example.com/feed',
          limit: 5,
          formattingLocale: 'en-US',
          feed: {
            items: [
              {
                title: 'With extras',
                permalink: 'https://example.com/one',
                summary: 'A short excerpt.\nSecond paragraph.',
                image: 'https://example.com/one.png',
              },
              {
                title: 'Unsafe image',
                permalink: 'https://example.com/two',
                summary: {html: '<b>not text</b>'},
                image: 'javascript:alert(1)',
              },
            ],
          },
        },
      }),
  });
  app.mount(container);

  const [first, second] = container.querySelectorAll('li');
  const image = first?.querySelector('img');

  expect(first?.querySelector('p')?.textContent).toBe(
    'A short excerpt.\nSecond paragraph.'
  );
  expect(image?.getAttribute('src')).toBe('https://example.com/one.png');
  expect(image?.getAttribute('alt')).toBe('');
  expect(image?.getAttribute('referrerpolicy')).toBe('no-referrer');
  expect(second?.querySelector('img')).toBeNull();
  expect(second?.querySelector('p')).toBeNull();

  app.unmount();
});

it('refreshes a cached feed from before items had summaries and images', async () => {
  fetchMock.mockResolvedValue(
    new Response(
      JSON.stringify({
        items: [
          {
            title: 'Fresh',
            permalink: 'https://example.com/fresh',
            summary: 'Now with a summary.',
            image: 'https://example.com/fresh.png',
          },
        ],
      })
    )
  );
  const {container, app} = mountFeed({
    items: [{title: 'Cached', permalink: 'https://example.com/cached'}],
  });

  expect(container.textContent).toContain('Cached');
  await settle();

  expect(fetchMock).toHaveBeenCalledOnce();
  expect(container.textContent).toContain('Now with a summary.');
  expect(container.querySelector('img')?.getAttribute('src')).toBe(
    'https://example.com/fresh.png'
  );
  expect(actionClient.post).toHaveBeenCalledOnce();

  app.unmount();
});

it('keeps showing a cached feed when refreshing it fails', async () => {
  const {container, app} = mountFeed({
    items: [{title: 'Cached', permalink: 'https://example.com/cached'}],
  });
  await settle();

  expect(fetchMock).toHaveBeenCalledOnce();
  expect(container.textContent).toContain('Cached');
  expect(container.querySelector('[role="alert"]')).toBeNull();

  app.unmount();
});

it('doesn’t refetch a cached feed that has summaries and images', async () => {
  const {app} = mountFeed({
    items: [
      {
        title: 'Cached',
        permalink: 'https://example.com/cached',
        summary: null,
        image: null,
      },
    ],
  });
  await settle();

  expect(fetchMock).not.toHaveBeenCalled();

  app.unmount();
});

it('leads each summary with its date', () => {
  const {container, app} = mountFeed({
    items: [
      {
        title: 'Dated',
        permalink: 'https://example.com/dated',
        date: '2026-09-05T12:00:00',
        summary: 'The excerpt.',
        image: null,
      },
    ],
  });

  const summary = container.querySelector('li p');

  expect(summary?.firstElementChild?.tagName).toBe('TIME');
  expect(summary?.textContent?.replace(/\s+/g, ' ').trim()).toBe(
    'Sep 5, 2026·, The excerpt.'
  );

  app.unmount();
});

it.each([
  ['2026-11-16T09:30:00Z', '9:30:00 AM'],
  ['2026-11-15T09:30:00Z', 'Yesterday'],
  ['2026-11-12T09:30:00Z', 'Thursday'],
  ['2026-11-01T09:30:00Z', 'Nov 1, 2026'],
])(
  'formats %s relative to now, as the server’s timestamps do',
  (date, expected) => {
    vi.useFakeTimers({toFake: ['Date']});
    vi.setSystemTime(new Date('2026-11-16T15:00:00Z'));
    const container = document.createElement('div');
    const app = createApp({
      render: () =>
        h(Feed, {
          data: {
            url: 'https://example.com/feed',
            limit: 5,
            formattingLocale: 'en-US',
            timeZone: 'UTC',
            feed: {
              items: [
                {
                  title: 'Example',
                  permalink: 'https://example.com',
                  date,
                  summary: null,
                  image: null,
                },
              ],
            },
          },
        }),
    });
    app.mount(container);

    expect(container.querySelector('li time')?.textContent?.trim()).toBe(
      expected
    );

    app.unmount();
    vi.useRealTimers();
  }
);
