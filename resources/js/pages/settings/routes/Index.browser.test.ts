import '../../../../css/cp.css';
import '@craftcms/ui/components/button/button';
import '@craftcms/ui/components/reorder-button/reorder-button';
import {createApp, defineComponent, h, nextTick, reactive} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser/context';
import type {RouteIndexData} from './types';
import Index from './Index.vue';

const inertia = vi.hoisted(() => ({
  page: {props: {routes: [] as RouteIndexData[]}},
}));
const visit = vi.hoisted(() =>
  vi.fn(
    (_url: unknown, options: {optimistic: () => Record<string, unknown>}) => {
      Object.assign(inertia.page.props, options.optimistic());
    }
  )
);

vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual('@inertiajs/vue3');
  return {
    ...actual,
    usePage: () => inertia.page,
    router: {visit},
  };
});
vi.mock('@/common/composables/useAppLayout', () => ({
  useAppLayout: () => {},
}));
vi.mock('@/common/components/LayoutSlot.vue', () => ({
  default: defineComponent({setup: () => () => null}),
}));

let app: ReturnType<typeof createApp> | undefined;

function route(uid: string, template: string): RouteIndexData {
  return {
    uid,
    template,
    siteName: '',
    uriDisplayHtml: template,
  } as RouteIndexData;
}

beforeEach(() => {
  vi.stubGlobal('Craft', {cpTrigger: 'admin'});
  inertia.page = reactive({
    props: {
      routes: [route('blog', 'blog/_entry'), route('news', 'news/_entry')],
    },
  });
});

afterEach(() => {
  app?.unmount();
  document.body.innerHTML = '';
  visit.mockClear();
  vi.unstubAllGlobals();
});

function renderedTemplates(): string[] {
  return [...document.querySelectorAll('.route__template span')].map(
    (template) => template.textContent!.trim()
  );
}

function reorderButton(template: string) {
  return [...document.querySelectorAll<HTMLElement>('.route')]
    .find((row) => row.textContent?.includes(template))!
    .querySelector('craft-reorder-button')!;
}

it.each([
  ['moveDown', 'blog/_entry'],
  ['moveUp', 'news/_entry'],
])(
  'moves a route with %s from the keyboard and keeps focus on its reorder button',
  async (action, template) => {
    const container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      setup: () => () =>
        h(Index, {
          title: 'Routes',
          routes: inertia.page.props.routes,
          isMultiSite: false,
        }),
    });
    app.mount(container);
    await nextTick();
    const button = reorderButton(template);
    await button.updateComplete;

    button.shadowRoot!.querySelector<HTMLElement>('craft-button')!.focus();
    await userEvent.keyboard('{Enter}');
    await vi.waitFor(() =>
      expect(
        button.shadowRoot!.querySelector('craft-action-menu')!.opened
      ).toBe(true)
    );
    button
      .shadowRoot!.querySelector<HTMLElement>(`[data-action="${action}"]`)!
      .focus();
    await userEvent.keyboard('{Enter}');
    await vi.waitFor(() =>
      expect(renderedTemplates()).toEqual(['news/_entry', 'blog/_entry'])
    );

    expect(inertia.page.props.routes.map(({uid}) => uid)).toEqual([
      'news',
      'blog',
    ]);
    const moved = reorderButton(template);
    expect(document.activeElement).toBe(moved);
    expect(moved.shadowRoot!.activeElement).toBe(
      moved.shadowRoot!.querySelector('craft-button')
    );
  }
);
