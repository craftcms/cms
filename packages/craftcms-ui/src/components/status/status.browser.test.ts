import {afterEach, beforeAll, expect, it} from 'vite-plus/test';
import {loadTokens} from '../../../test/contrast';
import {renderedShape} from '../../../test/shape';
import './status.js';
import type CraftStatus from './status.js';

beforeAll(loadTokens);

afterEach(() => {
  document.body.innerHTML = '';
  document.body.classList.remove('use-shapes');
});

async function renderStatus(status: string): Promise<HTMLElement> {
  document.body.innerHTML = `<craft-status status="${status}" style="--size: 60px"></craft-status>`;
  const element = document.querySelector<CraftStatus>('craft-status')!;
  await element.updateComplete;

  return element.shadowRoot!.querySelector<HTMLElement>('.status')!;
}

it.each([
  ['live', 'circle'],
  ['enabled', 'circle'],
  ['disabled', 'circle'],
  ['pending', 'triangle'],
  ['expired', 'square'],
])(
  'draws a %s dot as a %s with the use-shapes preference on',
  async (status, shape) => {
    document.body.classList.add('use-shapes');

    expect(renderedShape(await renderStatus(status))).toBe(shape);
  }
);

it.each(['pending', 'expired'])(
  'draws a %s dot as a circle with the use-shapes preference off',
  async (status) => {
    expect(renderedShape(await renderStatus(status))).toBe('circle');
  }
);
