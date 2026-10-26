/// <reference types="vite-plus/test/browser/providers/playwright" />
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {cdp, userEvent} from 'vite-plus/test/browser';
import {contrast} from '../../../test/contrast';
import type CraftActionMenu from '../action-menu/action-menu.js';
import type CraftReorderButton from './reorder-button.js';
import './reorder-button.js';
import '../../styles/cp.css';

interface FrameTree {
  frame: {id: string};
  childFrames?: FrameTree[];
}

interface AXNode {
  ignored: boolean;
  role?: {value: string};
  name?: {value: unknown};
  properties?: {name: string; value: {value: unknown}}[];
}

interface AXButton {
  name: string;
  disabled: boolean;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

afterEach(async () => {
  await cdp().send('Emulation.setEmulatedMedia', {features: []});
});

/** The buttons Chromium exposes in the accessibility tree, across every frame. */
async function accessibleButtons(): Promise<AXButton[]> {
  const session = cdp();
  const {frameTree} = (await session.send('Page.getFrameTree')) as {
    frameTree: FrameTree;
  };
  const frameIds: string[] = [];
  const collect = (tree: FrameTree) => {
    frameIds.push(tree.frame.id);
    tree.childFrames?.forEach(collect);
  };
  collect(frameTree);

  const buttons: AXButton[] = [];
  for (const frameId of frameIds) {
    const {nodes} = (await session.send('Accessibility.getFullAXTree', {
      frameId,
    })) as {nodes: AXNode[]};
    for (const node of nodes) {
      if (!node.ignored && node.role?.value === 'button') {
        buttons.push({
          name: String(node.name?.value ?? ''),
          disabled: !!node.properties?.some(
            (property) =>
              property.name === 'disabled' && property.value.value === true
          ),
        });
      }
    }
  }

  return buttons;
}

function surface(theme: string): HTMLElement {
  const wrapper = document.createElement('div');
  wrapper.dataset.theme = theme;
  wrapper.style.backgroundColor = 'var(--c-surface-default)';
  wrapper.style.padding = '2rem';
  document.body.append(wrapper);

  return wrapper;
}

async function renderButton(html: string): Promise<CraftReorderButton> {
  document.body.innerHTML = html;
  const button = document.querySelector<CraftReorderButton>(
    'craft-reorder-button'
  )!;
  await button.updateComplete;
  return button;
}

function invokerOf(button: CraftReorderButton): HTMLElement {
  return button.shadowRoot!.querySelector<HTMLElement>('craft-button')!;
}

function menuOf(button: CraftReorderButton): CraftActionMenu {
  return button.shadowRoot!.querySelector<CraftActionMenu>(
    'craft-action-menu'
  )!;
}

async function openMenu(button: CraftReorderButton): Promise<void> {
  invokerOf(button).focus();
  await userEvent.keyboard('{Enter}');
  await menuOf(button).updateComplete;
}

async function chooseMoveDown(button: CraftReorderButton): Promise<void> {
  button
    .shadowRoot!.querySelector<HTMLElement>('[data-action="moveDown"]')!
    .focus();
  await userEvent.keyboard('{Enter}');
  await menuOf(button).updateComplete;
}

it('keeps focus on the invoker after a move is chosen with Enter', async () => {
  const button = await renderButton(
    '<craft-reorder-button position="middle"></craft-reorder-button>'
  );

  await openMenu(button);
  await chooseMoveDown(button);

  expect(menuOf(button).opened).toBe(false);
  expect(button.shadowRoot!.activeElement).toBe(invokerOf(button));
});

it('keeps focus on the invoker when its list disables the chosen move', async () => {
  const button = await renderButton(
    '<craft-reorder-button position="first"></craft-reorder-button>'
  );
  button.addEventListener('craft-reorder', () => {
    button.position = 'last';
  });

  await openMenu(button);
  await chooseMoveDown(button);

  expect(menuOf(button).opened).toBe(false);
  expect(button.shadowRoot!.activeElement).toBe(invokerOf(button));
});

it('returns focus to the invoker when Escape closes the menu', async () => {
  const button = await renderButton(
    '<craft-reorder-button position="middle"></craft-reorder-button>'
  );

  await openMenu(button);
  button
    .shadowRoot!.querySelector<HTMLElement>('[data-action="moveDown"]')!
    .focus();
  await userEvent.keyboard('{Escape}');
  await menuOf(button).updateComplete;

  expect(menuOf(button).opened).toBe(false);
  expect(button.shadowRoot!.activeElement).toBe(invokerOf(button));
});

it('keeps focus on the invoker when its list moves it', async () => {
  const button = await renderButton(
    '<ul><li id="previous">Previous</li><li id="moved"><craft-reorder-button></craft-reorder-button></li></ul>'
  );

  invokerOf(button).focus();
  document
    .getElementById('previous')!
    .before(document.getElementById('moved')!);

  expect(document.activeElement).toBe(button);
  expect(button.shadowRoot!.activeElement).toBe(invokerOf(button));
});

it('leaves focus alone when its list moves it after focus left for somewhere unfocusable', async () => {
  const button = await renderButton(
    '<ul><li id="previous">Previous</li><li id="moved"><craft-reorder-button></craft-reorder-button></li></ul><p id="elsewhere">Elsewhere</p>'
  );

  await userEvent.click(invokerOf(button));
  await userEvent.keyboard('{Escape}');
  await userEvent.click(document.getElementById('elsewhere')!);
  await new Promise((resolve) => setTimeout(resolve));
  document
    .getElementById('previous')!
    .before(document.getElementById('moved')!);

  expect(document.activeElement).toBe(document.body);
});

it('leaves focus alone when it is removed and added back later', async () => {
  const button = await renderButton(
    '<ul><li id="item"><craft-reorder-button></craft-reorder-button></li></ul>'
  );
  const item = document.getElementById('item')!;

  invokerOf(button).focus();
  item.remove();
  await new Promise((resolve) => setTimeout(resolve));
  document.querySelector('ul')!.append(item);

  expect(document.activeElement).toBe(document.body);
});

it.each([
  {label: null, name: 'Reorder'},
  {label: 'Reorder Blog post', name: 'Reorder Blog post'},
])('names the invoker $name', async ({label, name}) => {
  const button = await renderButton(
    '<craft-reorder-button></craft-reorder-button>'
  );
  button.label = label;
  await button.updateComplete;

  await vi.waitFor(
    async () =>
      expect(await accessibleButtons()).toContainEqual({
        name,
        disabled: false,
      }),
    {timeout: 5000}
  );
});

it.each([
  {position: 'first', up: true, down: false},
  {position: 'middle', up: false, down: false},
  {position: 'last', up: false, down: true},
  {position: 'only', up: true, down: true},
])(
  'exposes a move that would go nowhere as disabled at $position',
  async ({position, up, down}) => {
    const button = await renderButton(
      `<craft-reorder-button position="${position}"></craft-reorder-button>`
    );
    menuOf(button).opened = true;
    await menuOf(button).updateComplete;

    await vi.waitFor(
      async () =>
        expect(await accessibleButtons()).toEqual(
          expect.arrayContaining([
            {name: 'Move up', disabled: up},
            {name: 'Move down', disabled: down},
          ])
        ),
      {timeout: 5000}
    );
  }
);

it.each([
  {orientation: 'vertical', names: ['Move up', 'Move down']},
  {orientation: 'horizontal', names: ['Move forward', 'Move backward']},
])(
  'names each $orientation move by its text alone',
  async ({orientation, names}) => {
    const button = await renderButton(
      `<craft-reorder-button orientation="${orientation}"></craft-reorder-button>`
    );
    menuOf(button).opened = true;
    await menuOf(button).updateComplete;

    await vi.waitFor(
      async () =>
        expect((await accessibleButtons()).map(({name}) => name)).toEqual(
          expect.arrayContaining(names)
        ),
      {timeout: 5000}
    );
  }
);

it('disables the invoker and marks the host aria-disabled when disabled', async () => {
  const button = await renderButton(
    '<craft-reorder-button disabled></craft-reorder-button>'
  );

  expect(button.getAttribute('aria-disabled')).toBe('true');
  await vi.waitFor(
    async () =>
      expect(await accessibleButtons()).toContainEqual({
        name: 'Reorder',
        disabled: true,
      }),
    {timeout: 5000}
  );
});

it.each([
  {key: 'Enter', press: '{Enter}'},
  {key: 'Space', press: '[Space]'},
])('is reached with Tab and opens with $key', async ({press}) => {
  const button = await renderButton(
    '<button type="button" id="before">Before</button><craft-reorder-button></craft-reorder-button>'
  );
  document.getElementById('before')!.focus();

  await userEvent.keyboard('{Tab}');
  expect(button.shadowRoot!.activeElement).toBe(invokerOf(button));

  await userEvent.keyboard(press);
  await expect.poll(() => menuOf(button).opened).toBe(true);
});

it('is skipped by Tab when disabled', async () => {
  await renderButton(
    '<button type="button" id="before">Before</button><craft-reorder-button disabled></craft-reorder-button><button type="button" id="after">After</button>'
  );
  document.getElementById('before')!.focus();

  await userEvent.keyboard('{Tab}');

  expect(document.activeElement).toBe(document.getElementById('after'));
});

it('is at least 24×24 CSS px', async () => {
  const button = await renderButton(
    '<craft-reorder-button></craft-reorder-button>'
  );
  const {width, height} = invokerOf(button).getBoundingClientRect();

  expect(width).toBeGreaterThanOrEqual(24);
  expect(height).toBeGreaterThanOrEqual(24);
});

async function focusInSurface(
  wrapper: HTMLElement
): Promise<CraftReorderButton> {
  wrapper.innerHTML =
    '<button type="button" id="before">Before</button><craft-reorder-button></craft-reorder-button>';
  const button = wrapper.querySelector('craft-reorder-button')!;
  await button.updateComplete;
  wrapper.querySelector<HTMLElement>('#before')!.focus();
  await userEvent.keyboard('{Tab}');

  return button;
}

describe.each(['light', 'dark'])('in the %s theme', (theme) => {
  it('rings a keyboard-focused invoker at 3:1 against the surface', async () => {
    const wrapper = surface(theme);
    const button = await focusInSurface(wrapper);
    const style = getComputedStyle(invokerOf(button));

    expect(button.shadowRoot!.activeElement).toBe(invokerOf(button));
    expect(style.outlineStyle).toBe('solid');
    expect(
      contrast(style.outlineColor, getComputedStyle(wrapper).backgroundColor)
    ).toBeGreaterThanOrEqual(3);
  });

  it('draws the grip icon at 3:1 against the surface', async () => {
    const wrapper = surface(theme);
    wrapper.innerHTML = '<craft-reorder-button></craft-reorder-button>';
    const button = wrapper.querySelector('craft-reorder-button')!;
    await button.updateComplete;
    const icon = button.shadowRoot!.querySelector('craft-icon')!;

    expect(
      contrast(
        getComputedStyle(icon).color,
        getComputedStyle(wrapper).backgroundColor
      )
    ).toBeGreaterThanOrEqual(3);
  });
});

it('keeps the grip icon and focus ring visible in forced colors mode', async () => {
  await cdp().send('Emulation.setEmulatedMedia', {
    features: [{name: 'forced-colors', value: 'active'}],
  });
  const wrapper = surface('light');
  const button = await focusInSurface(wrapper);
  const background = getComputedStyle(wrapper).backgroundColor;
  const ring = getComputedStyle(invokerOf(button));
  const icon = getComputedStyle(
    button.shadowRoot!.querySelector('craft-icon')!
  );

  expect(matchMedia('(forced-colors: active)').matches).toBe(true);
  expect(ring.outlineStyle).not.toBe('none');
  expect(contrast(ring.outlineColor, background)).toBeGreaterThanOrEqual(3);
  expect(contrast(icon.color, background)).toBeGreaterThanOrEqual(3);
});
