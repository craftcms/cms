import {beforeEach, expect, it} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser';
import '../../styles/cp.css';
import './badge.js';
import type CraftBadge from './badge.js';
import {contrast} from '../../../test/contrast';

beforeEach(() => {
  document.body.innerHTML = '';
});

function linkColor(): string {
  const link = document.createElement('a');
  link.href = '#';
  document.body.append(link);
  const color = getComputedStyle(link).color;
  link.remove();

  return color;
}

it('gives a colored badge’s links its text color and an underline until hovered', async () => {
  document.body.innerHTML = `
    <craft-badge id="default"><a href="#">Default</a></craft-badge>
    <craft-badge id="red" fill="red"><a href="#">Red</a></craft-badge>
    <craft-badge id="black" fill="black"><a href="#">Black</a></craft-badge>
    <craft-badge id="white" fill="white"><a href="#">White</a></craft-badge>`;
  for (const badge of document.querySelectorAll<CraftBadge>('craft-badge')) {
    await badge.updateComplete;
  }

  // The default fill is gray, which blue falls short of contrast against.
  for (const id of ['default', 'red']) {
    const badge = document.getElementById(id)!;
    const link = badge.querySelector('a')!;
    const body = badge.shadowRoot!.querySelector('.badge')!;

    expect(getComputedStyle(link).color).toBe(getComputedStyle(body).color);
    expect(getComputedStyle(link).textDecorationLine).toBe('underline');

    await userEvent.hover(link);
    expect(getComputedStyle(link).textDecorationLine).toBe('none');
    await userEvent.unhover(link);
  }

  const plain = document.querySelector<HTMLElement>('#white a')!;
  expect(getComputedStyle(plain).color).toBe(linkColor());
  expect(getComputedStyle(plain).textDecorationLine).toBe('none');

  const black = document.getElementById('black')!;
  const blackLink = getComputedStyle(black.querySelector('a')!);
  expect(blackLink.colorScheme).toBe('dark');
  expect(blackLink.textDecorationLine).toBe('none');
  expect(
    contrast(
      blackLink.color,
      getComputedStyle(black.shadowRoot!.querySelector('.badge')!)
        .backgroundColor
    )
  ).toBeGreaterThanOrEqual(4.5);
});
