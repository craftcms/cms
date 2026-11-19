import {createApp, h, nextTick, type App} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';

vi.mock('@craftcms/ui/utilities/api/http', async (importOriginal) => ({
  ...(await importOriginal<object>()),
  http: {post: vi.fn(() => Promise.resolve({}))},
}));
vi.mock('@actions/App/LicensesController', () => ({
  setShunCookie: () => ({url: '/actions/app/set-shun-cookie'}),
}));
vi.mock('@/common/layouts/AuthBase.vue', () => ({
  default: {
    render(this: any) {
      return h('div', this.$slots.default?.());
    },
  },
}));

const {default: Issues} = await import('./Issues.vue');
let app: App;
let root: HTMLElement;

afterEach(() => {
  app?.unmount();
  root?.remove();
  vi.useRealTimers();
});

it('adds the continue link to a status region that was there from the start', async () => {
  vi.useFakeTimers();
  root = document.createElement('div');
  document.body.append(root);
  app = createApp({
    render: () =>
      h(Issues, {issues: ['Issue'], hash: 'abc', cartUrl: '#', duration: 1}),
  });
  app.config.globalProperties.$page = {url: '/admin/dashboard'} as any;
  app.mount(root);
  await nextTick();

  const status = root.querySelector('[role="status"]');
  expect(status).not.toBeNull();
  expect(status!.querySelector('a')).toBeNull();

  await vi.advanceTimersByTimeAsync(1000);
  await nextTick();

  expect(root.querySelector('[role="status"]')).toBe(status);
  expect(status!.querySelector('a')!.getAttribute('href')).toBe(
    '/admin/dashboard'
  );
  expect(root.querySelector('[role="timer"]')).toBeNull();
});
