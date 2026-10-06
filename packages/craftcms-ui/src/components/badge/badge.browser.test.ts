import {beforeAll, beforeEach, describe, expect, it} from 'vite-plus/test';
import {contrast, loadTokens} from '../../../test/contrast';
import {Color} from '@src/constants/colors';
import './badge.js';
import type CraftBadge from './badge.js';

const fills = [...new Set(Object.values(Color))];

beforeAll(loadTokens);

beforeEach(() => {
  document.body.innerHTML = '';
});

async function render(theme: string, fill: string): Promise<CraftBadge> {
  document.body.innerHTML = `
    <div data-theme="${theme}">
      <craft-badge fill="${fill}">Label</craft-badge>
    </div>`;
  const badge = document.querySelector('craft-badge')!;
  await badge.updateComplete;
  await badge.shadowRoot!.querySelector('craft-indicator')!.updateComplete;

  return badge;
}

function dotOf(badge: CraftBadge): HTMLElement {
  return badge
    .shadowRoot!.querySelector('craft-indicator')!
    .shadowRoot!.querySelector<HTMLElement>('.indicator')!;
}

it('leaves the default indicator unnamed, so the label is announced once', async () => {
  const badge = await render('light', 'emerald');
  const dot = dotOf(badge);

  expect(dot.getAttribute('role')).toBeNull();
  expect(dot.getAttribute('aria-label')).toBeNull();
});

describe.each(['light', 'dark'])('in the %s theme', (theme) => {
  it('sets the label at 4.5:1 against the badge for every fill', async () => {
    for (const fill of fills) {
      const badge = await render(theme, fill);
      const style = getComputedStyle(
        badge.shadowRoot!.querySelector('.badge')!
      );

      expect(
        contrast(style.color, style.backgroundColor),
        fill
      ).toBeGreaterThanOrEqual(4.5);
    }
  });

  it('draws the indicator at 3:1 against the badge for every fill', async () => {
    for (const fill of fills) {
      const badge = await render(theme, fill);
      const surface = getComputedStyle(
        badge.shadowRoot!.querySelector('.badge')!
      ).backgroundColor;
      const dot = getComputedStyle(dotOf(badge));
      const edge =
        dot.borderTopColor === 'rgba(0, 0, 0, 0)'
          ? dot.backgroundColor
          : dot.borderTopColor;

      expect(contrast(edge, surface), fill).toBeGreaterThanOrEqual(3);
    }
  });
});
