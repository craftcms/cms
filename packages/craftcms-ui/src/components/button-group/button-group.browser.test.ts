import {beforeEach, expect, it} from 'vite-plus/test';
import type CraftButton from '../button/button.js';
import '../button/button.js';
import './button-group.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function render(markup: string): Promise<CraftButton[]> {
  document.body.innerHTML = `<craft-button-group style="--c-button-radius: 4px">${markup}</craft-button-group>`;
  const group = document.querySelector('craft-button-group')!;
  const buttons = Array.from(document.querySelectorAll('craft-button'));
  await Promise.all([
    group.updateComplete,
    ...buttons.map((button) => button.updateComplete),
  ]);
  await settle();

  return buttons;
}

const settle = () => new Promise((resolve) => setTimeout(resolve));

function corners(button: CraftButton): {start: boolean; end: boolean} {
  const style = getComputedStyle(button);

  return {
    start: parseFloat(style.borderStartStartRadius) > 0,
    end: parseFloat(style.borderStartEndRadius) > 0,
  };
}

it('rounds the outer corners of the first and last buttons', async () => {
  const [first, middle, last] = await render(`
    <craft-button>One</craft-button>
    <craft-button>Two</craft-button>
    <craft-button>Three</craft-button>`);

  expect(corners(first!)).toEqual({start: true, end: false});
  expect(corners(middle!)).toEqual({start: false, end: false});
  expect(corners(last!)).toEqual({start: false, end: true});
});

it('rounds the first visible button when hidden elements come before it', async () => {
  const [first, last] = await render(`
    <input type="hidden" name="value" value="">
    <div style="display: none"></div>
    <craft-button>Save</craft-button>
    <craft-button>More</craft-button>`);

  expect(corners(first!)).toEqual({start: true, end: false});
  expect(corners(last!)).toEqual({start: false, end: true});
});

it('joins a button rendered into a placeholder after the group mounts', async () => {
  const [last] = await render(`
    <div id="placeholder" style="display: none"></div>
    <craft-button>More</craft-button>`);

  const placeholder = document.getElementById('placeholder')!;
  const button = document.createElement('craft-button');
  button.textContent = 'Save';
  placeholder.append(button);
  placeholder.style.display = 'contents';
  await button.updateComplete;
  await settle();

  expect(corners(button)).toEqual({start: true, end: false});
  expect(corners(last!)).toEqual({start: false, end: true});
});
