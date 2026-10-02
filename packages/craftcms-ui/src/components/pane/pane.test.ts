import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftPane from './pane.js';
import styles from './pane.styles.js';
import './pane.js';

async function createPane(
  attrs: Record<string, string> = {},
  innerHTML = 'Body'
): Promise<CraftPane> {
  const element = document.createElement('craft-pane') as CraftPane;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;
  return element;
}

function shadow(element: CraftPane, selector: string): HTMLElement | null {
  return element.shadowRoot?.querySelector(selector) ?? null;
}

function spacing(element: CraftPane): string | undefined {
  return shadow(element, '.cp-pane')?.style.getPropertyValue('--_pane-spacing');
}

/**
 * Settle the light-DOM MutationObserver and the re-render it queues.
 */
async function settle(element: CraftPane): Promise<void> {
  await new Promise((resolve) => setTimeout(resolve));
  await element.updateComplete;
}

/** The component's own stylesheet, comments stripped, for structural checks. */
const cssText = styles.cssText.replace(/\/\*[\s\S]*?\*\//g, '');

/**
 * The component's rules, keyed by selector. There's no layout engine and no
 * cascade here, so the invariants that only CSS can express — which element is
 * the scroll container, which surface vars a variant claims — are asserted
 * against the stylesheet itself.
 */
function rules(): Map<string, string> {
  const map = new Map<string, string>();
  const pattern = /([^{}]+)\{([^{}]*)\}/g;
  let match: RegExpExecArray | null;

  while ((match = pattern.exec(cssText)) !== null) {
    map.set(
      match[1].trim().replace(/\s+/g, ' '),
      match[2].replace(/\s+/g, ' ')
    );
  }

  return map;
}

/** The rule that turns an element into the pane's scroll container. */
function scrollContainerRule(): {selector: string; declarations: string} {
  for (const [selector, declarations] of rules()) {
    if (declarations.includes('overflow: var(--_pane-overflow)')) {
      return {selector, declarations};
    }
  }

  throw new Error('No rule establishes the pane scroll container.');
}

/**
 * Fake an overflowing (or comfortably fitting) scroll container. happy-dom has
 * no layout engine, so every box measures zero; the pane re-measures on
 * light-DOM mutations, which is what the appended marker triggers.
 */
async function setOverflow(
  element: CraftPane,
  overflowing: boolean
): Promise<void> {
  const scroller = shadow(element, '.cp-pane')!;
  const visible = 200;

  for (const [property, value] of [
    ['clientHeight', visible],
    ['clientWidth', visible],
    ['scrollHeight', overflowing ? 800 : visible],
    ['scrollWidth', visible],
  ] as const) {
    Object.defineProperty(scroller, property, {value, configurable: true});
  }

  element.append(document.createComment('re-measure'));
  await settle(element);
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-pane defaults', () => {
  it('defaults to the raised appearance and the plain variant', async () => {
    const element = await createPane();

    expect(element.appearance).toBe('raised');
    expect(element.variant).toBe('plain');
    expect(element.getAttribute('appearance')).toBe('raised');
    expect(element.getAttribute('variant')).toBe('plain');
  });

  it('renders the body region with the default slot', async () => {
    const element = await createPane();

    const body = shadow(element, '.cp-pane__body');
    expect(body).not.toBeNull();
    expect(body!.querySelector('slot:not([name])')).not.toBeNull();
  });
});

