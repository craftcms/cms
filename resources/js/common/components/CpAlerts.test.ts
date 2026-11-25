import {createApp, nextTick, type App} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';

const {pageProps, post, ping, reload} = await vi.hoisted(async () => {
  const {reactive} = await import('vue');

  return {
    pageProps: reactive<{alerts?: Array<{content: string; showIcon: boolean}>}>(
      {}
    ),
    post: vi.fn(),
    ping: vi.fn(),
    reload: vi.fn(),
  };
});

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => ({props: pageProps}),
  router: {reload},
}));
vi.mock('@craftcms/ui/utilities/api/http', async (importOriginal) => ({
  ...(await importOriginal<object>()),
  http: {post},
}));
vi.mock('@craftcms/ui/utilities/api/apiClient', () => ({
  sendApiRequest: ping,
}));
vi.mock('@actions/App/CpAlertsController', () => ({
  destroy: () => ({url: '/actions/app/shun-cp-alert'}),
}));

const {default: CpAlerts} = await import('./CpAlerts.vue');
const {announcement} = await import('@/common/announcer');

let app: App;
let root: HTMLElement;

function mount(): void {
  root = document.createElement('div');
  document.body.append(root);
  app = createApp(CpAlerts);
  app.mount(root);
}

function alertTexts(): string[] {
  return [...root.querySelectorAll('craft-callout > div')].map(
    (alert) => alert.textContent ?? ''
  );
}

afterEach(() => {
  app?.unmount();
  root?.remove();
  delete pageProps.alerts;
  announcement.value = null;
  vi.clearAllMocks();
});

it('announces alerts that arrive after the page loads, but not the ones already showing', async () => {
  pageProps.alerts = [{content: 'Already showing.', showIcon: true}];
  mount();
  await nextTick();

  expect(announcement.value).toBeNull();

  pageProps.alerts = [
    {content: 'Already showing.', showIcon: true},
    {
      content: 'A <strong>critical</strong> update is available.',
      showIcon: true,
    },
  ];
  await nextTick();

  expect(alertTexts()).toEqual([
    'Already showing.',
    'A critical update is available.',
  ]);
  await vi.waitFor(() =>
    expect(announcement.value).toBe('A critical update is available.')
  );
});

it('dismisses an alert from its shun link', async () => {
  post.mockResolvedValue({});
  pageProps.alerts = [
    {content: 'Keep me.', showIcon: true},
    {
      content:
        'Plugin notice. <a href="#" class="shun:pluginNotice">Dismiss</a>',
      showIcon: true,
    },
  ];
  mount();
  await nextTick();

  root.querySelector<HTMLElement>('a[class^="shun:"]')!.click();

  await vi.waitFor(() => expect(alertTexts()).toEqual(['Keep me.']));
  expect(post).toHaveBeenCalledWith('/actions/app/shun-cp-alert', {
    message: 'pluginNotice',
  });
});

it('refreshes the license info before reloading resolvable licensing alerts', async () => {
  ping.mockResolvedValue({});
  reload.mockImplementation(({onFinish}) => onFinish());
  pageProps.alerts = [
    {
      content:
        '<p class="resolvable-alert-buttons"><a href="#">Resolve now</a></p>',
      showIcon: false,
    },
    {content: 'Not resolvable.', showIcon: true},
  ];
  mount();
  await nextTick();

  const buttons = root.querySelectorAll('craft-button');
  expect(buttons).toHaveLength(1);
  expect(buttons[0]?.textContent).toBe('Refresh');

  (buttons[0] as HTMLElement).click();

  await vi.waitFor(() => expect(reload).toHaveBeenCalled());
  expect(ping).toHaveBeenCalledWith('GET', 'ping');
  expect(ping.mock.invocationCallOrder[0]).toBeLessThan(
    reload.mock.invocationCallOrder[0]!
  );
  expect(reload).toHaveBeenCalledWith(
    expect.objectContaining({only: ['alerts']})
  );
});
