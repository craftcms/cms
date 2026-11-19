import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, nextTick, type App} from 'vue';
import AboutCraftMenu from './AboutCraftMenu.vue';

const get = vi.hoisted(() => vi.fn());
const needsAttention = vi.hoisted(() => ({value: false}));

vi.mock('@craftcms/ui', async () => ({
  ...(await vi.importActual<typeof import('@craftcms/ui')>('@craftcms/ui')),
  actionClient: {get},
}));
vi.mock('@actions/App/AboutCraftController', () => ({
  default: {url: () => '/actions/app/about-craft'},
}));
vi.mock('@/common/composables/useCraftData', async () => {
  const {ref} = await vi.importActual<typeof import('vue')>('vue');

  return {
    default: () => ({
      app: ref({
        version: '6.0.0',
        edition: {name: 'Pro'},
        needsAttention: needsAttention.value,
      }),
    }),
  };
});

let app: App | undefined;

afterEach(() => {
  app?.unmount();
  document.body.innerHTML = '';
  get.mockReset();
  needsAttention.value = false;
});

function mount(): HTMLElement {
  const host = document.createElement('div');
  document.body.append(host);
  app = createApp({render: () => h(AboutCraftMenu)});
  app.mount(host);
  return host;
}

it('names its button after the edition badge', () => {
  const host = mount();
  const button = host.querySelector('#about-craft-trigger')!;

  expect(button.querySelector('[aria-hidden="true"]')!.textContent).toBe('Pro');
  expect(button.querySelector('.sr-only')!.textContent).toBe('Pro edition');
  const tooltip = host.querySelector<HTMLElement & {for?: string}>(
    'craft-tooltip'
  )!;
  expect(tooltip.for ?? tooltip.getAttribute('for')).toBe(
    'about-craft-trigger'
  );
  expect(tooltip.textContent!.trim()).toBe('About Craft CMS');
});

it('fetches the trials and updates each time it opens', async () => {
  get.mockResolvedValue({
    data: {
      version: '6.0.0',
      edition: 'Pro',
      trial: {message: 'Craft Pro is installed as a trial.', cartUrl: '#'},
      updates: null,
    },
  });
  const host = mount();
  const popover = host.querySelector('craft-popover')!;

  expect(get).not.toHaveBeenCalled();

  popover.dispatchEvent(new CustomEvent('craft-show'));
  await vi.waitFor(() => expect(get).toHaveBeenCalledTimes(1));
  await nextTick();

  expect(host.querySelector('.about-craft__trial')!.textContent).toContain(
    'Craft Pro is installed as a trial.'
  );

  popover.dispatchEvent(new CustomEvent('craft-show'));
  await vi.waitFor(() => expect(get).toHaveBeenCalledTimes(2));
});

it('marks its button with a dot while there are updates or trials to act on', () => {
  expect(mount().querySelector('craft-badge-indicator')).toBeNull();
  app?.unmount();

  needsAttention.value = true;
  const dot = mount().querySelector(
    '#about-craft-trigger craft-badge-indicator'
  )!;

  expect(dot.getAttribute('alt-text')).toBe('Needs attention');
});
