let linkIdCounter = 0;

/**
 * Element label
 *
 * Displays a tooltip (`<craft-tooltip>`) with the label link's full text when
 * the link overflows its container. The tooltip references the link by id, so
 * the link stays where it is.
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
export class ElementLabel extends HTMLElement {
  private tooltip: HTMLElement | null = null;
  private resizeObserver: ResizeObserver | null = null;

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
    this.resizeObserver.observe(this.labelLink);
  }

  update(): void {
    const labelLink = this.labelLink;
    if (!labelLink) {
      return;
    }

    if (!this.isClipped(labelLink)) {
      this.removeTooltip();
      return;
    }

    if (!this.tooltip) {
      this.createTooltip(labelLink);
    }
  }

  createTooltip(labelLink: HTMLElement): void {
    labelLink.id ||= `craft-element-label-link-${++linkIdCounter}`;

    const tooltip = document.createElement('craft-tooltip');
    tooltip.setAttribute('for', labelLink.id);
    tooltip.setAttribute('invoker-relation', 'label');
    tooltip.textContent = (labelLink.textContent ?? '')
      .replace(/\s+/g, ' ')
      .trim();
    labelLink.after(tooltip);

    this.tooltip = tooltip;
  }

  removeTooltip(): void {
    this.tooltip?.remove();
    this.tooltip = null;
  }

  disconnectedCallback(): void {
    this.resizeObserver?.disconnect();
    this.resizeObserver = null;
    this.removeTooltip();
  }

  /**
   * Whether the link, or an element inside it, clips its text. Inline
   * elements can't clip, and report a zero `scrollWidth` and `clientWidth`.
   */
  isClipped(labelLink: HTMLElement): boolean {
    return [labelLink, ...labelLink.querySelectorAll<HTMLElement>('*')].some(
      (element) => element.scrollWidth > element.clientWidth
    );
  }
}
