import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftCallout from './callout.js';
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

function shadow(
  element: CraftCallout,
  selector: string
): HTMLElement | null {
  return element.shadowRoot?.querySelector(selector) ?? null;
}

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

    expect(
      shadow(element, '.callout')?.getAttribute('data-variant')
    ).toBe('danger');
  });

  it('follows a variant change', async () => {
    const element = await createCallout({variant: 'danger'});

    element.variant = 'success';
    await element.updateComplete;

    expect(
      shadow(element, '.callout')?.getAttribute('data-variant')
    ).toBe('success');
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
