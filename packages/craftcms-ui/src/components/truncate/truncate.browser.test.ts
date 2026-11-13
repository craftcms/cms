import {beforeEach, expect, it} from 'vite-plus/test';
import './truncate.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it.each([
  [
    'left-to-right text in a right-to-left page',
    'rtl',
    'Porting the element index to Vue',
  ],
  [
    'right-to-left text in a left-to-right page',
    'ltr',
    'مقارنة العناصر في لوحة التحكم الخاصة بالموقع',
  ],
])(
  'keeps the start of %s visible when it overflows',
  async (_name, dir, text) => {
    document.body.innerHTML = `
      <div dir="${dir}" style="display: flex; width: 120px">
        <craft-truncate>${text}</craft-truncate>
      </div>`;
    const truncate = document.querySelector('craft-truncate')!;
    await truncate.updateComplete;

    const clip = truncate
      .shadowRoot!.querySelector('.truncate')!
      .getBoundingClientRect();
    const range = document.createRange();
    range.setStart(truncate.firstChild!, 0);
    range.setEnd(truncate.firstChild!, 1);
    const first = range.getBoundingClientRect();

    expect(first.left).toBeGreaterThanOrEqual(clip.left);
    expect(first.right).toBeLessThanOrEqual(clip.right);
  }
);
