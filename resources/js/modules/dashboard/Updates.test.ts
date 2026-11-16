import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, nextTick, type App} from 'vue';
import Updates from './Updates.vue';
import {provideWidgetHeader} from './useWidgetHeader';

const state = vi.hoisted(() => ({post: vi.fn(), reload: vi.fn()}));
vi.mock('@craftcms/ui', async () => ({
  ...(await vi.importActual('@craftcms/ui')),
  actionClient: {post: state.post},
  formattingLocale: () => 'en-US',
}));
vi.mock('@inertiajs/vue3', () => ({router: {reload: state.reload}}));
vi.mock('@actions/Updates/UpdatesController', () => ({
  check: {url: () => '/updates/check'},
}));
vi.mock('@actions/Utilities/UtilitiesController', () => ({
  show: {url: () => '/utilities/updates'},
}));

let app: App;
let host: HTMLElement;

afterEach(() => {
  app?.unmount();
  host?.remove();
  vi.useRealTimers();
  vi.resetAllMocks();
});

function mount(data: {
  cached: boolean;
  total: number;
  dateFetched?: string | null;
}) {
  host = document.createElement('div');
  document.body.append(host);
  // The header, which the widget adds its refresh button to, as the
  // dashboard draws it
  app = createApp({
    setup() {
      const header = provideWidgetHeader();
      return () =>
        h('div', [
          h('span', {
            class: 'header-actions',
            ref: (element) =>
              (header.actionsTarget.value = element as HTMLElement | null),
          }),
          h(Updates, {data}),
        ]);
    },
  });
  app.mount(host);
}

const body = () =>
  host.querySelector('craft-card > .body')!.textContent!.replace(/\s+/g, ' ');

it('says when it last checked, while up to date', () => {
  vi.useFakeTimers({now: new Date('2026-11-16T12:00:00Z')});
  mount({cached: true, total: 0, dateFetched: '2026-11-16T10:00:00Z'});

  expect(body()).toContain('You’re up to date');
  expect(body()).toContain('Checked 2 hours ago');
});

it('counts the available updates, and links to them', () => {
  mount({cached: true, total: 3, dateFetched: '2026-11-16T10:00:00Z'});

  expect(body()).toContain('3 updates available');
  expect(body()).not.toContain('Checked');
  // Set as the button's property, which it links with
  expect(
    (host.querySelector('.updates__action') as HTMLElement & {href?: string})
      .href
  ).toBe('/utilities/updates');
});

it('checks again from the button it adds to its header', async () => {
  vi.useFakeTimers({now: new Date('2026-11-16T12:00:00Z')});
  state.post.mockResolvedValue({
    data: {total: 1, dateFetched: '2026-11-16T12:00:00Z'},
  });
  mount({cached: true, total: 0, dateFetched: '2026-11-15T12:00:00Z'});
  expect(body()).toContain('Checked yesterday');

  host
    .querySelector('.header-actions craft-button')!
    .dispatchEvent(new MouseEvent('click', {bubbles: true}));
  await vi.waitFor(() => expect(state.post).toHaveBeenCalledOnce());
  await nextTick();

  expect(state.post.mock.lastCall![1]).toEqual({forceRefresh: true});
  expect(body()).toContain('1 update available');
});
