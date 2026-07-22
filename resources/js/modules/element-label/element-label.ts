/**
 * Element label
 *
 * Displays a tooltip (`<craft-tooltip>`) when the label link overflows its
 * container.
 *
 * Ported out of the legacy CP bundle (`CraftElementLabel.js`) and made fully
 * jQuery-free. The legacy version hooked the `Craft.Tabs` `selectTab` event
 * (read via jQuery's `.data('tabs')` cache) to re-measure once a label inside a
 * `display: none` tab became visible, and re-ran on `$(ready)` for labels
 * rendered too early (e.g. in a dashboard widget). Both are replaced by a
 * `ResizeObserver` on the element: it fires when the label (or an ancestor,
 * such as a tab pane) transitions out of `display: none` and on any later size
 * change, with no coupling to `Craft.Tabs`.
 *
 * @example <craft-element-label><a href="#" class="label-link">Label</a></craft-element-label>
 */

/** Fallback for generating a unique invoker id when the element has none. */
let elementLabelTooltipId = 0;

export class ElementLabel extends HTMLElement {
  private tooltip: HTMLElement | null = null;
  private resizeObserver: ResizeObserver | null = null;
  private desiredWidth = 0;
  private hasOverflow = false;

  get labelLink(): HTMLElement | null {
    return this.querySelector('.label-link');
  }

  connectedCallback(): void {
    if (this.hasAttribute('disabled')) {
      return;
    }

    if (!this.labelLink) {
      console.warn('No label link found in craft-element-label.');
      return;
    }

    this.update();

    // Re-measure whenever the label gains or changes size. This covers a label
    // that starts inside a `display: none` tab pane (width 0 until shown) and
    // one rendered before layout has settled — the cases the legacy class used
    // the `Craft.Tabs` `selectTab` hook and `$(ready)` for.
    this.resizeObserver = new ResizeObserver(() => this.update());
    this.resizeObserver.observe(this);
  }

  update(): void {
    const labelLink = this.labelLink;

    if (!labelLink) {
      return;
    }

    // Measure the label link's text, not `this.innerText`, so the tooltip's
    // own (light-DOM) text content can't skew the overflow calculation.
    this.desiredWidth = this.calculateWidth(labelLink.innerText);
    this.hasOverflow = this.desiredWidth > this.scrollWidth;

    // If the label has an overflow, add a tooltip
    if (!this.hasOverflow) {
      return;
    }

    // Do we already have a tooltip?
    this.tooltip = this.querySelector('craft-tooltip');

    // If not, create one
    if (!this.tooltip) {
      this.createTooltip();
    }
  }

  createTooltip(): void {
    const labelLink = this.labelLink;

    if (!labelLink) {
      return;
    }

    // `craft-tooltip` references its invoker by id (`for`) rather than
    // wrapping it, so the link needs one. The CSS on `.label-link` already
    // handles the visual ellipsis truncation.
    if (!labelLink.id) {
      labelLink.id = this.id
        ? `${this.id}-link`
        : `element-label-link-${elementLabelTooltipId++}`;
    }

    // The tooltip shows the full, untruncated label text, in its default
    // slot. Present a context label (e.g. a draft name) in parentheses,
    // matching the inline label.
    let text = labelLink.innerText;
    const contextLabel = this.querySelector<HTMLElement>('.context-label');
    if (contextLabel) {
      text = text.replace(
        contextLabel.innerText,
        ` (${contextLabel.innerText})`
      );
    }

    const tooltip = document.createElement('craft-tooltip');
    tooltip.setAttribute('for', labelLink.id);
    tooltip.textContent = text;

    this.appendChild(tooltip);

    this.tooltip = tooltip;
  }

  disconnectedCallback(): void {
    this.resizeObserver?.disconnect();
    this.resizeObserver = null;

    // No need to restore the `.label-link` any more: referencing the invoker
    // by `for` leaves it where it is, so nothing has to be moved back before
    // `connectedCallback()` runs again.
    this.tooltip?.remove();
  }

  calculateWidth(text: string): number {
    const tag = document.createElement('span');
    Object.assign(tag.style, {
      position: 'absolute',
      visibility: 'hidden',
      whiteSpace: 'nowrap',
      fontFamily: 'inherit',
    });
    tag.innerText = text;

    this.appendChild(tag);
    const result = tag.clientWidth;
    this.removeChild(tag);
    return result;
  }
}
