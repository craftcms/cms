import {isMobileBrowser, setFocusWithin} from '@craftcms/garnish';

/**
 * Where focus goes when a dialog or slideout opens.
 *
 * - The heading by default, so screen readers announce what opened before
 *   anything inside it.
 * - The first field you can type in or choose from, when the user hasn't
 *   turned on "Disable autofocus" (`Craft.autofocusPreferred`). Never a link
 *   or button that happens to come first. It's skipped on mobile and tablet
 *   browsers, where focusing a field pops up the on-screen keyboard, the same
 *   as `TextControl`'s own autofocus.
 *
 * The heading takes focus straight away, and the field a frame later. A
 * screen's fields can still be rendering when it opens, and focus has to
 * leave the container now: it's often parked there to hide the page behind.
 * If something else has taken focus by then, such as a control that focuses
 * itself, it keeps it.
 *
 * With no heading, it falls back to `setFocusWithin()`'s first focusable
 * element, or the container itself.
 *
 * Does nothing if focus is already inside, other than on the container.
 */
export function focusDialogStart(
  container: HTMLElement,
  heading: HTMLElement | null
): void {
  if (focusIsWithin(container)) {
    return;
  }

  const preferField = autofocusPreferred() && !isMobileBrowser(true);

  if (!heading) {
    const field = preferField ? firstEditableField(container) : null;
    if (field) {
      field.focus();
    } else {
      setFocusWithin(container);
    }
    return;
  }

  focusHeading(heading);

  if (preferField) {
    requestAnimationFrame(() => {
      if (document.activeElement === heading) {
        firstEditableField(container)?.focus();
      }
    });
  }
}

/** Focuses a heading, which isn't focusable until it's given a `tabindex`. */
export function focusHeading(heading: HTMLElement): void {
  if (!heading.hasAttribute('tabindex')) {
    heading.tabIndex = -1;
  }
  heading.focus({preventScroll: true});
}

/**
 * The field marked `autofocus`, or else the first field in `root` that you
 * can type in or choose from: a text-like
 * `input`, `select` or `textarea` that's visible, enabled and not read-only.
 * It looks inside open shadow roots, so it finds the native control within a
 * component that renders its own. Skips anything in `.prevent-autofocus`.
 */
export function firstEditableField(root: Element): HTMLElement | null {
  const elements = [...composedDescendants(root)];
  const marked = elements.find(
    (el) => el.hasAttribute('autofocus') && isEditableField(el)
  );
  if (marked) {
    return marked as HTMLElement;
  }

  for (const el of elements) {
    if (isEditableField(el)) {
      return el;
    }
  }
  return null;
}

const FIELD_SELECTOR = [
  'input:not([type="hidden"], [type="button"], [type="submit"], [type="reset"], [type="image"], [type="checkbox"], [type="radio"], [type="file"], [type="range"], [type="color"])',
  'select',
  'textarea',
].join(', ');

function isEditableField(
  el: Element
): el is HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement {
  if (!el.matches(FIELD_SELECTOR)) {
    return false;
  }

  const field = el as
    | HTMLInputElement
    | HTMLSelectElement
    | HTMLTextAreaElement;

  return (
    !field.disabled &&
    !('readOnly' in field && field.readOnly) &&
    !field.closest('.prevent-autofocus, [inert]') &&
    field.checkVisibility()
  );
}

/** Every element under `root` in tree order, including inside open shadow roots. */
function* composedDescendants(root: Element | ShadowRoot): Generator<Element> {
  for (const child of root.children) {
    yield child;
    if (child.shadowRoot) {
      yield* composedDescendants(child.shadowRoot);
    }
    yield* composedDescendants(child);
  }
}

function focusIsWithin(container: HTMLElement): boolean {
  const active = document.activeElement;
  return Boolean(active && active !== container && container.contains(active));
}

function autofocusPreferred(): boolean {
  return window.Craft?.autofocusPreferred === true;
}
