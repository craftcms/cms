import {expect, it} from 'vite-plus/test';
import {withScrollableTables} from './prose';

function render(html: string): HTMLElement {
  const root = document.createElement('div');
  root.innerHTML = withScrollableTables(html);
  return root;
}

it('wraps each table in a focusable, named scrolling region and leaves it a table', () => {
  const root = render(
    '<p>Intro</p><table><caption>Transforms</caption><tr><th>Width</th></tr></table><table><tr><td>1</td></tr></table>'
  );
  const regions = root.querySelectorAll<HTMLElement>('.cp-prose-table');

  expect(regions).toHaveLength(2);
  expect(regions[0]!.getAttribute('role')).toBe('region');
  expect(regions[0]!.tabIndex).toBe(0);
  expect(regions[0]!.getAttribute('aria-label')).toBe('Transforms');
  expect(regions[1]!.getAttribute('aria-label')).toBe('Table');
  expect(regions[0]!.firstElementChild?.tagName).toBe('TABLE');
  expect(root.querySelector('th')?.textContent).toBe('Width');
  expect(root.firstElementChild?.outerHTML).toBe('<p>Intro</p>');
});

it('leaves prose without tables as it was', () => {
  const html = '<p>No <a href="https://example.com">tables</a> here.</p>';

  expect(withScrollableTables(html)).toBe(html);
});
