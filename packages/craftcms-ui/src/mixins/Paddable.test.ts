import {html, LitElement} from 'lit';
import {styleMap} from 'lit/directives/style-map.js';
import {beforeEach, describe, expect, it} from 'vite-plus/test';
import {Paddable, resolvePadding, SPACING_STEPS} from './Paddable.js';

/**
 * Two hosts, because the mixin's whole job is to let each component keep its
 * own custom property name and its own default: one writes a single property
 * and defaults to a value, the other writes a pair and defaults to nothing.
 */
class SinglePropertyHost extends Paddable(LitElement, {
  customProperty: '--_test-spacing',
  defaultValue: 'lg',
}) {
  protected override render() {
    return html`<div
      class="box"
      style="${styleMap(this.paddingStyles)}"
    ></div>`;
  }
}

class TwoAxisHost extends Paddable(LitElement, {
  customProperty: ['--_test-block', '--_test-inline'],
}) {
  protected override render() {
    return html`<div
      class="box"
      style="${styleMap(this.paddingStyles)}"
    ></div>`;
  }
}

customElements.define('test-single-padding', SinglePropertyHost);
customElements.define('test-two-axis-padding', TwoAxisHost);

async function create<T extends LitElement>(
  tag: string,
  padding?: string
): Promise<T> {
  const element = document.createElement(tag) as unknown as T;

  if (padding !== undefined) {
    element.setAttribute('padding', padding);
  }

  document.body.append(element);
  await element.updateComplete;
  return element;
}

/**
 * happy-dom doesn't resolve custom properties through `getComputedStyle`, so
 * these assert what the component writes — the inline custom property — rather
 * than the length it would compute to in a browser.
 */
function box(element: LitElement): HTMLElement {
  return element.shadowRoot!.querySelector('.box')!;
}

function written(element: LitElement, property: string): string {
  return box(element).style.getPropertyValue(property);
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('resolvePadding', () => {
  it.each([...SPACING_STEPS])('maps the %s step onto its token', (step) => {
    expect(resolvePadding(step)).toBe(`var(--c-spacing-${step})`);
  });

  it.each([0, '0', 'none'] as const)('collapses %s to zero', (value) => {
    expect(resolvePadding(value)).toBe('0');
  });

  /**
   * The attribute is closed to arbitrary lengths. Ignoring them leaves the
   * stylesheet's own default in place, rather than writing a value the design
   * system does not define — consumers who need one set the component's
   * padding custom properties instead.
   */
  it.each([24, '24', '0.5', '2rem', 'var(--my-spacing)', 'calc(1rem + 2px)'])(
    'ignores %s, which is off the spacing scale',
    (value) => {
      expect(resolvePadding(value)).toBeUndefined();
    }
  );

  /**
   * The signal for "don't write anything" — the stylesheet's own fallback is
   * left to apply, which is how a component keeps an asymmetric default.
   */
  it.each([undefined, null, ''] as const)(
    'resolves %s to nothing at all',
    (value) => {
      expect(resolvePadding(value)).toBeUndefined();
    }
  );
});

describe('Paddable with a default', () => {
  it('writes the default without an attribute', async () => {
    const element = await create<SinglePropertyHost>('test-single-padding');

    expect(element.padding).toBe('lg');
    expect(written(element, '--_test-spacing')).toBe('var(--c-spacing-lg)');
  });

  it('writes the resolved value to the nominated property', async () => {
    const element = await create<SinglePropertyHost>(
      'test-single-padding',
      'none'
    );

    expect(written(element, '--_test-spacing')).toBe('0');
  });

  it('re-renders when the property changes', async () => {
    const element = await create<SinglePropertyHost>('test-single-padding');

    element.padding = 'xl';
    await element.updateComplete;

    expect(written(element, '--_test-spacing')).toBe('var(--c-spacing-xl)');
  });

  /** Removing the attribute has to clear the value, not strand the last one. */
  it('drops back to nothing when the attribute is removed', async () => {
    const element = await create<SinglePropertyHost>(
      'test-single-padding',
      'sm'
    );
    expect(written(element, '--_test-spacing')).toBe('var(--c-spacing-sm)');

    element.removeAttribute('padding');
    await element.updateComplete;

    expect(written(element, '--_test-spacing')).toBe('');
  });
});

describe('Paddable without a default', () => {
  it('writes nothing until the attribute is set', async () => {
    const element = await create<TwoAxisHost>('test-two-axis-padding');

    expect(element.padding).toBeUndefined();
    expect(element.paddingStyles).toEqual({});
    expect(box(element).getAttribute('style')).toBe('');
  });

  it('writes the same value to every nominated property', async () => {
    const element = await create<TwoAxisHost>('test-two-axis-padding', 'md');

    expect(written(element, '--_test-block')).toBe('var(--c-spacing-md)');
    expect(written(element, '--_test-inline')).toBe('var(--c-spacing-md)');
  });
});
