import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, defineComponent, h, type App} from 'vue';
import QuickPost from './QuickPost.vue';
import type {DashboardWidget} from './types';
import {SlideoutHost, closeAllSlideouts} from '@/common/slideouts';
import {setSlideoutPageLoader} from '@/common/slideouts/store';

const state = vi.hoisted(() => ({
  post: vi.fn(),
}));
vi.mock('@craftcms/ui', async () => ({
  ...(await vi.importActual('@craftcms/ui')),
  actionClient: {post: state.post},
  t: (message: string) => message,
}));
vi.mock('@actions/Entries/CreateEntryController', () => ({
  default: {
    '/{cpTrigger?}/{actionTrigger?}/entries/create': {
      url: () => '/entries/create',
    },
  },
}));

let app: App;
let host: HTMLElement;

afterEach(() => {
  closeAllSlideouts();
  setSlideoutPageLoader();
  vi.unstubAllGlobals();
  app?.unmount();
  host?.remove();
  vi.resetAllMocks();
});

it('opens the created entry in a slideout', async () => {
  // Icons are external assets; the editor response is supplied below.
  vi.stubGlobal(
    'fetch',
    async () => new Response('<svg xmlns="http://www.w3.org/2000/svg"></svg>')
  );
  setSlideoutPageLoader(async (href) => ({
    component: defineComponent({
      setup() {
        return () => h('h1', 'Edit news draft');
      },
    }),
    props: {},
    url: href,
  }));
  state.post.mockResolvedValue({
    data: {cpEditUrl: '/entries/news/123?draftId=456'},
  });
  const params = {section: 'news', type: 'article', siteId: 1};
  host = document.createElement('div');
  document.body.append(host);
  app = createApp({
    render: () =>
      h('div', [
        h(SlideoutHost, {assetVersion: 'test'}),
        h(QuickPost, {
          widget: {
            id: 1,
            type: 'QuickPost',
            name: 'Quick Post',
            title: 'Create entry',
            subtitle: null,
            colspan: 1,
            maxColspan: 4,
            settings: {},
            settingsUi: null,
            component: 'craft:widget-quick-post',
            data: {params},
            fragment: {html: '', headHtml: '', bodyHtml: ''},
          } satisfies DashboardWidget,
        }),
      ]),
  });
  app.mount(host);

  const button = host.querySelector('craft-button')!;
  button.dispatchEvent(new MouseEvent('click', {bubbles: true}));

  await vi.waitFor(() =>
    expect(document.querySelector('[role=dialog]')?.textContent).toContain(
      'Edit news draft'
    )
  );
});