describe('craft-pane header/footer presence', () => {
  it('renders no header or footer chrome for a bare body', async () => {
    const element = await createPane();

    expect(shadow(element, '.cp-pane__header')).toBeNull();
    expect(shadow(element, '.cp-pane__footer')).toBeNull();
  });

  it('renders the header for the label attribute', async () => {
    const element = await createPane({label: 'Settings'});

    expect(shadow(element, '.cp-pane__header')).not.toBeNull();
    expect(shadow(element, '.cp-pane__title')?.textContent).toContain(
      'Settings'
    );
  });

  it('renders the header for slotted title/header-actions content', async () => {
    const element = await createPane(
      {},
      '<span slot="header-actions">Action</span>Body'
    );
    await settle(element);

    expect(shadow(element, '.cp-pane__header')).not.toBeNull();
    expect(shadow(element, '.cp-pane__title')).toBeNull();
  });

  it.each([
    'footer',
    'footer-content',
    'feedback',
    'actions',
    'primary-action',
    'secondary-action',
  ])('renders the footer for slotted %s content', async (slotName) => {
    const element = await createPane(
      {},
      `Body<div slot="${slotName}">Footer bit</div>`
    );
    await settle(element);

    expect(shadow(element, '.cp-pane__footer')).not.toBeNull();
  });

  it('tracks header and footer content added after first render', async () => {
    const element = await createPane();
    expect(shadow(element, '.cp-pane__footer')).toBeNull();

    const action = document.createElement('button');
    action.slot = 'primary-action';
    action.textContent = 'Save';
    element.append(action);
    await settle(element);

    expect(shadow(element, '.cp-pane__footer')).not.toBeNull();

    action.remove();
    await settle(element);

    expect(shadow(element, '.cp-pane__footer')).toBeNull();
  });

  it('ignores matching slot names on nested elements', async () => {
    const element = await createPane(
      {},
      '<div><span slot="footer">Nested card footer</span></div>'
    );
    await settle(element);

    expect(shadow(element, '.cp-pane__footer')).toBeNull();
  });

  it('nests the default footer layout inside footer-content and actions', async () => {
    const element = await createPane({}, '<span slot="feedback">Saved</span>');
    await settle(element);

    const footerContent = shadow(element, 'slot[name="footer-content"]');
    expect(footerContent).not.toBeNull();
    expect(
      footerContent!.querySelector('slot[name="feedback"]')
    ).not.toBeNull();

    const actions = footerContent!.querySelector('slot[name="actions"]');
    expect(actions).not.toBeNull();
    expect(
      actions!.querySelector('slot[name="secondary-action"]')
    ).not.toBeNull();
    expect(
      actions!.querySelector('slot[name="primary-action"]')
    ).not.toBeNull();
  });
});

describe('craft-pane padding', () => {
  it('maps named steps onto spacing tokens', async () => {
    for (const step of ['sm', 'md', 'lg', 'xl']) {
      const element = await createPane({padding: step});
      expect(spacing(element)).toBe(`var(--c-spacing-${step})`);
    }
  });

  it('defaults to lg', async () => {
    const element = await createPane();

    expect(spacing(element)).toBe('var(--c-spacing-lg)');
  });

  it('zeroes out spacing for 0', async () => {
    const element = await createPane({padding: '0'});

    expect(spacing(element)).toBe('0');
  });

  /**
   * `none` is the same instruction spelled the way markup reaches for it, and
   * a bare `none` isn't a valid length — passed through it would drop the
   * declaration and leave the default padding standing.
   */
  it('zeroes out spacing for none', async () => {
    const element = await createPane({padding: 'none'});

    expect(spacing(element)).toBe('0');
  });

  /**
   * The attribute is closed to the spacing scale. An off-scale value writes
   * nothing, so `--c-pane-padding` and the stylesheet default still apply —
   * which is the escape hatch for arbitrary spacing.
   */
  it.each(['24', '2rem', 'var(--my-spacing)'])(
    'ignores %s, which is off the spacing scale',
    async (padding) => {
      const element = await createPane({padding});

      expect(spacing(element)).toBe('');
    }
  );

  it('re-renders when the padding property changes', async () => {
    const element = await createPane();

    element.padding = 0;
    await element.updateComplete;

    expect(spacing(element)).toBe('0');
  });
});

describe('craft-pane appearance and variant', () => {
  it('reflects appearance and variant so they can be combined', async () => {
    const element = await createPane({appearance: 'outline', variant: 'code'});

    expect(element.getAttribute('appearance')).toBe('outline');
    expect(element.getAttribute('variant')).toBe('code');
  });

  it('exposes the surface element as the base part', async () => {
    const element = await createPane();

    expect(shadow(element, '[part="base"]')).not.toBeNull();
  });
});

describe('craft-pane code variant surface', () => {
  it('resets the shadow it would otherwise inherit from the appearance', () => {
    // The variant block follows the appearance block at equal specificity, so
    // anything it doesn't re-declare survives — `raised` (the default) would
    // leave a drop shadow under every code well.
    expect(rules().get(":host([variant='code'])")).toContain(
      '--_pane-shadow: var(--c-pane-shadow, none);'
    );
  });

  it('keeps the raised appearance shadow for ordinary panes', () => {
    expect(rules().get(":host([appearance='raised'])")).toContain(
      '--_pane-shadow: var(--c-shadow-raised);'
    );
  });

  it('claims the rest of its own surface treatment', () => {
    const code = rules().get(":host([variant='code'])")!;

    expect(code).toContain('--_pane-background:');
    expect(code).toContain('--_pane-border-color:');
    expect(code).toContain('--_pane-overflow: auto;');
    expect(code).toContain('--_pane-max-block-size:');
  });
});

