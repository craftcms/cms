import {beforeAll, beforeEach, describe, expect, it} from 'vite-plus/test';
import {contrast, loadTokens} from '../../../test/contrast';
import {colors} from '@src/constants/colors';
import {variants} from '@src/constants/variants';
import './indicator.js';
import type CraftIndicator from './indicator.js';

const themes = ['light', 'dark'];

const surfaces = [
  '--c-surface-default',
  '--c-surface-sunken',
  '--c-color-accent-fill-quiet',
];

const fills = [
  ...colors.filter((color) => color !== 'white' && color !== 'black'),
  ...variants,
];

beforeAll(loadTokens);

beforeEach(() => {
  document.body.innerHTML = '';
});

async function render(
  theme: string,
  surface: string,
  attributes: string
): Promise<{dot: HTMLElement; background: string}> {
  document.body.innerHTML = `
    <div data-theme="${theme}" style="background-color: var(${surface})">
      <craft-indicator ${attributes}></craft-indicator>
    </div>`;
  const indicator = document.querySelector<CraftIndicator>('craft-indicator')!;
  await indicator.updateComplete;

  return {
    dot: indicator.shadowRoot!.querySelector<HTMLElement>('.indicator')!,
    background: getComputedStyle(indicator.parentElement!).backgroundColor,
  };
}

describe.each(themes)('in the %s theme', (theme) => {
  it.each(surfaces)(
    'draws every solid fill at 3:1 against %s',
    async (surface) => {
      for (const fill of fills) {
        const {dot, background} = await render(
          theme,
          surface,
          `fill="${fill}"`
        );

        expect(
          contrast(getComputedStyle(dot).backgroundColor, background),
          fill
        ).toBeGreaterThanOrEqual(3);
      }
    }
  );

  it.each(surfaces)(
    'draws every hollow ring at 3:1 against %s',
    async (surface) => {
      for (const fill of fills) {
        const {dot, background} = await render(
          theme,
          surface,
          `fill="${fill}" appearance="outline"`
        );
        const style = getComputedStyle(dot);

        expect(style.backgroundColor, fill).toBe('rgba(0, 0, 0, 0)');
        expect(
          contrast(style.borderTopColor, background),
          fill
        ).toBeGreaterThanOrEqual(3);
      }
    }
  );

  it.each(['white', 'black'])(
    'rings a %s dot at 3:1 against its own fill',
    async (fill) => {
      const {dot} = await render(
        theme,
        '--c-surface-default',
        `fill="${fill}"`
      );
      const style = getComputedStyle(dot);

      expect(
        contrast(style.borderTopColor, style.backgroundColor)
      ).toBeGreaterThanOrEqual(3);
    }
  );
});
