import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, nextTick, type App} from 'vue';
import AboutView from './AboutView.vue';

const state = vi.hoisted(() => ({get: vi.fn()}));

vi.mock('@craftcms/ui', async () => ({
  ...(await vi.importActual('@craftcms/ui')),
  actionClient: {get: state.get},
}));
vi.mock('@actions/App/AboutCraftController', () => ({
  default: {url: () => '/actions/app/about'},
}));
vi.mock('@/common/composables/useCraftData', async () => {
  const {ref} = await vi.importActual<typeof import('vue')>('vue');

  return {
    default: () => ({
      app: ref({version: '6.0.0', edition: {name: 'Pro'}}),
    }),
  };
});

let app: App | undefined;

afterEach(() => {
  app?.unmount();
  document.body.innerHTML = '';
  vi.resetAllMocks();
});

it('shows the version and edition straight away, and the trials once they’re fetched', async () => {
  let respond!: (value: unknown) => void;
  state.get.mockReturnValue(new Promise((resolve) => (respond = resolve)));
  const host = document.createElement('div');
  document.body.append(host);
  app = createApp({render: () => h(AboutView)});
  app.mount(host);

  expect(host.querySelector('.about-craft__version')!.textContent).toContain(
    '6.0.0'
  );
  expect(host.querySelector('.about-craft__trial')).toBeNull();

  respond({
    data: {
      version: '6.0.0',
      edition: 'Pro',
      trial: {message: 'Craft Pro is installed as a trial.', cartUrl: '/cart'},
      updates: null,
    },
  });
  await new Promise((resolve) => setTimeout(resolve));
  await nextTick();

  expect(state.get).toHaveBeenCalledWith('/actions/app/about');
  expect(host.querySelector('.about-craft__trial')!.textContent).toContain(
    'Craft Pro is installed as a trial.'
  );
});
