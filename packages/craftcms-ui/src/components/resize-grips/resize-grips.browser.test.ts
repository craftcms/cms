import {beforeEach, expect, it} from 'vite-plus/test';
import type CraftResizeGrips from './resize-grips.js';
import type {ResizeGripsSize} from './resize-grips.js';
import './resize-grips.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function render(direction: 'ltr' | 'rtl' = 'ltr') {
  document.body.innerHTML = `
    <div id="box" dir="${direction}" style="position: relative; box-sizing: border-box; width: 400px; height: 300px; max-width: 600px; max-height: 500px">
      <craft-resize-grips></craft-resize-grips>
    </div>`;
  const box = document.getElementById('box')!;
  const grips = box.querySelector<CraftResizeGrips>('craft-resize-grips')!;
  await grips.updateComplete;
  const sizes: ResizeGripsSize[] = [];
  grips.addEventListener('craft-resize', (event) => {
    const {detail} = event as CustomEvent<ResizeGripsSize>;
    sizes.push(detail);
    box.style.width = detail.width === null ? '400px' : `${detail.width}px`;
    box.style.height = detail.height === null ? '300px' : `${detail.height}px`;
  });
  const grip = (name: 'inline' | 'block' | 'corner') =>
    grips.shadowRoot!.querySelector<HTMLElement>(`.grip--${name}`)!;

  return {box, grips, grip, sizes, last: () => sizes.at(-1)};
}

function drag(grip: HTMLElement, dx: number, dy: number): void {
  const pointer = (type: string, x: number, y: number) =>
    grip.dispatchEvent(
      new PointerEvent(type, {
        bubbles: true,
        composed: true,
        pointerId: 1,
        button: 0,
        clientX: x,
        clientY: y,
      })
    );
  pointer('pointerdown', 100, 100);
  pointer('pointermove', 100 + dx, 100 + dy);
  pointer('pointerup', 100 + dx, 100 + dy);
}

it('resizes the box by both of its edges, one axis from each edge and both from the corner', async () => {
  const {grip, last} = await render();

  drag(grip('inline'), 20, 50);
  expect(last()).toEqual({width: 440, height: null});

  drag(grip('block'), 50, 20);
  expect(last()).toEqual({width: 440, height: 340});

  drag(grip('corner'), -10, -10);
  expect(last()).toEqual({width: 420, height: 320});
});

it('keeps the size within the box’s max size and min-size', async () => {
  const {grip, last} = await render();

  drag(grip('corner'), 500, 500);
  expect(last()).toEqual({width: 600, height: 500});

  drag(grip('corner'), -500, -500);
  expect(last()).toEqual({width: 200, height: 200});
});

it('mirrors the inline axis in right-to-left text', async () => {
  const {grip, last} = await render('rtl');

  drag(grip('inline'), -20, 0);
  expect(last()).toEqual({width: 440, height: null});
});

it('lets only the corner grip take focus, resizing by arrow keys and resetting with Enter', async () => {
  const {grips, grip, last} = await render();
  const key = (name: string, shiftKey = false) =>
    grip('corner').dispatchEvent(
      new KeyboardEvent('keydown', {
        key: name,
        shiftKey,
        bubbles: true,
        cancelable: true,
      })
    );

  expect(grip('inline').tabIndex).toBe(-1);
  expect(grip('block').tabIndex).toBe(-1);
  expect(grip('inline').getAttribute('aria-hidden')).toBe('true');
  expect(grip('block').getAttribute('aria-hidden')).toBe('true');
  expect(grip('corner').localName).toBe('button');
  expect(grip('corner').getAttribute('aria-label')).toBe('Resize');

  grip('corner').focus();
  expect(grips.shadowRoot!.activeElement).toBe(grip('corner'));

  key('ArrowRight');
  expect(last()).toEqual({width: 416, height: 300});
  key('ArrowDown', true);
  expect(last()).toEqual({width: 416, height: 364});

  key('Enter');
  expect(last()).toEqual({width: null, height: null});
});

it('resets on a double-click', async () => {
  const {grip, last} = await render();

  drag(grip('corner'), 20, 20);
  grip('block').dispatchEvent(new MouseEvent('dblclick', {bubbles: true}));

  expect(last()).toEqual({width: null, height: null});
});

it('clamps a resized box again when a window resize lowers its cap', async () => {
  const {box, grip, last} = await render();

  drag(grip('corner'), 100, 100);
  expect(last()).toEqual({width: 600, height: 500});

  box.style.maxWidth = '500px';
  window.dispatchEvent(new Event('resize'));

  expect(last()).toEqual({width: 500, height: 500});
});
