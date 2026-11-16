import {afterAll, beforeAll} from 'vite-plus/test';

/**
 * The checks `Element.checkVisibility()` makes, for happy-dom, which doesn't
 * implement it. Like the real method, an element isn't visible when it has no
 * box: it isn't in the document, it's `display: contents`, or it or something
 * it renders inside is `display: none`, it's in a closed `<details>` other than
 * its summary, or an ancestor has `content-visibility: hidden`. The options add
 * the same optional checks: `visibility` and `opacity`. It follows the
 * flattened tree, through slots and shadow hosts, as rendering does: a shadow
 * host's child that no slot takes isn't rendered.
 *
 * happy-dom applies no browser default styles, so this adds the `hidden`
 * attribute's to the document: `display: none`, or `content-visibility:
 * hidden` for `hidden="until-found"`. A page's own CSS can still override
 * them. Document stylesheets don't reach shadow trees, so there the defaults
 * are applied directly, and only an inline `display` overrides them.
 *
 * happy-dom doesn't do layout, so anything that depends on size or position
 * (an empty box, content scrolled out of view, `contentVisibilityAuto`) still
 * needs a browser test.
 */
export function checkVisibility(
  element: Element,
  options: CheckVisibilityOptions = {}
): boolean {
  if (!element.isConnected) {
    return false;
  }
  addHiddenDefaults(element.ownerDocument);

  const checksVisibilityCss =
    options.visibilityProperty ?? options.checkVisibilityCSS ?? false;
  const checksOpacity =
    options.opacityProperty ?? options.checkOpacity ?? false;

  const self = getComputedStyle(element);
  if (self.display === 'contents') {
    return false;
  }
  if (checksVisibilityCss && ['hidden', 'collapse'].includes(self.visibility)) {
    return false;
  }

  for (
    let node: Element | null = element, child: Element | null = null;
    node;
    child = node, node = flatParent(node)
  ) {
    if (node === UNASSIGNED) {
      return false;
    }
    const style = node === element ? self : getComputedStyle(node);

    if (isDisplayNone(node, style)) {
      return false;
    }
    if (node !== element && hidesContent(node, style)) {
      return false;
    }
    if (checksOpacity && style.opacity === '0') {
      return false;
    }
    if (
      node instanceof HTMLDetailsElement &&
      !node.open &&
      child &&
      child !== node.querySelector(':scope > summary')
    ) {
      return false;
    }
  }

  return true;
}

const HIDDEN_DEFAULTS_ID = 'check-visibility-hidden-defaults';

/** The browser's default styles for `hidden`, which happy-dom doesn't apply. */
function addHiddenDefaults(doc: Document): void {
  if (doc.getElementById(HIDDEN_DEFAULTS_ID)) {
    return;
  }
  const style = doc.createElement('style');
  style.id = HIDDEN_DEFAULTS_ID;
  style.textContent =
    '[hidden]:not([hidden="until-found" i]) { display: none; } [hidden="until-found" i] { content-visibility: hidden; }';
  doc.head.prepend(style);
}

/** The `hidden` attribute's value in a shadow tree, where the defaults don't reach. */
function shadowHidden(node: Element): string | null {
  return node.getRootNode() instanceof ShadowRoot
    ? (node.getAttribute('hidden')?.toLowerCase() ?? null)
    : null;
}

function isDisplayNone(node: Element, style: CSSStyleDeclaration): boolean {
  if (style.display === 'none') {
    return true;
  }
  const hidden = shadowHidden(node);
  return (
    hidden !== null &&
    hidden !== 'until-found' &&
    !(node as HTMLElement).style?.display
  );
}

function hidesContent(node: Element, style: CSSStyleDeclaration): boolean {
  return (
    style.contentVisibility === 'hidden' || shadowHidden(node) === 'until-found'
  );
}

/** Marks a shadow host's child that no slot takes, so it isn't rendered. */
const UNASSIGNED = document.createElement('unassigned');

/**
 * The element `element` renders inside: its slot, its parent, or its shadow
 * host. happy-dom doesn't implement `assignedSlot`, so a child of an open
 * shadow host is matched to the slot whose `name` equals its `slot` attribute
 * (or the default slot), as the browser assigns it.
 */
function flatParent(element: Element): Element | null {
  const parent = element.parentNode;

  if (parent instanceof Element && parent.shadowRoot) {
    const name = element.getAttribute('slot') ?? '';
    const slot =
      element.assignedSlot ??
      Array.from(parent.shadowRoot.querySelectorAll('slot')).find(
        (candidate) => (candidate.getAttribute('name') ?? '') === name
      );
    return slot ?? UNASSIGNED;
  }

  if (parent instanceof ShadowRoot) {
    return parent.host;
  }
  return parent instanceof Element ? parent : null;
}

/**
 * Installs {@link checkVisibility} as `Element.prototype.checkVisibility` for
 * the test file (or `describe` block) that calls it, when the environment
 * doesn't provide one.
 */
export function standInCheckVisibility(): void {
  const proto: object = Element.prototype;
  let added = false;

  beforeAll(() => {
    if ('checkVisibility' in proto) {
      return;
    }
    Object.defineProperty(proto, 'checkVisibility', {
      configurable: true,
      value(this: Element, options?: CheckVisibilityOptions) {
        return checkVisibility(this, options);
      },
    });
    added = true;
  });

  afterAll(() => {
    if (added) {
      delete (proto as {checkVisibility?: unknown}).checkVisibility;
      added = false;
    }
  });
}
