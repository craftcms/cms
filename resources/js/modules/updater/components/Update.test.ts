import {createApp, nextTick} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import Update from './Update.vue';

let app: ReturnType<typeof createApp>;
let container: HTMLElement;

const renewalUrl = 'https://console.craftcms.com/licenses/renew';

it.each([
  {status: 'expired', abandoned: false},
  {status: 'eligible', abandoned: true},
])('renders formatted update notices: $status, $abandoned', async (props) => {
  container = document.createElement('div');
  document.body.append(container);
  app = createApp(Update, {
    ...props,
    statusText:
      '<strong>License notice</strong> <a href="https://craftcms.com">Learn more</a>',
  });
  app.mount(container);
  await nextTick();

  const notice = container.querySelector('blockquote');
  expect(notice?.querySelector('strong')?.textContent).toBe('License notice');
  expect(notice?.querySelector('a')?.getAttribute('href')).toBe(
    'https://craftcms.com'
  );
  expect(notice?.textContent).toBe('License notice Learn more');
});

afterEach(() => {
  app?.unmount();
  container?.remove();
});

it.each([
  {allowPurchases: true, ctaUrl: renewalUrl, visible: true},
  {allowPurchases: false, ctaUrl: renewalUrl, visible: false},
  {allowPurchases: true, ctaUrl: false, visible: false},
])(
  'separates license actions from package updates: $allowPurchases, $ctaUrl',
  async ({allowPurchases, ctaUrl, visible}) => {
    container = document.createElement('div');
    document.body.append(container);
    app = createApp(Update, {
      name: 'Craft CMS',
      latestVersion: '100.0.0',
      status: 'expired',
      ctaText: 'Renew for $99',
      ctaUrl: ctaUrl as string | false,
      allowUpdates: false,
      allowPurchases,
    });
    app.mount(container);
    await nextTick();

    const renewal = container.querySelector(`[href="${renewalUrl}"]`);
    expect(renewal !== null).toBe(visible);
    if (renewal) {
      expect(renewal.textContent).toContain('Renew for $99');
      expect(renewal.getAttribute('target')).toBe('_blank');
    }
    expect(container.querySelector('form')).toBeNull();
  }
);
