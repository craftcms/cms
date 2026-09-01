/**
 * Focus containment for overlays that can't use the platform's own.
 *
 * A `<dialog>` opened with `showModal()` gets focus containment from the
 * browser. One opened with `show()` does not — it stays in the normal stacking
 * context, which is the point (top-layer dialogs paint above `<body>`-appended
 * menus and make them unclickable), but the price is that Tab walks straight
 * out of it. These helpers pay that price back.
 */

const FOCUSABLE_SELECTOR = [
  'a[href]',
  'area[href]',
  'button',
  'input',
  'select',
  'textarea',
  'details > summary',
  'iframe',
  'object',
  'embed',
  'audio[controls]',
  'video[controls]',
  '[contenteditable]',
  '[tabindex]',
].join(',');

function isTabbable(el: HTMLElement): boolean {
  if (el.hasAttribute('disabled') || el.hasAttribute('inert')) {
    return false;
  }

  if (el.getAttribute('aria-hidden') === 'true' || el.hidden) {
    return false;
  }

  return el.tabIndex >= 0;
}

/**
 * Tab-reachable elements inside a host, shadow tree first.
 *
 * A host's shadow tree and its slotted light-DOM children are separate trees,
 * so this walks both. Concatenating them approximates document order — resolving
 * it exactly would mean matching every `<slot>`'s assigned nodes against that
 * slot's position in the shadow tree. The approximation holds for the shape
 * these overlays use (chrome rendered in shadow, content slotted into the
 * middle), and only the first and last entries are ever used.
 *
 * Deliberately does not filter on visibility: `isVisible()` depends on layout,
 * which happy-dom does not compute, and a trap that finds nothing under test is
 * worse than one that occasionally includes a hidden control.
 */
export function focusableWithin(host: HTMLElement): HTMLElement[] {
  const roots: ParentNode[] = host.shadowRoot
    ? [host.shadowRoot, host]
    : [host];

  return roots
    .flatMap((root) =>
      Array.from(root.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR))
    )
    .filter(isTabbable);
}

/**
 * Wrap Tab navigation around the focusable elements inside `host`.
 *
 * Returns a disposer; call it when the overlay closes. Installing a second trap
 * on the same host without releasing the first leaves both listeners attached,
 * so callers should hold the disposer rather than re-trapping.
 */
export function trapFocus(host: HTMLElement): () => void {
  const onKeydown = (event: KeyboardEvent): void => {
    if (event.key !== 'Tab') {
      return;
    }

    const focusable = focusableWithin(host);

    if (focusable.length === 0) {
      return;
    }

    const first = focusable[0]!;
    const last = focusable[focusable.length - 1]!;
    // `composedPath()` rather than `target`, so a focused node inside a nested
    // shadow root still matches the element we collected.
    const active = event.composedPath()[0] as HTMLElement | undefined;

    if (event.shiftKey && active === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && active === last) {
      event.preventDefault();
      first.focus();
    }
  };

  host.addEventListener('keydown', onKeydown);

  return () => host.removeEventListener('keydown', onKeydown);
}
