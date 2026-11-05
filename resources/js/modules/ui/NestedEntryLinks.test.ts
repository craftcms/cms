import {afterEach, describe, expect, it} from 'vite-plus/test';
import {nestedEntryLinks} from './nestedEntryLinks';

function side(items: string): HTMLElement {
  const element = document.createElement('div');
  element.innerHTML = items;
  document.body.append(element);

  return element;
}

const cards = (...ids: number[]) =>
  ids
    .map((id) => `<li class="element" data-id="${id}"><div></div></li>`)
    .join('');

function linksBetween(from: HTMLElement, to: HTMLElement) {
  return nestedEntryLinks(new DOMRect(), [[from, to]]);
}

describe('nestedEntryLinks', () => {
  afterEach(() => {
    document.body.innerHTML = '';
  });

  it('marks only the entry that moved when one moves to the top', () => {
    const {links, changes} = linksBetween(
      side(cards(1, 2, 3, 4)),
      side(cards(4, 1, 2, 3))
    );

    expect(links.map((link) => link.moved)).toEqual([
      true,
      false,
      false,
      false,
    ]);
    expect(changes).toEqual({moved: 1, added: 0, removed: 0});
  });

  it('keeps the entry that stayed put when the order is reversed', () => {
    const {links} = linksBetween(side(cards(1, 2, 3)), side(cards(3, 2, 1)));

    expect(links.map((link) => link.moved)).toEqual([true, false, true]);
  });

  it('matches a draft’s copy of an entry by its canonical ID, and leaves out entries nested in entries', () => {
    const from = side(`
      <li class="element" data-id="10">
        <ul><li class="element" data-id="99"><div></div></li></ul>
      </li>
      ${cards(11, 12)}
    `);
    const to = side(`
      <li class="element" data-id="20"><div data-canonical-id="10"></div></li>
      ${cards(11, 13)}
    `);

    const {links, changes} = linksBetween(from, to);

    expect(links).toHaveLength(2);
    expect(changes).toEqual({moved: 0, added: 1, removed: 1});
  });
});
