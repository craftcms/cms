import {expect, it} from 'vite-plus/test';
import {scheduleMeasure} from './frame.js';

const nextFrame = () =>
  new Promise((resolve) => requestAnimationFrame(resolve));

it('runs every queued read before any of their writes', async () => {
  const box = document.createElement('div');
  const seen: string[] = [];

  const measure = (name: string) => () => {
    seen.push(`${name} read ${box.dataset.written ?? 'nothing'}`);
    return () => {
      box.dataset.written = name;
    };
  };
  scheduleMeasure(measure('a'));
  scheduleMeasure(measure('b'));
  await nextFrame();

  expect(seen).toEqual(['a read nothing', 'b read nothing']);
  expect(box.dataset.written).toBe('b');
});

it('runs a function queued twice before its frame once', async () => {
  let runs = 0;
  const measure = () => {
    runs++;
  };
  scheduleMeasure(measure);
  scheduleMeasure(measure);
  await nextFrame();

  expect(runs).toBe(1);
});

it('runs a measure queued by a write in the following frame', async () => {
  const seen: string[] = [];
  const second = () => {
    seen.push('second');
  };
  scheduleMeasure(() => () => {
    seen.push('first');
    scheduleMeasure(second);
  });
  await nextFrame();
  expect(seen).toEqual(['first']);

  await nextFrame();
  expect(seen).toEqual(['first', 'second']);
});
