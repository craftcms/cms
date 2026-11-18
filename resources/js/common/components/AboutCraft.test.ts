import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, defineComponent, h, type App} from 'vue';
import type {AboutCraftInfo} from './about-craft';
import AboutCraft from './AboutCraft.vue';

const state = vi.hoisted(() => ({get: vi.fn()}));
vi.mock('@craftcms/ui', async () => ({
  ...(await vi.importActual('@craftcms/ui')),
  actionClient: {get: state.get},
}));
vi.mock('@actions/App/AboutCraftController', () => ({
  default: {url: () => '/about-craft'},
}));
// Stands in for the updates row, with a button that finishes a check.
vi.mock('./AboutCraftUpdates.vue', () => ({
  default: defineComponent({
    emits: ['checked'],
    setup:
      (_, {emit}) =>
      () =>
        h('button', {class: 'finish-check', onClick: () => emit('checked')}),
  }),
}));

let app: App | undefined;

afterEach(() => {
  app?.unmount();
  document.body.innerHTML = '';
  vi.resetAllMocks();
});

function mount(info: AboutCraftInfo): HTMLElement {
  const host = document.createElement('div');
  document.body.append(host);
  app = createApp({render: () => h(AboutCraft, {info})});
  app.mount(host);

  return host;
}

it('shows Craft’s edition and version', () => {
  const host = mount({
    version: '6.0.0',
    edition: 'Pro',
    trial: null,
    updates: null,
  });

  expect(
    host
      .querySelector('.about-craft__app')!
      .textContent!.replace(/\s+/g, ' ')
      .trim()
  ).toBe('Craft CMS Pro Pro edition');
  expect(
    host.querySelector('.about-craft__edition [aria-hidden="true"]')!
      .textContent
  ).toBe('Pro');
  expect(host.querySelector('.about-craft__version')!.textContent).toBe(
    '6.0.0'
  );
  // Nothing to report below them
  expect(host.querySelector('.about-craft__section')).toBeNull();
});

it('offers to buy the trials that are installed', () => {
  const host = mount({
    version: '6.0.0',
    edition: 'Pro',
    trial: {
      message: 'Craft Pro is installed as a trial.',
      cartUrl: 'https://console.craftcms.com/cart/new',
    },
    updates: null,
  });

  const trial = host.querySelector('.about-craft__trial')!;
  // Set as the button's property, which it links with
  const button = trial.querySelector('craft-button') as HTMLElement & {
    href?: string;
  };

  expect(trial.textContent).toContain('Craft Pro is installed as a trial.');
  expect(button.textContent!.trim()).toBe('Buy now');
  expect(button.href).toBe('https://console.craftcms.com/cart/new');
});

it('fetches the trials again once the updates are checked, as they’re only known with the license info', async () => {
  // The license info had expired with the updates, so no trials were known.
  state.get.mockResolvedValue({
    data: {
      version: '6.0.0',
      edition: 'Pro',
      trial: {
        message: 'Craft Pro is installed as a trial.',
        cartUrl: 'https://console.craftcms.com/cart/new',
      },
      updates: {cached: true, total: 0, dateFetched: '2026-11-16T12:00:00Z'},
    },
  });
  const host = mount({
    version: '6.0.0',
    edition: 'Pro',
    trial: null,
    updates: {cached: false, total: 0, dateFetched: null},
  });
  expect(host.querySelector('.about-craft__trial')).toBeNull();

  host.querySelector<HTMLElement>('.finish-check')!.click();

  await vi.waitFor(() =>
    expect(host.querySelector('.about-craft__trial')?.textContent).toContain(
      'Craft Pro is installed as a trial.'
    )
  );
  expect(state.get).toHaveBeenCalledWith('/about-craft');
});
