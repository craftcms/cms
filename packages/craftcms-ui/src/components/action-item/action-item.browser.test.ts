import {expect, it} from 'vite-plus/test';
import {contrast} from '../../../test/contrast';
import {Color} from '../../constants/colors';
import type CraftActionItem from './action-item.js';
import './action-item.js';

const colors = Object.values(Color);

it.each(['light', 'dark'])(
  'sets a colored icon in the color’s loud tone, or black and white in the text color, at 3:1 against the surface in the %s theme',
  async (theme) => {
    await import('../../styles/cp.css');
    document.body.innerHTML = `
      <div data-theme="${theme}" style="background-color: var(--c-surface-raised)">
        ${colors
          .map(
            (color) =>
              `<craft-action-item icon="star" icon-color="${color}">${color}</craft-action-item>`
          )
          .join('')}
      </div>`;
    const surface = getComputedStyle(
      document.querySelector('[data-theme]')!
    ).backgroundColor;
    const failures: string[] = [];

    for (const item of document.querySelectorAll<CraftActionItem>(
      'craft-action-item'
    )) {
      await item.updateComplete;
      const color = item.getAttribute('icon-color')!;
      const icon = item.shadowRoot!.querySelector('craft-icon')!;
      const reference = document.createElement('span');
      reference.style.color = ['black', 'white'].includes(color)
        ? 'var(--c-text-default)'
        : `var(--c-color-${color}-fill-loud)`;
      document.querySelector('[data-theme]')!.append(reference);

      expect(getComputedStyle(icon).color, color).toBe(
        getComputedStyle(reference).color
      );
      const ratio = contrast(getComputedStyle(icon).color, surface);
      if (ratio < 3) {
        failures.push(`${color}: ${ratio.toFixed(2)}`);
      }
    }

    expect(failures).toEqual([]);
  }
);
