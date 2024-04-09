/**
 * Craft Scroll Container
 *
 * Custom element that wraps a scrollable element and generates overflow shadows
 * based on the scroll position.
 *
 * @typedef {'top'|'right'|'bottom'|'left'} Direction
 *
 * @property {'ltr'|'rtl'} direction - The direction of the element.
 * @property {Object<Direction, HTMLElement>} shadows - The shadow elements.
 * @property {HTMLElement} scroller - The scrollable element.
 * @property {ResizeObserver|undefined} resizeObserver - The resize observer.
 *
 * @example <craft-scroll-container><table><!-- Lots of table markup --></table></craft-scroll-container>
 */
class CraftScrollContainer extends HTMLElement {
  static observedAttributes = [
    'overflow-inline',
    'overflow-block',
    'position-inline',
    'position-block',
  ];

  /**
   * Get the inline scrollbar offset
   * @returns {number|number}
   */
  get scrollbarOffsetInline() {
    return this.hasOverflowInline
      ? this.scroller.offsetWidth - this.scroller.clientWidth
      : 0;
  }

  /**
   * Get the block scrollbar offset
   * @returns {number|number}
   */
  get scrollbarOffsetBlock() {
    return this.hasOverflowBlock
      ? this.scroller.offsetHeight - this.scroller.clientHeight
      : 0;
  }

  /**
   * Whether the container has an overflow along the x-axis
   * @returns {boolean}
   */
  get hasOverflowInline() {
    return this.scroller.scrollWidth > this.scroller.clientWidth;
  }

  /**
   * Whether the container has an overflow along the y-axis
   * @returns {boolean}
   */
  get hasOverflowBlock() {
    return this.scroller.scrollHeight > this.scroller.clientHeight;
  }

  /**
   * Abstract current position of the scroller along the x-axis
   * @returns {'start'|'end'|'center'}
   */
  get positionInline() {
    if (this.scroller.scrollLeft === 0) {
      return 'start';
    }

    // DEV: The - 1 is to account for the scroll never exactly equaling
    if (
      this.scroller.scrollWidth - this.scroller.scrollLeft - 1 <=
      this.scroller.clientWidth
    ) {
      return 'end';
    }

    return 'center';
  }

  /**
   * Abstract current position of the scroller along the y-axis
   * @returns {'top'|'bottom'|'center'}
   */
  get positionBlock() {
    if (this.scroller.scrollTop === 0) {
      return 'top';
    }

    if (
      this.scroller.scrollHeight - this.scroller.scrollTop - 1 <=
      this.scroller.clientHeight
    ) {
      return 'bottom';
    }

    return 'center';
  }

  connectedCallback() {
    this.direction = window.getComputedStyle(this).direction;

    this.createScroller();
    this.setStyles();
    this.shadows = this.createShadows();

    window.addEventListener('resize', this.updateOverflowing.bind(this));

    // Add a resize observer to the inner element
    this.resizeObserver = new ResizeObserver(this.updateOverflowing.bind(this));
    this.resizeObserver.observe(this.scroller);

    this.scroller.addEventListener('scroll', this.handleScroll.bind(this));

    this.handleScroll();
  }

  disconnectedCallback() {
    // Remove the resize observer
    if (this.resizeObserver) {
      this.resizeObserver.unobserve(this);
    }

    // Destroy the shadows
    Object.keys(this.shadows).forEach((side) => {
      this.shadows[side].remove();
    });

    // Remove the scroll listener
    this.removeEventListener('scroll', this.handleScroll.bind(this));
  }

  attributeChangedCallback(name, oldValue, newValue) {
    if (name === 'overflow-inline') {
      this.shadows.left.style.display = newValue === 'true' ? 'block' : 'none';
      this.shadows.right.style.display = newValue === 'true' ? 'block' : 'none';
      this.style.setProperty(
        '--scrollbar-offset-inline',
        `${this.scrollbarOffsetInline}px`
      );
    }

    if (name === 'overflow-block') {
      this.shadows.top.style.display = newValue === 'true' ? 'block' : 'none';
      this.shadows.bottom.style.display =
        newValue === 'true' ? 'block' : 'none';
      this.style.setProperty(
        '--scrollbar-offset-block',
        `${this.scrollbarOffsetBlock}px`
      );
    }

    if (name === 'position-inline') {
      this.shadows.left.style.opacity = newValue === 'start' ? '0' : '1';
      this.shadows.right.style.opacity = newValue === 'end' ? '0' : '1';
    }

    if (name === 'position-block') {
      this.shadows.top.style.opacity = newValue === 'top' ? '0' : '1';
      this.shadows.bottom.style.opacity = newValue === 'bottom' ? '0' : '1';
    }
  }

