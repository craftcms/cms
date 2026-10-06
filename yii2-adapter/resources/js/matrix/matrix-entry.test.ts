import jquery from 'jquery';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {MatrixEntry} from './matrix-entry';
import {MatrixInput} from './matrix-input';

afterEach(() => {
  document.body.replaceChildren();
  localStorage.clear();
  vi.unstubAllGlobals();
});

it.each(['sr-only', 'visually-hidden'])(
  'updates the accessible status scope in %s compatibility markup',
  (hiddenClass) => {
    vi.stubGlobal('$', jquery);
    vi.stubGlobal('Craft', {systemUid: 'test'});
    vi.stubGlobal('matchMedia', () => ({matches: true}));
    const matrix = new MatrixInput('unmounted-matrix', [], 'fields[blocks]');
    const container = document.createElement('div');
    container.dataset.id = '81';
    container.dataset.siteName = 'English';
    container.innerHTML = `
      <div data-matrix-block-actions>
        <span class="status"><span class="${hiddenClass}">Disabled</span></span>
      </div>
      <input name="fields[blocks][entries][81][enabled]" value="1">
      <input name="fields[blocks][entries][81][enabledForSite]" value="1">
    `;
    document.body.append(container);
    const entry = new MatrixEntry(matrix, container);

    try {
      entry.disableForSite();
      expect(container.querySelector('.status')?.textContent).toBe(
        'Disabled for English'
      );
      entry.disableGlobally();
      expect(container.querySelector('.status')?.textContent).toBe(
        'Disabled globally'
      );
    } finally {
      entry.destroy();
      matrix.destroy();
    }
  }
);
