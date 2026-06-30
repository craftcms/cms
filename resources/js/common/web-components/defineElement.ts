/**
 * Register a custom element, guarding against a duplicate `define()` (which
 * throws). Mirrors the `customElements.get(tag)` check each wrapper's `index.ts`
 * was repeating.
 */
export function defineElement(
  tag: string,
  ctor: CustomElementConstructor
): void {
  if (!customElements.get(tag)) {
    customElements.define(tag, ctor);
  }
}
