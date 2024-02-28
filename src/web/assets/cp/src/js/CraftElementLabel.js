import $ from 'jquery';

class CraftElementLabel extends HTMLElement {
  connectedCallback() {
    this.labelLink = this.querySelector('.label-link');
    this.tooltip = null;

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
  }

  update() {
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
      this.tooltip = document.createElement('craft-tooltip');
      this.tooltip.innerText = this.innerText;
      this.labelLink.appendChild(this.tooltip);
    }
  }

  disconnectedCallback() {
    this.tooltip?.remove();
    this.$tabs.data('tabs')?.off('selectTab');
  }

  calculateWidth(text) {
    const tag = document.createElement('span');
    Object.assign(tag.style, {
      position: 'absolute',
      visibility: 'hidden',
      whiteSpace: 'nowrap',
      fontFamily: 'inherit',
    });
    tag.innerHTML = text;

    this.appendChild(tag);
    const result = tag.clientWidth;
    this.removeChild(tag);
    return result;
  }
}

customElements.define('craft-element-label', CraftElementLabel);
