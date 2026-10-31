import {beforeEach, expect, it} from 'vite-plus/test';
import '../../styles/cp.css';
import type CraftInput from './input.js';
import './input.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function nativeInput(html: string): Promise<HTMLInputElement> {
  document.body.innerHTML = html;
  const element = document.querySelector<CraftInput>('craft-input')!;
  await element.updateComplete;

  return element.querySelector('input')!;
}

it('keeps its control border in a table cell', async () => {
  const standalone = await nativeInput(
    '<craft-input label="Title"><input slot="input" /></craft-input>'
  );
  const standaloneBorder = getComputedStyle(standalone).borderTopWidth;

  const inTable = await nativeInput(`
    <table class="cp-table">
      <tbody>
        <tr>
          <td><craft-input label="Title"><input slot="input" /></craft-input></td>
        </tr>
      </tbody>
    </table>`);

  expect(standaloneBorder).not.toBe('0px');
  expect(getComputedStyle(inTable).borderTopWidth).toBe(standaloneBorder);
});

it('fills its cell without a border in an editable table', async () => {
  const input = await nativeInput(`
    <table class="cp-table cp-table--editable">
      <tbody>
        <tr>
          <td><craft-input label="Title" label-sr-only><input slot="input" /></craft-input></td>
        </tr>
      </tbody>
    </table>`);

  expect(getComputedStyle(input).borderTopWidth).toBe('0px');
});
