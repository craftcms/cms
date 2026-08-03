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
    if (typeof input === 'string') {
        return document.querySelector<HTMLElement>(input);
    }
    if (input instanceof Element) {
        return input as HTMLElement;
    }
    if (typeof (input as ArrayLike<Element>).length === 'number') {
        return ((input as ArrayLike<Element>)[0] as HTMLElement) ?? null;
    }
    return null;
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
