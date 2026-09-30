import {computed, createApp, h, provide, type App} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {
  elementIndexContextKey,
  type ElementIndexContext,
} from '@/modules/elements/index/index-context';

const http = vi.hoisted(() => ({
  data: undefined as Record<string, unknown> | undefined,
  post: vi.fn(),
  transform: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
  usePage: () => ({
    props: {
      elementType: 'PageEntry',
      context: 'index',
      source: {type: 'native', key: 'page', label: 'Page'},
    },
  }),
  useHttp: (data: Record<string, unknown>) => {
    http.data = data;

    return {
      processing: false,
      post: http.post,
      transform: http.transform,
    };
  },
}));

vi.mock('@craftcms/ui', () => ({
  t: (message: string) => message,
  appendBodyHtml: vi.fn(),
  appendHeadHtml: vi.fn(),
  ButtonVariant: {Fill: 'fill', Primary: 'primary'},
}));

vi.mock('@/common/composables/useAnnouncer', () => ({
  useAnnouncer: () => ({announce: vi.fn()}),
}));

vi.mock('@/modules/conditions/ConditionBuilder.vue', () => ({
  default: {name: 'ConditionBuilder', render: () => null},
}));

const FilterHud = (await import('./FilterHud.vue')).default;
let app: App | undefined;
let container: HTMLElement | undefined;

afterEach(() => {
  app?.unmount();
  container?.remove();
  app = undefined;
  container = undefined;
  http.data = undefined;
  http.post.mockReset();
  http.transform.mockReset();
});

function mount(context?: ElementIndexContext) {
  container = document.createElement('div');
  document.body.append(container);
  app = createApp({
    setup() {
      if (context) {
        provide(
          elementIndexContextKey,
          computed(() => context)
        );
      }

      return () => h(FilterHud);
    },
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
}

it('uses the provided index context and merges detached owner parameters', () => {
  mount({
    elementType: 'NestedEntry',
    context: 'embeddedIndex',
    source: {type: 'native', key: '__IMP__', label: 'Entries'},
    fieldLayouts: [{uid: 'layout-1'}],
    extraParams: {ownerId: 42, attribute: 'field:entries'},
  });

  expect(http.data).toEqual({
    elementType: 'NestedEntry',
    context: 'embeddedIndex',
    source: {type: 'native', key: '__IMP__', label: 'Entries'},
    fieldLayouts: [{uid: 'layout-1'}],
    ownerId: 42,
    attribute: 'field:entries',
    id: 'filters',
  });
});

it('falls back to the Inertia page when no index context is provided', () => {
  mount();

  expect(http.data).toMatchObject({
    elementType: 'PageEntry',
    context: 'index',
    source: {type: 'native', key: 'page', label: 'Page'},
  });
});
