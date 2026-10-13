import {beforeEach, expect, it} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser';
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

/** Moves the pointer to an empty corner, so nothing it was left over stays hovered. */
async function parkPointer(): Promise<void> {
  const spot = document.createElement('div');
  spot.style.cssText =
    'position: fixed; inset-block-end: 0; inset-inline-end: 0; width: 10px; height: 10px;';
  document.body.append(spot);
  await userEvent.hover(spot);
  spot.remove();
}

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

it.each([
  ['single', 'default', ''],
  ['multiple', 'pointer', ' multiple'],
])(
  'gives a %s-selection group’s selected button a %s cursor',
  async (_, cursor, attrs) => {
    document.body.innerHTML = `
    <craft-button-group name="mode"${attrs}>
      <craft-button value="a" active>A</craft-button>
      <craft-button value="b">B</craft-button>
    </craft-button-group>`;
    const group = document.querySelector('craft-button-group')!;
    await group.updateComplete;
    await settle();
    const [selected, other] = Array.from(
      document.querySelectorAll('craft-button')
    );

    expect(getComputedStyle(selected!).cursor).toBe(cursor);
    expect(getComputedStyle(other!).cursor).toBe('pointer');
  }
);

it('overlaps adjacent buttons’ borders, with no gap', async () => {
  const [first, second] = await render(`
    <craft-button variant="outline">One</craft-button>
    <craft-button variant="outline">Two</craft-button>`);
  const border = parseFloat(getComputedStyle(first!).borderInlineEndWidth);

  expect(border).toBeGreaterThan(0);
  expect(
    first!.getBoundingClientRect().right - second!.getBoundingClientRect().left
  ).toBeCloseTo(border);
});

it('raises the selected button, so its whole border shows', async () => {
  const [unselected, selected] = await render(`
    <craft-button variant="outline" aria-pressed="false">One</craft-button>
    <craft-button variant="outline" aria-pressed="true">Two</craft-button>`);
  await parkPointer();

  expect(Number(getComputedStyle(selected!).zIndex)).toBeGreaterThan(
    Number(getComputedStyle(unselected!).zIndex) || 0
  );
});

const transparent = 'rgba(0, 0, 0, 0)';

/**
 * The colors a button's divider gradients draw at its left and right edges,
 * read from the first stop of each background layer.
 */
function dividers(button: Element): {left: string; right: string} {
  const image = getComputedStyle(button).backgroundImage;
  const layers: string[] = [];
  let depth = 0;
  let current = '';
  for (const char of image) {
    if (char === ',' && depth === 0) {
      layers.push(current.trim());
      current = '';
      continue;
    }
    depth += char === '(' ? 1 : char === ')' ? -1 : 0;
    current += char;
  }
  layers.push(current.trim());

  const firstStop = (layer: string, direction: string) => {
    const body = layer.slice(layer.indexOf('(') + 1, -1);
    const afterDirection = body.slice(
      body.indexOf(direction) + direction.length
    );
    return afterDirection
      .replace(/^\s*,\s*/, '')
      .replace(/\s+1px.*$/, '')
      .trim();
  };

  return {
    left: firstStop(
      layers.find((layer) => layer.includes('to right'))!,
      'to right'
    ),
    right: firstStop(
      layers.find((layer) => layer.includes('to left'))!,
      'to left'
    ),
  };
}

it('draws a divider between filled buttons, but not beside the selected one', async () => {
  const buttons = await render(`
    <craft-button aria-pressed="false">One</craft-button>
    <craft-button aria-pressed="false">Two</craft-button>
    <craft-button aria-pressed="true">Three</craft-button>
    <craft-button aria-pressed="false">Four</craft-button>`);

  expect(dividers(buttons[1]!).left).not.toBe(transparent);
  expect(dividers(buttons[0]!).right).toBe(dividers(buttons[1]!).left);
  expect(dividers(buttons[1]!).right).toBe(transparent);
  expect(dividers(buttons[3]!).left).toBe(transparent);
});

it('draws the divider as a full-height line rather than a side border', async () => {
  const [first, second] = await render(`
    <craft-button>One</craft-button>
    <craft-button>Two</craft-button>`);

  expect(getComputedStyle(second!).borderInlineStartColor).toBe(transparent);
  expect(getComputedStyle(first!).borderInlineEndColor).toBe(transparent);
  expect(getComputedStyle(second!).backgroundOrigin).toContain('border-box');
});

it('keeps the divider showing when the button before it is hovered', async () => {
  const [first, second] = await render(`
    <craft-button>One</craft-button>
    <craft-button>Two</craft-button>`);

  await userEvent.hover(first!);

  expect(Number(getComputedStyle(first!).zIndex)).toBeGreaterThan(
    Number(getComputedStyle(second!).zIndex) || 0
  );
  expect(dividers(first!).right).not.toBe(transparent);

  await parkPointer();
});

it('draws the divider on the left edge in right-to-left text', async () => {
  document.body.innerHTML = `
    <craft-button-group dir="rtl">
      <craft-button>One</craft-button>
      <craft-button>Two</craft-button>
    </craft-button-group>`;
  const group = document.querySelector('craft-button-group')!;
  const [first, second] = Array.from(document.querySelectorAll('craft-button'));
  await Promise.all([group.updateComplete, first!.updateComplete]);
  await settle();

  expect(dividers(second!).right).not.toBe(transparent);
  expect(dividers(second!).left).toBe(transparent);
  expect(dividers(first!).left).not.toBe(transparent);
});

it('draws the divider on a button nested in a grouped element', async () => {
  document.body.innerHTML = `
    <craft-button-group>
      <craft-button variant="primary">Save</craft-button>
      <span style="display: inline-flex">
        <craft-button variant="primary" icon="chevron-down"></craft-button>
      </span>
    </craft-button-group>`;
  const group = document.querySelector('craft-button-group')!;
  const [, nested] = Array.from(document.querySelectorAll('craft-button'));
  await Promise.all([group.updateComplete, nested!.updateComplete]);
  await settle();

  expect(dividers(nested!).left).not.toBe(transparent);
});
