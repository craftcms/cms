import {afterEach, expect, it} from 'vite-plus/test';
import {createApp, h, type App} from 'vue';
import type {AboutCraftInfo} from './about-craft';
import AboutCraft from './AboutCraft.vue';

let app: App | undefined;

afterEach(() => {
  app?.unmount();
  document.body.innerHTML = '';
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
