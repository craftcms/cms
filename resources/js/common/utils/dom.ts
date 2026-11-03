import {focusableWithin} from '@craftcms/ui/utilities/focus-trap';
import {nextTick} from 'vue';

/**
 * A DOM-ish argument: a CSS selector, an element, or an array-like collection
 * of elements (including a jQuery object). The shape the imperative
 * `@craftcms/garnish` `Base` ports accept where the legacy code took `$(...)`.
 */
export type ElementArg =
  | string
  | Element
  | ArrayLike<Element>
  | null
  | undefined;

/**
 * Resolves a selector / element / array-like (incl. a jQuery object) to a single
 * `HTMLElement`, or `null`. The jQuery-free replacement for `$(input).get(0)`
 * that the ported CP classes lean on for their container/toggle arguments.
 */
export function resolveElement(input: ElementArg): HTMLElement | null {
  if (input == null) {
    return null;
  }
  if (input instanceof HTMLElement) {
    return input;
  }
  if (input instanceof Element) {
    return null;
  }
  if (!(input instanceof Object)) {
    return document.querySelector<HTMLElement>(input);
  }
  return input[0] instanceof HTMLElement ? input[0] : null;
}

/**
 * `Array.from(document.querySelectorAll(selector))`, or `[]` for a nullish
 * selector — the document-scoped companion to {@link resolveElement}.
 */
export function queryAll(selector: string | null | undefined): HTMLElement[] {
  return selector
    ? Array.from(document.querySelectorAll<HTMLElement>(selector))
    : [];
}

/**
 * Whether an element is interactive — something the user acts on directly (a
 * link, button, form control, menu trigger, custom-element control,
 * contenteditable region, …) rather than passive content.
 *
 * We use focusability as the proxy: every genuinely interactive control is
 * focusable and reports a non-negative `tabIndex`, while passive containers,
 * text, icons, etc. do not. This avoids maintaining a selector list of every
 * interactive tag/web-component.
 */
export function isInteractiveElement(node: EventTarget | null): boolean {
  return node instanceof HTMLElement && node.tabIndex >= 0;
}

/**
 * Whether a click landed on (or inside) an interactive control rather than the
 * passive body of `boundary` — the element the click listener is bound to
 * (`event.currentTarget`) by default.
 *
 * Use this for "click the body of a row/card/panel to do something, but let
 * links, buttons, inputs, menus, and other controls inside it behave normally."
 *
 * It walks the event's composed path — which pierces open shadow DOM, so the
 * real focused control inside a web component is seen rather than the retargeted
 * host — stopping at `boundary` (whose own focusability is never counted).
 */
export function isInteractiveClick(
  event: Event,
  boundary: EventTarget | null = event.currentTarget
): boolean {
  for (const node of event.composedPath()) {
    if (node === boundary) break;
    if (isInteractiveElement(node)) return true;
  }
  return false;
}

/** Finds the first tab-reachable descendant, including nested open shadow roots. */
export function firstFocusableWithin(
  root: HTMLElement
): HTMLElement | undefined {
  const focusable = new Set(focusableWithin(root));
  const children = [...(root.shadowRoot?.children ?? []), ...root.children];

  for (const child of children) {
    if (
      !(child instanceof HTMLElement) ||
      child.hidden ||
      (child instanceof HTMLInputElement && child.type === 'hidden') ||
      child.hasAttribute('inert') ||
      child.hasAttribute('disabled') ||
      child.getAttribute('aria-hidden') === 'true'
    )
      continue;

    if (focusable.has(child)) return child;

    const nested = firstFocusableWithin(child);
    if (nested) return nested;
  }
}

/**
 * Shows an element that may be in a tab panel that isn't selected, or in a
 * hidden region a button discloses, such as a screen's details panel, then
 * scrolls it into view.
 *
 * Tabs and disclosure buttons are found by the `aria-controls` that names
 * what they show, so this covers `craft-tabs` in external-panel mode, the
 * legacy `Craft.Tabs`, and `craft-disclosure` in external mode. A
 * `craft-tabs` strip is set to the tab, which leaves focus where it is; a
 * legacy tab or a disclosure button is clicked.
 */
export async function revealElement(
  element: Element,
  {block = 'nearest'}: {block?: ScrollLogicalPosition} = {}
): Promise<void> {
  const root = element.getRootNode() as Document | ShadowRoot;

  for (
    let ancestor = element.parentElement;
    ancestor;
    ancestor = ancestor.parentElement
  ) {
    const disclosure =
      ancestor.id && ancestor.hidden
        ? root.querySelector<HTMLElement>(
            `button[aria-controls="${CSS.escape(ancestor.id)}"]:not([role="tab"])`
          )
        : null;

    if (disclosure) {
      disclosure.click();
      // Its owner shows the region once the disclosure has updated.
      await (
        disclosure.closest('craft-disclosure') as {
          updateComplete?: Promise<unknown>;
        } | null
      )?.updateComplete;
      await nextTick();

      continue;
    }

    const tab = ancestor.id
      ? root.querySelector<HTMLElement>(
          `[role="tab"][aria-controls="${CSS.escape(ancestor.id)}"]`
        )
      : null;

    if (!tab || tab.getAttribute('aria-selected') === 'true') {
      continue;
    }

    const tabs = tab.closest('craft-tabs') as
      | (HTMLElement & {
          selectedIndex: number;
          updateComplete: Promise<unknown>;
        })
      | null;

    if (tabs) {
      tabs.selectedIndex = [
        ...tabs.querySelectorAll(':scope > craft-tab'),
      ].indexOf(tab);
      await tabs.updateComplete;
    } else {
      tab.click();
    }
  }

  element.scrollIntoView({
    block,
    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches
      ? 'auto'
      : 'smooth',
  });
}
