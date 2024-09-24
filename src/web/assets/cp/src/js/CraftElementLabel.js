/** global: $ */
/** global: jQuery */

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
    this.desiredWidth = this.calculateWidth(this.innerText);
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
    this.tooltip = document.createElement('craft-tooltip');
    this.tooltip.setAttribute('self-managed', 'true');
    this.tooltip.setAttribute('aria-label', this.innerText);
    this.tooltip.setAttribute('aria-hidden', 'true');

    // If there's a context label, make it a little nicer
    const contextLabel = this.querySelector('.context-label');
    if (contextLabel) {
      this.tooltip.innerText = this.tooltip.innerText.replace(
        contextLabel.innerText,
        ` (${contextLabel.innerText})`
      );
    }

    this.labelLink.appendChild(this.tooltip);
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
