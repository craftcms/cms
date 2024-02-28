import {arrow, computePosition, flip, offset, shift} from '@floating-ui/dom';

/**
 * Renders a tooltip on hover or focus of the parent element.
 */
export default class CraftTooltip extends HTMLElement {
  connectedCallback() {
    this.renderArrow();

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

  renderArrow() {
    this.arrowElement = document.createElement('span');
    this.arrowElement.classList.add('arrow');
    this.appendChild(this.arrowElement);
  }

  show() {
    this.update();
    Object.assign(this.style, {
      opacity: 1,
      transform: `translateY(0)`,
    });
  }

  hide() {
    Object.assign(this.style, {
      opacity: 0,
      transform: `translateY(5px)`,
    });
  }

  update() {
    computePosition(this.parentElement, this, {
      strategy: 'fixed',
      placement: this.getAttribute('placement') || 'bottom',
      middleware: [
        offset(4),
        flip(),
        shift({padding: 10}),
        arrow({element: this.arrowElement}),
      ],
    }).then(({x, y, middlewareData, placement}) => {
      Object.assign(this.style, {
        opacity: 1,
        transform: `translateY(0)`,
        left: `${x}px`,
        top: `${y}px`,
      });

      const {x: arrowX, y: arrowY} = middlewareData.arrow;
      const staticSide = {
        top: 'bottom',
        right: 'left',
        bottom: 'top',
        left: 'right',
      }[placement.split('-')[0]];

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

customElements.define('craft-tooltip', CraftTooltip);
