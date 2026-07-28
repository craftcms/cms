// jQuery survives ONLY at the legacy `#tabs` `.data('tabs')` seam: the tab set
// is still the legacy `Craft.Tabs` instance, stashed in jQuery's data cache, so
// there's no jQuery-free way to read it until Tabs is ported. Everything else
// is plain DOM.

/** The jQuery global, if present (the CP always loads it). */
function jq(): any {
  return (window as any).jQuery ?? null;
}

/**
 * Element label
 *
 * Displays a tooltip (`<craft-tooltip>`) when the label link overflows its
 * container.
 *
 * Ported out of the legacy CP bundle (`CraftElementLabel.js`); the `$('#tabs')`
 * tab-change subscription and `$(ready)` deferral were converted to the `jq()`
 * seam + a native document-ready check.
 *
 * @example <craft-element-label><a href="#" class="label-link">Label</a></craft-element-label>
 */
export class ElementLabel extends HTMLElement {
  private tooltip: HTMLElement | null = null;
  /** The legacy `Craft.Tabs` instance this label lives inside, if any. */
  private tabs: any = null;
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

    // When the element is inside a tab, we need to listen for tab changes.
    // Tabs are initially rendered as `display: none`, which would give the
    // label a width of 0.
    const $ = jq();
    const tabsEl = document.getElementById('tabs');
    if ($ && tabsEl) {
      this.tabs = $(tabsEl).data('tabs') ?? null;
      this.tabs?.on('selectTab', () => {
        this.update();
      });
    }

    this.update();

    // Update again once the document is ready. This is currently necessary
    // inside a dashboard widget, where this component is rendered too early.
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.update());
    } else {
      this.update();
    }
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
    // Put the `.label-link` back into `<craft-element-label>` so that when
    // `connectedCallback()` runs again after an insertBefore/insertAfter move,
    // everything can re-initialise as expected. `Element.moveBefore`/`moveAfter`
    // aren't used as they're still experimental (unavailable in Safari & FF).
    const labelLink = this.labelLink;
    if (labelLink) {
      this.append(labelLink);
    }

    this.tooltip?.remove();
    this.tabs?.off('selectTab');
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
