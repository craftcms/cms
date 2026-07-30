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
