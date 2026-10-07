import {expect} from 'vite-plus/test';

/**
 * Asserts that `element` is what the browser actually paints at its center and
 * just inside each corner, so nothing stacked above it covers it (WCAG 2.4.11
 * Focus Not Obscured).
 *
 * Use this instead of `toBeVisible()` when the question is "can a person see
 * it?" — `toBeVisible()` only checks size, `display` and `visibility`, so an
 * element hidden under a header bar still passes. This needs real layout, so
 * call it from a `*.browser.test.ts` file.
 */
export function expectUnobscured(element: Element, inset = 2): void {
  element.scrollIntoView({block: 'nearest', inline: 'nearest'});

  const rect = element.getBoundingClientRect();
  expect(
    rect.width > inset * 2 && rect.height > inset * 2,
    `${describeElement(element)} has no area to show`
  ).toBe(true);

  const root = element.getRootNode() as Document | ShadowRoot;
  const points: Array<[string, number, number]> = [
    ['center', rect.left + rect.width / 2, rect.top + rect.height / 2],
    ['top start', rect.left + inset, rect.top + inset],
    ['top end', rect.right - inset, rect.top + inset],
    ['bottom start', rect.left + inset, rect.bottom - inset],
    ['bottom end', rect.right - inset, rect.bottom - inset],
  ];

  const covered = points.flatMap(([name, x, y]) => {
    const hit = root.elementFromPoint(x, y);

    return hit && (hit === element || element.contains(hit))
      ? []
      : [`${name}: ${hit ? describeElement(hit) : 'outside the viewport'}`];
  });

  expect(covered, `${describeElement(element)} is covered`).toEqual([]);
}

function describeElement(element: Element): string {
  const id = element.id ? `#${element.id}` : '';
  const classes = [...element.classList].map((name) => `.${name}`).join('');
  const text = element.textContent?.trim().slice(0, 40);

  return `<${element.localName}${id}${classes}>${text ? ` "${text}"` : ''}`;
}