describe('craft-pane keyboard scrolling', () => {
  it('makes the scroll container itself the tab stop', async () => {
    const element = await createPane({variant: 'code'}, '<pre>config</pre>');
    await setOverflow(element, true);

    const focusable = element.shadowRoot!.querySelector('[tabindex="0"]');
    expect(focusable).not.toBeNull();

    // The whole bug: a browser scrolls the focused element or its nearest
    // scrollable *ancestor*, never a descendant. So the element that takes
    // focus has to be the element the stylesheet makes scrollable and clamps —
    // not the host wrapped around it.
    const {selector, declarations} = scrollContainerRule();
    expect(selector.startsWith(':host')).toBe(false);
    expect(focusable!.matches(selector)).toBe(true);
    expect(declarations).toContain(
      'max-block-size: var(--_pane-max-block-size);'
    );
  });

  it('never asks the host to carry the tab stop', async () => {
    const element = await createPane({variant: 'code'}, '<pre>config</pre>');
    await setOverflow(element, true);

    expect(element.hasAttribute('tabindex')).toBe(false);
    expect(shadow(element, '.cp-pane')?.getAttribute('tabindex')).toBe('0');
  });

  it('exposes the scroll container as a labelled region', async () => {
    const element = await createPane({variant: 'code'}, '<pre>config</pre>');
    await setOverflow(element, true);

    const scroller = shadow(element, '.cp-pane')!;
    expect(scroller.getAttribute('role')).toBe('region');
    expect(scroller.getAttribute('aria-label')).toBe('Scrollable region');
  });

  it('names the region after the pane label', async () => {
    const element = await createPane({variant: 'code', label: 'Loaded config'});
    await setOverflow(element, true);

    expect(shadow(element, '.cp-pane')?.getAttribute('aria-label')).toBe(
      'Loaded config'
    );
  });

  it('names the region after the host aria-label', async () => {
    const element = await createPane({
      variant: 'code',
      'aria-label': 'Project config diff',
    });
    await setOverflow(element, true);

    expect(shadow(element, '.cp-pane')?.getAttribute('aria-label')).toBe(
      'Project config diff'
    );
  });

  it('leaves a pane whose content fits out of the tab order', async () => {
    const element = await createPane({variant: 'code'}, '<pre>short</pre>');
    await setOverflow(element, false);

    const scroller = shadow(element, '.cp-pane')!;
    expect(scroller.hasAttribute('tabindex')).toBe(false);
    expect(scroller.hasAttribute('role')).toBe(false);
  });

  it('adds and drops the tab stop as the content grows and shrinks', async () => {
    const element = await createPane({variant: 'code'}, '<pre>short</pre>');
    await setOverflow(element, false);
    expect(shadow(element, '.cp-pane')?.hasAttribute('tabindex')).toBe(false);

    await setOverflow(element, true);
    expect(shadow(element, '.cp-pane')?.getAttribute('tabindex')).toBe('0');

    await setOverflow(element, false);
    expect(shadow(element, '.cp-pane')?.hasAttribute('tabindex')).toBe(false);
  });

  it('forwards focus() to the scroll container', async () => {
    const element = await createPane({variant: 'code'}, '<pre>config</pre>');
    await setOverflow(element, true);

    element.focus();

    expect(element.shadowRoot!.activeElement).toBe(shadow(element, '.cp-pane'));
  });

  it('draws the focus ring on the scroll container, not the host', () => {
    const {selector} = scrollContainerRule();
    const ring = rules().get(`${selector}:focus-visible`);

    expect(ring).toBeDefined();
    expect(ring).toContain(
      'outline: var(--c-focus-outline-width) solid var(--c-color-focus-outline);'
    );
    // Inset, so the ring can't be clipped by an ancestor — and because it's on
    // the element that carries `border-radius`, it traces the pane's corners
    // instead of boxing the unrounded host.
    expect(ring).toContain(
      'outline-offset: calc(-1 * var(--c-focus-outline-width));'
    );
    expect(rules().get(scrollContainerRule().selector)).toContain(
      'border-radius: var(--_pane-radius);'
    );
  });

  it('rounds the host outline for consumers that still focus the host', () => {
    expect(rules().get(':host(:focus-visible)')).toContain(
      'border-radius: var(--_pane-radius);'
    );
  });
});
