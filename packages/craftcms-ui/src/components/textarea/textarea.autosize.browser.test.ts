import {beforeAll, expect, it, vi} from 'vite-plus/test';
import type CraftTextarea from './textarea.js';
import {loadTokens} from '../../../test/contrast';

// Sizes with the `autosize` fallback, as browsers without `field-sizing` do.
beforeAll(async () => {
  const supports = CSS.supports.bind(CSS);
  vi.spyOn(CSS, 'supports').mockImplementation((...args: [string, string?]) =>
    args[0] === 'field-sizing' ? false : supports(...(args as [string]))
  );
  await import('./textarea.js');
  await loadTokens();
});

function borderBoxHeight(textarea: HTMLTextAreaElement, rows: number): number {
  const style = getComputedStyle(textarea);

  return (
    rows * parseFloat(style.lineHeight) +
    parseFloat(style.paddingTop) +
    parseFloat(style.paddingBottom) +
    parseFloat(style.borderTopWidth) +
    parseFloat(style.borderBottomWidth)
  );
}

it.each([
  ['capped', '4', 4],
  ['unlimited', 'none', 10],
])(
  're-measures a %s height when its size changes',
  async (_name, maxRows, rows) => {
    const element = document.createElement('craft-textarea') as CraftTextarea;
    element.label = 'Notes';
    element.setAttribute('rows', '2');
    element.setAttribute('max-rows', maxRows);
    document.body.append(element);
    await element.updateComplete;
    element.value = Array.from({length: 10}, () => 'Line').join('\n');
    await element.updateComplete;
    const textarea = element.querySelector('textarea')!;

    for (const size of ['large', 'medium']) {
      element.setAttribute('size', size);
      await element.updateComplete;

      expect(textarea.getBoundingClientRect().height).toBeCloseTo(
        borderBoxHeight(textarea, rows),
        0
      );
    }
  }
);
