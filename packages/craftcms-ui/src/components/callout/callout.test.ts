import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftCallout from './callout.js';
import styles from './callout.styles.js';
import './callout.js';

async function createCallout(
  attrs: Record<string, string> = {},
  innerHTML = 'Body'
): Promise<CraftCallout> {
  const element = document.createElement('craft-callout') as CraftCallout;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;
  return element;
}

function shadow(element: CraftCallout, selector: string): HTMLElement | null {
  return element.shadowRoot?.querySelector(selector) ?? null;
}

/**
 * The custom property the callout writes for an axis. happy-dom doesn't
 * resolve custom properties through `getComputedStyle`, so what's asserted is
 * what the component writes, not the length it computes to.
 */
function spacing(element: CraftCallout, axis: 'block' | 'inline'): string {
  return (
    shadow(element, '.callout')?.style.getPropertyValue(
      `--_callout-padding-${axis}`
    ) ?? ''
  );
}

/**
 * The component's own stylesheet, comments and whitespace stripped, for the
 * invariants only CSS can express.
 */
const cssText = styles.cssText
  .replace(/\/\*[\s\S]*?\*\//g, '')
  .replace(/\s+/g, '');

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-callout variant', () => {
  /**
   * The palette is applied to the callout itself rather than left to the host,
   * so `callout.styles.ts` must not set the `--c-color-*` tokens on `.callout`
   * — same specificity as the shared sheet's `[data-variant]` rule, and it
   * would win on source order.
   */
  it('hands its variant to the element that draws it', async () => {
    const element = await createCallout({variant: 'danger'});

    expect(shadow(element, '.callout')?.getAttribute('data-variant')).toBe(
      'danger'
    );
  });

  it('follows a variant change', async () => {
    const element = await createCallout({variant: 'danger'});

    element.variant = 'success';
    await element.updateComplete;

    expect(shadow(element, '.callout')?.getAttribute('data-variant')).toBe(
      'success'
    );
  });
});

describe('craft-callout padding', () => {
  /**
   * The shipped look is asymmetric, so the default has to stay in the
   * stylesheet: with no `padding` attribute the component writes nothing and
   * the fallbacks below are what applies.
   */
  it('writes nothing when no padding is set', async () => {
    const element = await createCallout();

    expect(element.padding).toBeUndefined();
    expect(spacing(element, 'block')).toBe('');
    expect(spacing(element, 'inline')).toBe('');
  });

  /**
   * So the shipped appearance is byte-identical to the `padding: sm md`
   * shorthand this replaced: the pair still comes from the stylesheet, and the
   * attribute only ever overrides it.
   */
  it('keeps the asymmetric default in the stylesheet', () => {
    expect(cssText).toContain(
      '--_callout-padding-block:var(--c-callout-padding-block,var(--c-spacing-sm));'
    );
    expect(cssText).toContain(
      '--_callout-padding-inline:var(--c-callout-padding-inline,var(--c-spacing-md));'
    );
    expect(cssText).toContain(
      'padding:var(--_callout-padding-block)var(--_callout-padding-inline);'
    );
  });

  it('pads both axes from a single value', async () => {
    const element = await createCallout({padding: 'lg'});

    expect(spacing(element, 'block')).toBe('var(--c-spacing-lg)');
    expect(spacing(element, 'inline')).toBe('var(--c-spacing-lg)');
  });

  /** What markup reaches for to mean "no padding here". */
  it('collapses none to zero on both axes', async () => {
    const element = await createCallout({padding: 'none'});

    expect(spacing(element, 'block')).toBe('0');
    expect(spacing(element, 'inline')).toBe('0');
  });

  it.each([
    ['0', '0'],
    ['24', 'calc(24rem / 16)'],
    ['2rem', '2rem'],
    ['var(--my-spacing)', 'var(--my-spacing)'],
  ])('resolves %s to %s', async (padding, expected) => {
    const element = await createCallout({padding});

    expect(spacing(element, 'block')).toBe(expected);
  });

  it('re-renders when the padding property changes', async () => {
    const element = await createCallout();

    element.padding = 'md';
    await element.updateComplete;

    expect(spacing(element, 'inline')).toBe('var(--c-spacing-md)');
  });
});

describe('craft-callout icon', () => {
  it('collapses the icon column when the variant has no default icon', async () => {
    const element = await createCallout({variant: 'neutral'});

    expect(shadow(element, '.callout--hide-icon')).not.toBeNull();
    expect(shadow(element, 'craft-icon')).toBeNull();
  });

  it("falls back to the variant's icon", async () => {
    const element = await createCallout({variant: 'warning'});

    expect(shadow(element, '.callout--hide-icon')).toBeNull();
    expect(shadow(element, 'craft-icon')?.getAttribute('name')).toBe(
      'circle-exclamation'
    );
  });

  it('prefers an icon that was set over the variant default', async () => {
    const element = await createCallout({variant: 'warning', icon: 'lock'});

    expect(shadow(element, 'craft-icon')?.getAttribute('name')).toBe('lock');
  });

  /** The column follows the icon, so a variant change has to re-resolve it. */
  it('re-resolves the icon when the variant changes', async () => {
    const element = await createCallout({variant: 'neutral'});
    expect(shadow(element, '.callout--hide-icon')).not.toBeNull();

    element.variant = 'success';
    await element.updateComplete;

    expect(shadow(element, '.callout--hide-icon')).toBeNull();
    expect(shadow(element, 'craft-icon')?.getAttribute('name')).toBe(
      'circle-check'
    );
  });

  /** Artwork the consumer brings counts, even with no name to resolve. */
  it('keeps the column for a slotted icon on an iconless variant', async () => {
    const element = await createCallout(
      {variant: 'neutral'},
      '<svg slot="icon"></svg>Body'
    );

    expect(shadow(element, '.callout--hide-icon')).toBeNull();
    expect(shadow(element, 'slot[name="icon"]')).not.toBeNull();
  });

  it('still honors hide-icon', async () => {
    const element = await createCallout({variant: 'warning', 'hide-icon': ''});

    expect(shadow(element, '.callout--hide-icon')).not.toBeNull();
    expect(shadow(element, 'slot[name="icon"]')).toBeNull();
  });
});
