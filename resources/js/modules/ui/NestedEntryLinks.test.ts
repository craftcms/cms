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

/** The IDs the links connect, by the item on the “to” side. */
const linkedIds = (links: ReturnType<typeof linksBetween>['links']) =>
  links.map((link) => link.items[1].dataset.id);

describe('nestedEntryLinks', () => {
  afterEach(() => {
    document.body.innerHTML = '';
  });

  it('connects only the entry that moved when one moves to the top', () => {
    const {links, changes} = linksBetween(
      side(cards(1, 2, 3, 4)),
      side(cards(4, 1, 2, 3))
    );

    expect(linkedIds(links)).toEqual(['4']);
    expect(changes).toEqual({moved: 1, added: 0, removed: 0});
  });

  it('leaves out the entry that stayed put when the order is reversed', () => {
    const {links} = linksBetween(side(cards(1, 2, 3)), side(cards(3, 2, 1)));

    expect(linkedIds(links)).toEqual(['3', '1']);
  });

  it('connects nothing when entries only shift around an added one', () => {
    const {links, changes} = linksBetween(
      side(cards(1, 2, 3)),
      side(cards(9, 1, 2, 3))
    );

    expect(links).toEqual([]);
    expect(changes).toEqual({moved: 0, added: 1, removed: 0});
  });

  it('matches a draft’s copy of an entry by its canonical ID, and leaves out entries nested in entries', () => {
    const from = side(`
      <li class="element" data-id="10">
        <ul><li class="element" data-id="99"><div></div></li></ul>
      </li>
      ${cards(11, 12)}
    `);
    const to = side(`
      ${cards(11)}
      <li class="element" data-id="20"><div data-canonical-id="10"></div></li>
      ${cards(13)}
    `);

    const {links, changes} = linksBetween(from, to);

    expect(links).toHaveLength(1);
    expect(changes).toEqual({moved: 1, added: 1, removed: 1});
  });

  it('connects the facing edges when the sides are in a right-to-left layout', () => {
    const at = (element: Element, left: number, right: number, top = 0) => {
      element.getBoundingClientRect = () =>
        new DOMRect(left, top, right - left, 20);
    };
    const from = side(cards(1, 2));
    const to = side(cards(2, 1));
    at(from, 500, 900);
    at(to, 0, 400);
    from
      .querySelectorAll('li')
      .forEach((item, index) => at(item, 520, 600, index * 40));
    to.querySelectorAll('li').forEach((item, index) =>
      at(item, 20, 100, index * 40)
    );

    const {links} = linksBetween(from, to);

    expect(linkedIds(links)).toEqual(['1']);
    // From the left edge of the older side’s item, along to the edge of its
    // field, then curving to the right edge of the newer side’s item.
    expect(links[0]!.ends).toEqual([
      [520, 10],
      [100, 50],
    ]);
    expect(links[0]!.path).toBe('M 520 10 H 500 C 300 10, 300 50, 100 50');
  });

  it('compares each relation field nested in the field on its own', () => {
    const chips = (...ids: number[]) =>
      ids
        .map(
          (id) =>
            `<li class="element-chips__item"><craft-chip data-id="${id}"></craft-chip></li>`
        )
        .join('');
    const block = (a: number[], b: number[]) => `
      <div data-layout-element="block">
        <div data-layout-element="relation-a"><ul>${chips(...a)}</ul></div>
        <div data-layout-element="relation-b"><ul>${chips(...b)}</ul></div>
      </div>
    `;

    const {links, changes} = linksBetween(
      side(block([1, 2], [1])),
      side(block([2], [1]))
    );

    expect(links).toEqual([]);
    expect(changes).toEqual({moved: 0, added: 0, removed: 1});
  });
});
