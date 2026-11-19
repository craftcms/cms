import {expect} from 'vite-plus/test';
import {backdropOf, contrast, isPainted, over} from './contrast';

/**
 * Hovers the element that `mount` returns on each surface, in `theme`, and
 * expects its text to keep 4.5:1 (WCAG 1.4.3) against what's then painted
 * behind it: its own background and its `::before`, over the surfaces it sits
 * on.
 *
 * A case where hovering paints no fill fails too, so a fill that stops
 * applying (a renamed token, a hover media query that doesn't match) can't
 * pass by measuring the text against the bare surface. Every failing case is
 * listed in one assertion.
 */
export async function expectReadableOnHover(
  mount: (surface: HTMLElement) => Promise<HTMLElement>,
  {theme, surfaces}: {theme: string; surfaces: string[]}
): Promise<void> {
  const {userEvent} = await import('vite-plus/test/browser');
  const failures: string[] = [];

  for (const surface of surfaces) {
    const wrapper = document.createElement('div');
    wrapper.dataset.theme = theme;
    wrapper.style.backgroundColor = `var(--c-surface-${surface})`;
    wrapper.style.padding = '2rem';
    document.body.append(wrapper);

    const target = await mount(wrapper);
    const label = `surface-${surface}`;
    const hoverLayers = () => {
      const own = getComputedStyle(target).backgroundColor;
      const before = getComputedStyle(target, '::before');
      return before.content === 'none' ? [own] : [own, before.backgroundColor];
    };
    const hadTestId = 'testid' in target.dataset;

    // Vitest and Playwright can name a target differently, so hover by test id.
    target.dataset.testid ??= 'hover-target';

    try {
      await userEvent.hover(target);

      try {
        await expect.poll(() => hoverLayers().some(isPainted)).toBe(true);
      } catch {
        failures.push(`${label}: hovering paints no fill`);
        continue;
      }

      let backdrop = backdropOf(target);
      const layers = hoverLayers();
      for (const layer of layers.slice(0, -1)) {
        backdrop = over(layer, backdrop);
      }
      const measured = contrast(
        getComputedStyle(target).color,
        layers.at(-1)!,
        backdrop
      );

      if (measured < 4.5) {
        failures.push(`${label}: ${measured.toFixed(2)}:1`);
      }
    } finally {
      await userEvent.unhover(target);
      if (!hadTestId) {
        delete target.dataset.testid;
      }
      wrapper.remove();
    }
  }

  expect(failures, `text below 4.5:1 on hover, ${theme} theme`).toEqual([]);
}
