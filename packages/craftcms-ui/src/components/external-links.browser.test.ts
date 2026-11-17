import {afterEach, expect, it} from 'vite-plus/test';
import '../styles/cp.css';
import {openExternalLinksInNewTabs} from '../utilities/external-links';

afterEach(() => {
  document.body.innerHTML = '';
});

function iconOf(link: HTMLAnchorElement): CSSStyleDeclaration {
  return getComputedStyle(link, '::after');
}

it('shows an external-link icon after a text link that opens in a new tab', () => {
  document.body.innerHTML =
    '<p>See <a href="https://craftcms.com/" style="color: rgb(0, 0, 255)">the docs</a>.</p>';
  const link = document.querySelector('a')!;

  openExternalLinksInNewTabs(document.body);

  const icon = iconOf(link);
  expect(icon.content).toBe('""');
  expect(icon.backgroundColor).toBe('rgb(0, 0, 255)');
  expect(parseFloat(icon.width)).toBeGreaterThan(0);
});

it.each([
  ['a link to this host', '<a href="/admin">Dashboard</a>'],
  [
    'a link with its own target',
    '<a href="https://craftcms.com/" target="_blank">Docs</a>',
  ],
  [
    'a link with its own icon',
    '<a href="https://craftcms.com/">Docs <craft-icon name="external"></craft-icon></a>',
  ],
  [
    'an image link',
    '<a href="https://craftcms.com/"><img alt="Craft" src="data:,"></a>',
  ],
])('shows no icon on %s', (_case, html) => {
  document.body.innerHTML = html;
  const link = document.querySelector('a')!;

  openExternalLinksInNewTabs(document.body);

  expect(iconOf(link).content).toBe('none');
});
