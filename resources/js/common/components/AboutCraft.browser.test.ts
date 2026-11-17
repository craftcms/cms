import '../../../css/cp.css';
import '@craftcms/ui';
import {afterEach, beforeAll, describe, expect, it} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import {createApp, defineComponent, h, type App} from 'vue';
import {contrast} from '../../../../packages/craftcms-ui/test/contrast';
import DashboardAboutCraft from '@/modules/dashboard/AboutCraft.vue';
import type {DashboardWidget} from '@/modules/dashboard/types';
import {provideWidgetColor} from '@/modules/dashboard/useWidgetColor';
import type {AboutCraftInfo} from './about-craft';
import AboutCraft from './AboutCraft.vue';

const info: AboutCraftInfo = {
  version: '6.0.0',
  edition: 'Pro',
  trial: {
    message: 'Craft Pro is installed as a trial.',
    cartUrl: 'https://console.craftcms.com/cart',
  },
  updates: {cached: true, total: 1, dateFetched: '2026-11-17T12:00:00Z'},
};

const tints = [
  'red',
  'orange',
  'amber',
  'yellow',
  'lime',
  'green',
  'emerald',
  'teal',
  'cyan',
  'sky',
  'blue',
  'indigo',
  'violet',
  'purple',
  'fuchsia',
  'pink',
  'rose',
];

/** Where it's shown: the command palette, or a widget card in a color. */
const surfaces: Array<[string, string | null | undefined]> = [
  ['the command palette', undefined],
  ['an uncolored card', null],
  ...['white', 'gray', 'slate', 'black', ...tints].map(
    (color): [string, string] => [`a ${color} card`, color]
  ),
];

beforeAll(async () => {
  await page.viewport(1024, 768);
});

let app: App | undefined;
let host: HTMLElement;

afterEach(() => {
  app?.unmount();
  host?.remove();
  document.getElementById('hide-ink')?.remove();
});

function mount(theme: string, color: string | null | undefined) {
  host = document.createElement('div');
  host.setAttribute('data-theme', theme);
  host.style.cssText =
    'inline-size: 32rem; padding: 24px; background-color: var(--c-surface-overlay)';
  document.body.append(host);

  const Root = defineComponent({
    setup() {
      if (color === undefined) {
        return () => h(AboutCraft, {info});
      }

      provideWidgetColor(() => color);

      return () =>
        h(DashboardAboutCraft, {
          data: info,
          widget: {} as DashboardWidget,
        });
    },
  });

  app = createApp(Root);
  app.mount(host);
}

const button = (selector: string) =>
  host.querySelector(selector) as HTMLElement & {
    updateComplete: Promise<unknown>;
  };

/** The element whose color the text is drawn in. */
const ink = (element: HTMLElement) =>
  getComputedStyle(element.shadowRoot?.querySelector('a') ?? element).color;

/**
 * The lowest contrast between `element`'s text and the pixels behind it,
 * with the text itself painted transparent. Its color stays, so a fill mixed
 * from it doesn't change.
 */
async function worstContrast(element: HTMLElement): Promise<number> {
  const color = ink(element);
  const range = document.createRange();
  range.selectNodeContents(element);
  const box = range.getBoundingClientRect();

  const style = document.createElement('style');
  style.id = 'hide-ink';
  style.textContent = `
    .about-craft, .about-craft * {
      -webkit-text-fill-color: transparent !important;
    }`;
  document.head.append(style);

  const base64 = (await page.screenshot({
    element: host,
    save: false,
  })) as string;
  style.remove();

  const image = new Image();
  image.src = `data:image/png;base64,${base64}`;
  await image.decode();
  const canvas = document.createElement('canvas');
  canvas.width = image.naturalWidth;
  canvas.height = image.naturalHeight;
  const context = canvas.getContext('2d', {willReadFrequently: true})!;
  context.drawImage(image, 0, 0);

  const origin = host.getBoundingClientRect();
  const scale = image.naturalWidth / origin.width;
  // Above the baseline, clear of a hover underline
  const left = Math.ceil((box.left - origin.left) * scale);
  const top = Math.ceil((box.top + box.height * 0.1 - origin.top) * scale);
  const width = Math.floor(box.width * scale);
  const height = Math.floor(box.height * 0.5 * scale);
  const {data} = context.getImageData(left, top, width, height);

  let worst = Infinity;
  for (let i = 0; i < data.length; i += 4) {
    worst = Math.min(
      worst,
      contrast(color, `rgb(${data[i]}, ${data[i + 1]}, ${data[i + 2]})`)
    );
  }

  return worst;
}

async function hover(element: HTMLElement) {
  await userEvent.hover(element);
  // Past the hover transition
  await new Promise((resolve) => setTimeout(resolve, 400));
  expect(getComputedStyle(element).backgroundColor).not.toBe(
    'rgba(0, 0, 0, 0)'
  );
}

describe.each(['light', 'dark'])('in the %s theme', (theme) => {
  it.each(surfaces)(
    'keeps its text readable in %s, at rest and hovered',
    async (_, color) => {
      mount(theme, color);
      const go = button('.about-craft-updates__go');
      const buy = button('.about-craft__buy');
      await go.updateComplete;
      await buy.updateComplete;

      const title = host.querySelector<HTMLElement>(
        '.about-craft-updates .about-craft-row__title'
      )!;
      expect(await worstContrast(title)).toBeGreaterThanOrEqual(4.5);
      expect(await worstContrast(go)).toBeGreaterThanOrEqual(4.5);
      expect(await worstContrast(buy)).toBeGreaterThanOrEqual(4.5);

      await hover(go);
      expect(await worstContrast(go)).toBeGreaterThanOrEqual(4.5);

      await hover(buy);
      expect(await worstContrast(buy)).toBeGreaterThanOrEqual(4.5);
    }
  );
});
