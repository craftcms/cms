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
 * @property {boolean} self-managed - Whether the tooltip should manage its own state.
 * @property {string} aria-label - Text content for the tooltip
 * @property {number} delay - The delay before the tooltip is shown on mouseentery.
 * @method show - Show the tooltip.
 * @method hide - Hide the tooltip.
 * @method update - Update the position of the tooltip.
 *
 * @example <craft-tooltip aria-label="Tooltip content"><button type="button">Trigger</button></craft-tooltip>
 */
class CraftTooltip extends HTMLElement {
  static get observedAttributes() {
    return ['aria-label'];
  }

  connectedCallback() {
    this.arrowElement = this.querySelector('.arrow');
    this.trigger = this.querySelector('a, button, [role="button"]');
    this.selfManaged = this.hasAttribute('self-managed');

    this.arrow = this.getAttribute('arrow') !== 'false';
    this.offset = this.hasAttribute('offset')
      ? parseInt(this.getAttribute('offset'), 10)
      : 8;

    this.placement = this.getAttribute('placement') || 'bottom';
    this.direction = getComputedStyle(this).direction;
    this.delay = this.getAttribute('delay') || 500;
    this.delayTimeout = null;

    this.renderTooltip();
    this.renderInner();

    if (this.arrow && !this.arrowElement) {
      this.renderArrow();
    }

    this.listeners = [
      ['mouseenter', this.show, this.delay],
      ['focus', this.show, 0],
      ['mouseleave', this.hide, 0],
      ['blur', this.hide, 0],
    ];

    if (this.selfManaged) {
      this.trigger = this.parentElement;
    }

    if (!this.trigger) {
      console.warn('No trigger found for tooltip');
      return false;
    }

    // Make sure the trigger accepts pointer events
    this.trigger.style.pointerEvents = 'auto';

    this.listeners.forEach(([event, handler, delay]) => {
      this.trigger?.addEventListener(event, handler.bind(this, delay));
    });

    // Close on ESC
    document.addEventListener('keyup', this.handleKeyUp.bind(this));

    // Update & hide to make sure everything is where it needs to be
    this.update();
    this.hide();
  }

  disconnectedCallback() {
    this.hide();

    if (this.listeners.length) {
      this.listeners.forEach(([event, handler]) => {
        this.trigger?.removeEventListener(event, handler.bind(this));
      });
    }

    document.removeEventListener('keyup', this.handleKeyUp.bind(this));
  }

  attributeChangedCallback(name, oldValue, newValue) {
    if (name === 'aria-label' && this.inner) {
      this.inner.innerText = newValue;

      // innerText will remove the arrow, so we have to put it back if we need it.
      if (this.arrow) {
        this.renderArrow();
        this.update();
      }
    }
  }

  handleKeyUp(e) {
    if (e.key === 'Escape') {
      this.hide();
    }
  }

  renderTooltip() {
    this.tooltip = document.createElement('span');
    this.tooltip.classList.add('craft-tooltip');
    this.appendChild(this.tooltip);
  }

  /**
   * Renders an inner container so we can use padding for the offset and
   * maintain a better hover experience for users using zoom.
   */
  renderInner() {
    this.inner = document.createElement('span');
    this.inner.classList.add('inner');
    this.inner.innerText = this.getAttribute('aria-label');

    // Replace the content with the inner container
    this.tooltip.appendChild(this.inner);
  }

  renderArrow() {
    this.arrowElement = document.createElement('span');
    this.arrowElement.classList.add('arrow');
    this.inner.appendChild(this.arrowElement);
  }

  show(delay) {
    this.update();

    this.delayTimeout = setTimeout(() => {
      Object.assign(this.tooltip.style, {
        opacity: 1,
        transform: ['left', 'right'].includes(this.getStaticSide())
          ? `translateX(0)`
          : `translateY(0)`,
        // Make sure if a user hovers over the label itself, it stays open
        pointerEvents: 'auto',
      });
    }, delay);
  }

  hide() {
    if (this.delayTimeout) {
      clearTimeout(this.delayTimeout);
    }

    Object.assign(this.tooltip.style, {
      opacity: 0,
      transform: this.getInitialTransform(),
      pointerEvents: 'none',
    });
  }

  getInitialTransform() {
    // Make sure the bubble moves in a natural direction
    return {
      top: `translateY(-${this.offset}px)`,
      right: `translateX(${this.offset}px)`,
      bottom: `translateY(${this.offset}px)`,
      left: `translateX(-${this.offset}px)`,
    }[this.getStaticSide()];
  }

  getStaticSide() {
    return {
      top: 'bottom',
      right: 'left',
      bottom: 'top',
      left: 'right',
    }[this.placement.split('-')[0]];
  }

  update() {
    computePosition(this.trigger, this.tooltip, {
      strategy: 'fixed',
      placement: this.placement,
      middleware: [
        flip(),
        shift({padding: 10}),
        offset(0),
        ...(this.arrow ? [arrow({element: this.arrowElement})] : []),
      ],
    }).then(({x, y, middlewareData, placement}) => {
      // Placement may have changed
      this.placement = placement;

      Object.assign(this.tooltip.style, {
        left: `${x}px`,
        top: `${y}px`,
        // Add padding to the static side for accessible hovers
        [`padding${Craft.uppercaseFirst(this.getStaticSide())}`]:
          `${this.offset}px`,
      });

      if (!this.arrowElement) {
        return;
      }

      const {x: arrowX, y: arrowY} = middlewareData.arrow;
      this.arrowElement.dataset.placement = placement;
      Object.assign(this.arrowElement.style, {
        left: arrowX != null ? `${arrowX}px` : '',
        top: arrowY != null ? `${arrowY}px` : '',
        right: '',
        bottom: '',
        [this.getStaticSide()]: '-4px',
      });
    });
  }
}

customElements.define('craft-tooltip', CraftTooltip);
