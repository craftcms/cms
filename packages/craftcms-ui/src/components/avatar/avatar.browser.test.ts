import {expect, it} from 'vite-plus/test';
import {contrast} from '../../../test/contrast';
import type CraftAvatar from './avatar.js';
import './avatar.js';

it.each(['light', 'dark'])(
  'draws its initials at 4.5:1 against both ends of the disc in the %s theme',
  async (theme) => {
    await import('../../styles/cp.css');
    document.body.innerHTML = `
      <div data-theme="${theme}" style="background-color: var(--c-surface-raised)">
        <craft-avatar label="Ada Lovelace"></craft-avatar>
      </div>`;
    const avatar = document.querySelector<CraftAvatar>('craft-avatar')!;
    await avatar.updateComplete;

    const root = avatar.shadowRoot!;
    const disc = getComputedStyle(
      root.querySelector('.avatar')!
    ).backgroundColor;
    const opacity = Number(
      root.querySelector('circle')!.getAttribute('opacity') ?? 1
    );
    const initials = getComputedStyle(
      root.querySelector('.avatar__text')!
    ).fill;

    for (const stop of root.querySelectorAll('stop')) {
      const tint = getComputedStyle(stop)
        .stopColor.replace(/^rgb\(/, 'rgba(')
        .replace(/\)$/, `, ${opacity})`);

      expect(contrast(initials, tint, disc)).toBeGreaterThanOrEqual(4.5);
    }
  }
);
