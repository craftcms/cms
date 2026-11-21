import {beforeAll, describe, expect, it} from 'vite-plus/test';

import {backdropOf, contrast, loadTokens} from '../../../test/contrast';
import './status.js';
import type CraftStatus from './status.js';

beforeAll(loadTokens);

const surfaces = [
  '--c-surface-default',
  '--c-surface-sunken',
  '--c-surface-page',
  '--c-surface-details',
  '--c-color-accent-fill-quiet',
];

/**
 * The colored states' fills are as saturated as their hue allows while still
 * reaching 3:1 by themselves, since their border matches the fill. Disabled
 * keeps a pale fill and relies on its darker border.
 */
describe.each(['light', 'dark'])('in the %s theme', (theme) => {
  it.each(['live', 'enabled', 'pending', 'expired'])(
    'gives a %s dot a fill that reaches 3:1 on every surface',
    async (status) => {
      document.body.innerHTML = `
        <div data-theme="${theme}">
          ${surfaces
            .map(
              (surface) =>
                `<div style="background: var(${surface}); padding: 4px">
                  <craft-status status="${status}"></craft-status>
                </div>`
            )
            .join('')}
          <div style="background: var(--c-surface-default)">
            <div style="background: var(--c-table-row-hover); padding: 4px">
              <craft-status status="${status}"></craft-status>
            </div>
          </div>
        </div>`;
      const dots = [...document.querySelectorAll<CraftStatus>('craft-status')];
      await Promise.all(dots.map((dot) => dot.updateComplete));

      const measured = dots.map((element) => {
        const dot = element.shadowRoot!.querySelector('.status')!;
        const {backgroundColor, borderTopColor} = getComputedStyle(dot);
        const backdrop = backdropOf(dot);

        return {
          backdrop,
          borderTopColor,
          backgroundColor,
          ratio: contrast(backgroundColor, backdrop),
        };
      });

      for (const {backdrop, ratio} of measured) {
        expect(ratio, `${status} on ${backdrop}`).toBeGreaterThanOrEqual(3);
      }
      for (const {backgroundColor, borderTopColor} of measured) {
        expect(borderTopColor).toBe(backgroundColor);
      }
    }
  );
});
