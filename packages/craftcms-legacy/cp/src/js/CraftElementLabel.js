/** global: $ */
/** global: jQuery */

// Fallback for generating a unique invoker id when the element itself has none.
let elementLabelTooltipId = 0;

/**
 * Element label
 *
 * Displays a tooltip when the label link overflows its container.
 *
 * @method update Recalculate overflow and create tooltip if necessary.
 * @method calculateWidth Calculate the desired width of the label.
 *
 * @example <craft-element-label><a href="#" class="label-link">Label</a></craft-element-label>
 */
class CraftElementLabel extends HTMLElement {
  constructor() {
    super();
    this.tooltip = null;
    this.$tabs = null;
    this.disabled = false;
  }

  get labelLink() {
    return this.querySelector('.label-link');
  }

  connectedCallback() {
    if (this.hasAttribute('disabled')) {
      return;
    }

    if (!this.labelLink) {
      console.warn('No label link found in craft-element-label.');
      return;
    }

    /**
     * When the element is inside a tab, we need to listen for tab changes.
     * Tabs are initially rendered as `display: none` which will cause the
     * label to have a width of 0
     */
    this.$tabs = $('#tabs');
    if (this.$tabs.length && this.$tabs.data('tabs')) {
      this.$tabs.data('tabs').on('selectTab', () => {
        this.update();
      });
    }

    this.update();

    // Update again when the document is ready.
    // At the moment, this is necessary for this functionality within a dashboard
    // widget. In that case, this component is rendered too early.
    $(() => {
      this.update();
    });
  }

  update() {
    // Measure the label link's text, not `this.innerText`, so the tooltip's
    // own (light-DOM) text content can't skew the overflow calculation.
    this.desiredWidth = this.calculateWidth(this.labelLink.innerText);
    this.hasOverflow = this.desiredWidth > this.scrollWidth;

    // If the label has an overflow, add a tooltip
    if (!this.hasOverflow) {
      return;
    }

    // Do we already have a tooltip?
    /** @type {CraftTooltip|null} */
    this.tooltip = this.querySelector('craft-tooltip');

    // If not, create one
    if (!this.tooltip) {
      this.createTooltip();
    }
  }

  createTooltip() {
    const labelLink = this.labelLink;

    // The new craft-tooltip references its invoker (the label link) by id
    // rather than wrapping it, so make sure the link has one. The CSS on
    // `.label-link` already handles the visual ellipsis truncation.
    if (!labelLink.id) {
      labelLink.id = this.id
        ? `${this.id}-link`
        : `element-label-link-${elementLabelTooltipId++}`;
    }

    // The tooltip shows the full, untruncated label text; the tooltip content
    // lives in its default slot. Present a context label (e.g. a draft name)
    // in parentheses, matching the inline label.
    let text = labelLink.innerText;
    const contextLabel = this.querySelector('.context-label');
    if (contextLabel) {
      text = text.replace(
        contextLabel.innerText,
        ` (${contextLabel.innerText})`
      );
    }

    this.tooltip = document.createElement('craft-tooltip');
    this.tooltip.setAttribute('for', labelLink.id);
    this.tooltip.textContent = text;

    this.appendChild(this.tooltip);
  }

  disconnectedCallback() {
    this.tooltip?.remove();
    if (this.$tabs?.length) {
      this.$tabs.data('tabs')?.off('selectTab');
    }
  }

  calculateWidth(text) {
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

customElements.define('craft-element-label', CraftElementLabel);
