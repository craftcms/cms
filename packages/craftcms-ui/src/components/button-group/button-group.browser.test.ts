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

it('draws a divider between filled buttons, but not beside the selected one', async () => {
  const buttons = await render(`
    <craft-button aria-pressed="false">One</craft-button>
    <craft-button aria-pressed="false">Two</craft-button>
    <craft-button aria-pressed="true">Three</craft-button>
    <craft-button aria-pressed="false">Four</craft-button>`);
  const style = (button: CraftButton) => getComputedStyle(button);
  const transparent = 'rgba(0, 0, 0, 0)';

  expect(style(buttons[1]!).borderInlineStartColor).not.toBe(transparent);
  expect(style(buttons[0]!).borderInlineEndColor).toBe(
    style(buttons[1]!).borderInlineStartColor
  );
  expect(style(buttons[1]!).borderInlineEndColor).toBe(transparent);
  expect(style(buttons[3]!).borderInlineStartColor).toBe(transparent);
});

it('keeps the divider showing when the button before it is hovered', async () => {
  const [first, second] = await render(`
    <craft-button>One</craft-button>
    <craft-button>Two</craft-button>`);

  await userEvent.hover(first!);

  expect(Number(getComputedStyle(first!).zIndex)).toBeGreaterThan(
    Number(getComputedStyle(second!).zIndex) || 0
  );
  expect(getComputedStyle(first!).borderInlineEndColor).not.toBe(
    'rgba(0, 0, 0, 0)'
  );

  await parkPointer();
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

  expect(getComputedStyle(nested!).borderInlineStartColor).not.toBe(
    'rgba(0, 0, 0, 0)'
  );
});