  /**
   * Set the basic styles on the component
   */
  setStyles() {
    this.style.display = 'block';
    this.style.setProperty('--scroll-shadow-size', '1rem');
    this.style.position = 'relative';
  }

  /**
   * Create the inner scrolling element.
   */
  createScroller() {
    this.scroller = document.createElement('craft-scroller');
    this.scroller.style.display = 'block';
    this.scroller.style.overflow = 'auto';
    this.scroller.append(this.firstElementChild);

    this.append(this.scroller);
  }

  /**
   * Update the overflow attributes on the container
   */
  updateOverflowing() {
    this.setAttribute(
      'overflow-inline',
      this.hasOverflowInline ? 'true' : 'false'
    );
    this.setAttribute(
      'overflow-block',
      this.hasOverflowBlock ? 'true' : 'false'
    );
  }

  /**
   * Update the position attributes on the container
   */
  updateScrollPosition() {
    this.setAttribute('position-inline', this.positionInline);
    this.setAttribute('position-block', this.positionBlock);
  }

  /**
   * Scroll event listener
   */
  handleScroll() {
    this.updateScrollPosition();
  }

  /**
   * Return the direction of the gradient with respect to the language direction
   *
   * @param {'right'|'left'|'bottom'|'top'} side
   * @returns {string}
   */
  getGradientFor(side) {
    const direction = {
      right: this.direction === 'ltr' ? 'left' : 'right',
      left: this.direction === 'ltr' ? 'right' : 'left',
      top: 'bottom',
      bottom: 'top',
    }[side];

    return `linear-gradient(to ${direction}, hsla(var(--gray-900-hsl), 0.2), transparent)`;
  }

  /**
   * Get the styles for the edge shadow with respect to the language direction
   * @param side
   * @returns {Object}
   */
  getEdgeStyles(side) {
    return {
      top: {
        insetBlockStart: 0,
        insetInlineStart: 0,
        insetInlineEnd: `${this.scrollbarOffsetInline}px`,
        width: `calc(100% - ${this.scrollbarOffsetInline}px)`,
        height: 'var(--scroll-shadow-size)',
      },
      right: {
        insetBlockStart: 0,
        insetBlockEnd: `${this.scrollbarOffsetInline}px`,
        insetInlineEnd: 'var(--scrollbar-offset-block)',
        height: `calc(100% - ${this.scrollbarOffsetBlock}px)`,
        width: 'var(--scroll-shadow-size)',
      },
      bottom: {
        insetBlockEnd: `${this.scrollbarOffsetBlock}px`,
        insetInlineStart: 0,
        insetInlineEnd: `${this.scrollbarOffsetInline}px`,
        width: `calc(100% - ${this.scrollbarOffsetInline}px)`,
        height: 'var(--scroll-shadow-size)',
      },
      left: {
        insetBlockStart: 0,
        insetBlockEnd: `${this.scrollbarOffsetBlock}`,
        insetInlineStart: 0,
        height: `calc(100% - ${this.scrollbarOffsetBlock}px)`,
        width: 'var(--scroll-shadow-size)',
      },
    }[side];
  }

  /**
   * Create a shadow object
   * @param {'top'|'right'|'bottom'|'left'} side
   * @returns {HTMLElement}
   */
  createShadow(side) {
    const edge = document.createElement(`craft-shadow`);
    edge.setAttribute('edge', side);

    Object.assign(edge.style, {
      position: 'absolute',
      pointerEvents: 'none',
      opacity: 0,
      transition: 'opacity 0.1s',
      zIndex: 1,
      backgroundImage: this.getGradientFor(side),
      ...this.getEdgeStyles(side),
    });

    return edge;
  }

  /**
   * Create all the shadows
   * @returns {Object<string, HTMLElement>}
   */
  createShadows() {
    return ['top', 'right', 'bottom', 'left'].reduce((acc, side) => {
      const edge = this.createShadow(side);
      this.appendChild(edge);

      acc[side] = edge;
      return acc;
    }, {});
  }
}

customElements.define('craft-scroll-container', CraftScrollContainer);
