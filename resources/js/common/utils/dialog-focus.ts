import {isMobileBrowser, setFocusWithin} from '@craftcms/garnish';
import {
  composedContains,
  containsFocus,
} from '@craftcms/ui/utilities/focus-out';

/**
 * Where focus goes when a dialog or slideout opens.
 *
 * - The heading by default, so screen readers announce what opened before
 *   anything inside it.
 * - A field, when the user hasn't turned on "Disable autofocus"
 *   (`Craft.autofocusPreferred`): the one marked `autofocus`, or else the
 *   first field you can type in or choose from, if it's in the dialog's first
 *   field. See {@link autofocusField}. Never a link or button that happens to
 *   come first. It's skipped on mobile and tablet browsers, where focusing a
 *   field pops up the on-screen keyboard, the same as `TextControl`'s own
 *   autofocus.
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
    const field = preferField ? autofocusField(container) : null;
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
        autofocusField(container)?.focus();
      }
    });
  }
}

/** Focuses a heading, which isn't focusable until it's given a `tabindex`. */
function focusHeading(heading: HTMLElement): void {
  if (!heading.hasAttribute('tabindex')) {
    heading.tabIndex = -1;
  }
  heading.focus({preventScroll: true});
}

/**
 * The field to focus in `root`: a text-like `input`, `select` or `textarea`
 * that's visible, enabled (including by any `fieldset` around it) and not
 * read-only.
 *
 * - The one marked `autofocus`, wherever it is: the form chose it.
 * - Otherwise the first one, but only if it's inside `root`'s first field
 *   (`craft-field`, or legacy markup's `.field`). A form that opens with
 *   instructions or a read-only field, such as the authenticator app setup,
 *   keeps focus on its heading rather than jumping past them to a field
 *   further down. This is how Craft 5's `Garnish.setFocusWithin()` chose
 *   (craftcms/cms#15245). Without any field containers, the first one counts.
 *
 * It looks inside open shadow roots, so it finds the native control within a
 * component that renders its own. Skips anything in `.prevent-autofocus`.
 */
export function autofocusField(root: Element): HTMLElement | null {
  const elements = [...composedDescendants(root)];
  const marked = elements.find(
    (el) => el.hasAttribute('autofocus') && isEditableField(el)
  );
  if (marked) {
    return marked as HTMLElement;
  }

  const first = elements.find(isEditableField);
  if (!first) {
    return null;
  }

  const firstContainer = elements.find(
    (el) =>
      el.matches(FIELD_CONTAINER_SELECTOR) &&
      el.checkVisibility({visibilityProperty: true})
  );
  if (firstContainer && !composedContains(firstContainer, first)) {
    return null;
  }
  return first;
}

/** A field's outer element: the component, or legacy markup's wrapper. */
const FIELD_CONTAINER_SELECTOR = 'craft-field, .field';

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
    !field.matches(':disabled') &&
    !('readOnly' in field && field.readOnly) &&
    !field.closest('.prevent-autofocus, [inert]') &&
    field.checkVisibility({visibilityProperty: true})
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

/** Whether focus is on something inside `container`, not on the container itself. */
function focusIsWithin(container: HTMLElement): boolean {
  const root = container.getRootNode() as Document | ShadowRoot;
  return containsFocus(container) && root.activeElement !== container;
}

function autofocusPreferred(): boolean {
  return window.Craft?.autofocusPreferred === true;
}
