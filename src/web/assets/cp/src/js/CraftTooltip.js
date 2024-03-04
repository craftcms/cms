import {arrow, computePosition, flip, offset, shift} from '@floating-ui/dom';

/**
 * Tooltip
 *
 * Renders a tooltip on hover or focus of the parent element.
 *
 * Tooltips are used to provide additional or context for an element. By default
 * the tooltip will be positioned below an element and will avoid the edges
 * of the browser window.
 *
 * @property {'top'|'top-start'|'top-end'|'right'|'right-start'|'right-end'|'bottom'|'bottom-start'|'bottom-end'|'left'|'left-start'|'left-end'} placement - The placement of the tooltip relative to the parent element.
 * @property {boolean} arrow - Whether the tooltip should have an arrow.
 * @property {number} offset - The offset of the tooltip from the parent element.
 * @method show - Show the tooltip.
 * @method hide - Hide the tooltip.
 * @method update - Update the position of the tooltip.
 * @example <craft-tooltip arrow="false" placement="top" offset="10">Tooltip content</craft-tooltip>
 */
class CraftTooltip extends HTMLElement {
  connectedCallback() {
    this.arrowElement = this.querySelector('.arrow');

    this.arrow = this.getAttribute('arrow') !== 'false';
    this.offset = this.hasAttribute('offset')
      ? parseInt(this.getAttribute('offset'), 10)
      : 8;

    if (this.arrow && !this.arrowElement) {
      this.renderInner();
      this.renderArrow();
    }

    this.listeners = [
      ['mouseenter', this.show],
      ['focus', this.show],
      ['mouseleave', this.hide],
      ['blur', this.hide],
    ];

    this.listeners.forEach(([event, handler]) => {
      this.parentElement?.addEventListener(event, handler.bind(this));
    });

    // Close on ESC
    document.addEventListener('keyup', this.handleKeyUp.bind(this));
  }

  disconnectedCallback() {
    this.hide();

    if (this.listeners.length) {
      this.listeners.forEach(([event, handler]) => {
        this.parentElement?.removeEventListener(event, handler.bind(this));
      });
    }

    document.removeEventListener('keyup', this.handleKeyUp.bind(this));
  }

  handleKeyUp(e) {
    if (e.key === 'Escape') {
      this.hide();
    }
  }

  /**
   * Renders an inner container so we can use padding for the offset and
   * maintain a better hover experience for users using zoom.
   */
  renderInner() {
    this.inner = document.createElement('span');
    this.inner.classList.add('inner');
    this.inner.innerText = this.innerText;

    // Replace the content with the inner container
    this.innerHTML = '';
    this.appendChild(this.inner);
  }

  renderArrow() {
    this.arrowElement = document.createElement('span');
    this.arrowElement.classList.add('arrow');
    this.inner.appendChild(this.arrowElement);
  }

  show() {
    this.update();
    Object.assign(this.style, {
      opacity: 1,
      transform: `translateY(0)`,
      // Make sure if a user hovers over the label itself, it stays open
      pointerEvents: 'auto',
    });
  }

  hide() {
    Object.assign(this.style, {
      opacity: 0,
      transform: `translateY(5px)`,
      pointerEvents: 'none',
    });
  }

  update() {
    computePosition(this.parentElement, this, {
      strategy: 'fixed',
      placement: this.getAttribute('placement') || 'bottom',
      middleware: [
        flip(),
        shift({padding: 10}),
        offset(0),
        ...(this.arrow ? [arrow({element: this.arrowElement})] : []),
      ],
    }).then(({x, y, middlewareData, placement}) => {
      Object.assign(this.style, {
        left: `${x}px`,
        top: `${y}px`,
      });

      if (!this.arrowElement) {
        return;
      }

      const {x: arrowX, y: arrowY} = middlewareData.arrow;
      const staticSide = {
        top: 'bottom',
        right: 'left',
        bottom: 'top',
        left: 'right',
      }[placement.split('-')[0]];

      // Add padding to the static side for accessible hovers
      Object.assign(this.style, {
        [`padding${staticSide[0].toUpperCase()}${staticSide.slice(1)}`]:
          `${this.offset}px`,
      });

      this.arrowElement.dataset.placement = placement;
      Object.assign(this.arrowElement.style, {
        left: arrowX != null ? `${arrowX}px` : '',
        top: arrowY != null ? `${arrowY}px` : '',
        right: '',
        bottom: '',
        [staticSide]: '-4px',
      });
    });
  }
}

export default CraftTooltip;

customElements.define('craft-tooltip', CraftTooltip);
