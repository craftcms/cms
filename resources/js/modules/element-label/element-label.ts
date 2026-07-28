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
    this.desiredWidth = this.calculateWidth(this.innerText);
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
    const tooltip = document.createElement('craft-tooltip');
    tooltip.setAttribute('self-managed', 'true');
    tooltip.setAttribute('text', this.innerText);
    tooltip.setAttribute('aria-hidden', 'true');

    // Make sure tooltips created show ellipses
    Object.assign(tooltip.style, {
      overflow: 'hidden',
      textOverflow: 'ellipsis',
      whiteSpace: 'nowrap',
    });

    // If there's a context label, make it a little nicer
    const contextLabel = this.querySelector<HTMLElement>('.context-label');
    if (contextLabel) {
      tooltip.innerText = tooltip.innerText.replace(
        contextLabel.innerText,
        ` (${contextLabel.innerText})`
      );
    }

    const labelLink = this.labelLink;
    if (labelLink) {
      this.insertBefore(tooltip, labelLink);
      tooltip.appendChild(labelLink);
    }

    this.tooltip = tooltip;
  }

  disconnectedCallback(): void {
    this.resizeObserver?.disconnect();
    this.resizeObserver = null;

    // Put the `.label-link` back into `<craft-element-label>` so that when
    // `connectedCallback()` runs again after an insertBefore/insertAfter move,
    // everything can re-initialise as expected. `Element.moveBefore`/`moveAfter`
    // aren't used as they're still experimental (unavailable in Safari & FF).
    const labelLink = this.labelLink;
    if (labelLink) {
      this.append(labelLink);
    }

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
