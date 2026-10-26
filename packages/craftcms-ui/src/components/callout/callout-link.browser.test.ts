import {beforeEach, expect, it} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser';
import '../../styles/cp.css';
import './callout.js';
import type CraftCallout from './callout.js';
import {appearances} from '../../constants/appearances';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('gives links its text color and an underline until hovered, in every appearance', async () => {
  document.body.innerHTML = appearances
    .map(
      (appearance) =>
        `<craft-callout variant="info" appearance="${appearance}">Text <a href="#">Learn more</a></craft-callout>`
    )
    .join('');

  for (const callout of document.querySelectorAll<CraftCallout>(
    'craft-callout'
  )) {
    await callout.updateComplete;
    const link = callout.querySelector('a')!;
    const body = callout.shadowRoot!.querySelector('.callout')!;
    const appearance = callout.appearance;

    expect(getComputedStyle(link).color, appearance).toBe(
      getComputedStyle(body).color
    );
    expect(getComputedStyle(link).textDecorationLine, appearance).toBe(
      'underline'
    );

    await userEvent.hover(link);
    expect(getComputedStyle(link).textDecorationLine, appearance).toBe('none');
    await userEvent.unhover(link);
  }
});

it('leaves links in the action slot to their own styling', async () => {
  document.body.innerHTML = `<craft-callout variant="info">Text <a slot="action" href="#">Upgrade</a></craft-callout>`;
  const callout = document.querySelector<CraftCallout>('craft-callout')!;
  await callout.updateComplete;

  expect(getComputedStyle(callout.querySelector('a')!).textDecorationLine).toBe(
    'none'
  );
});
