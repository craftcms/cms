import {beforeEach, expect, it, vi} from 'vite-plus/test';
import './progress.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

function nextFrame(): Promise<number> {
  return new Promise((resolve) => requestAnimationFrame(resolve));
}

async function moveAfterRender(progress: string) {
  document.body.innerHTML = `
    <craft-progress progress="${progress}"></craft-progress>
    <div id="elsewhere"></div>`;
  const element = document.querySelector('craft-progress')!;
  await element.updateComplete;

  return {
    element,
    canvas: element.shadowRoot!.querySelector('canvas')!,
    move: () => document.getElementById('elsewhere')!.append(element),
  };
}

it('keeps an indeterminate indicator moving after it is moved', async () => {
  const {canvas, move} = await moveAfterRender('-1');

  move();
  await nextFrame();
  const drawn = canvas.toDataURL();

  await vi.waitFor(() => expect(canvas.toDataURL()).not.toBe(drawn));
});

it('finishes the complete animation when moved partway through it', async () => {
  const {element, move} = await moveAfterRender('40');
  let completed = false;
  element.addEventListener('craft-complete', () => (completed = true));

  void element.complete();
  await nextFrame();
  move();

  await vi.waitFor(() => expect(completed).toBe(true), {timeout: 3000});
});

it('draws its arc in the color it inherits', async () => {
  document.body.innerHTML = `<craft-progress progress="100" style="color: rgb(0, 128, 0)"></craft-progress>`;
  const element = document.querySelector('craft-progress')!;
  await element.updateComplete;
  await nextFrame();

  const canvas = element.shadowRoot!.querySelector('canvas')!;
  const [red, green, blue] = canvas
    .getContext('2d')!
    .getImageData(canvas.width / 2, 1, 1, 1).data;

  expect([red, green, blue]).toEqual([0, 128, 0]);
});

it('draws its track in a color given as a design token', async () => {
  document.body.innerHTML = `<craft-progress progress="0" style="--track: rgb(0, 0, 255); --c-progress-track-color: var(--track)"></craft-progress>`;
  const element = document.querySelector('craft-progress')!;
  await element.updateComplete;
  await nextFrame();

  const canvas = element.shadowRoot!.querySelector('canvas')!;
  const [red, green, blue] = canvas
    .getContext('2d')!
    .getImageData(canvas.width / 2, 1, 1, 1).data;

  expect([red, green, blue]).toEqual([0, 0, 255]);
});

it('draws at a size given with calc()', async () => {
  document.body.innerHTML = `<craft-progress progress="50" style="--c-progress-size: calc(24rem / 16)"></craft-progress>`;
  const element = document.querySelector('craft-progress')!;
  await element.updateComplete;

  const canvas = element.shadowRoot!.querySelector('canvas')!;

  expect(canvas.width).toBe(24 * (window.devicePixelRatio > 1 ? 2 : 1));
});
