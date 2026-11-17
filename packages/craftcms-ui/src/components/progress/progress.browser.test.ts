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
