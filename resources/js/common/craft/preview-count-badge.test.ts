import {afterEach, expect, it, vi} from 'vite-plus/test';

const loadElementThumbs = vi.hoisted(() => vi.fn());
vi.mock('@/modules/element-thumb-loader/globals', () => ({loadElementThumbs}));

await import('./preview-count-badge');

function preview(thumbs: boolean): HTMLButtonElement {
  document.body.innerHTML = `<div id="preview"><a href="#">First</a><button data-preview-count-badge="${thumbs ? 'thumbs' : ''}">+2</button></div>`;
  const badge = document.querySelector('button')!;
  badge.dataset.other = JSON.stringify(
    '<a href="#" id="second">Second</a><a href="#">Third</a>'
  );

  return badge;
}

afterEach(() => {
  document.body.innerHTML = '';
  loadElementThumbs.mockReset();
});

it('swaps itself for the rest of the chips, focusing the first of them', () => {
  preview(true).click();

  expect(document.querySelector('#preview')!.textContent).toBe(
    'FirstSecondThird'
  );
  expect(document.activeElement?.id).toBe('second');
  expect(loadElementThumbs).toHaveBeenCalledWith(
    document.querySelector('#preview')
  );
});

it('expands from the keyboard, without loading thumbnails it doesn’t need', () => {
  preview(false).dispatchEvent(
    new KeyboardEvent('keydown', {key: 'Enter', bubbles: true})
  );

  expect(document.querySelector('#second')).not.toBeNull();
  expect(loadElementThumbs).not.toHaveBeenCalled();
});
