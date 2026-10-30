/**
 * Renders legacy `data-icon` glyphs as SVG icons.
 *
 * Craft 5 drew `[data-icon]` elements with an icon font through a `::before`
 * rule in the legacy CP stylesheet, which Craft 6 no longer loads, so plugin
 * markup like `<a data-icon="settings">` lost its icon. This prepends a
 * decorative `<craft-icon>` with the same name instead; `craft-icon` resolves
 * the legacy glyph names (`settings`, `edit`, `world`…) to their SVGs.
 *
 * Custom elements are skipped: there `data-icon` is data, not a glyph (such as
 * the field type icon on `craft-field`).
 */
const ICON_SELECTOR = ':scope > craft-icon[data-legacy-icon]';
const VOID_ELEMENTS = new Set([
  'INPUT',
  'IMG',
  'BR',
  'HR',
  'TEXTAREA',
  'SELECT',
]);

let warned = false;

function upgrade(element: Element): void {
  const name = element.getAttribute('data-icon');

  if (
    !name ||
    element.tagName.includes('-') ||
    VOID_ELEMENTS.has(element.tagName)
  ) {
    return;
  }

  let icon = element.querySelector(ICON_SELECTOR);

  if (!icon) {
    if (!warned) {
      warned = true;
      console.warn(
        'The `data-icon` attribute is deprecated. Render a `<craft-icon name="…">` (or `iconSvg()` in Twig) instead.',
        element
      );
    }

    icon = document.createElement('craft-icon');
    icon.setAttribute('data-legacy-icon', '');
    element.prepend(icon);
  }

  if (icon.getAttribute('name') !== name) {
    icon.setAttribute('name', name);
  }
}

export function upgradeDataIcons(root: ParentNode): void {
  if (root instanceof Element && root.hasAttribute('data-icon')) {
    upgrade(root);
  }

  root.querySelectorAll('[data-icon]').forEach(upgrade);
}

export function observeDataIcons(
  root: Element = document.body
): MutationObserver {
  upgradeDataIcons(root);

  const observer = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
      if (mutation.type === 'attributes') {
        upgrade(mutation.target as Element);
        continue;
      }

      mutation.addedNodes.forEach((node) => {
        if (node instanceof Element) {
          upgradeDataIcons(node);
        }
      });
    }
  });

  observer.observe(root, {
    childList: true,
    subtree: true,
    attributes: true,
    attributeFilter: ['data-icon'],
  });

  return observer;
}

if (document.body) {
  observeDataIcons();
} else {
  document.addEventListener('DOMContentLoaded', () => observeDataIcons(), {
    once: true,
  });
}
