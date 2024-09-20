/**
 * Proxy scrollbar
 *
 * Display a scrollbar that is synced with another element
 *
 * @property {string} scroller - The selector of the element that will be scrolled
 * @property {string} content - The selector of the element within the scroller containing the overflow content
 * @property {boolean} hidden - Whether the scrollbar should be hidden
 * @property {HTMLElement} proxy - The element that represents the scrollbar
 * @property {HTMLElement} scroller - The element that will be scrolled
 * @property {HTMLElement} content - The element within the scroller containing the overflow content
 */
class CraftProxyScrollbar extends HTMLElement {
  static observedAttributes = ['hidden'];

  get hidden() {
    return this.getAttribute('hidden');
  }

  get hasOverflow() {
    return this.content?.scrollWidth > this.scroller?.clientWidth;
  }

  connectedCallback() {
    this.ignoreScrollEvent = false;
    this.animation = false;

    this.scroller = document.querySelector(this.getAttribute('scroller'));
    this.content = document.querySelector(this.getAttribute('content'));

    if (!this.scroller || !this.content) {
      return;
    }

    this.proxy = document.createElement('div');
    this.proxy.style.height = '1px';
    this.proxy.style.width = this.content.getBoundingClientRect().width + 'px';

    this.appendChild(this.proxy);

    this.addEventListener('scroll', this.syncScroll(this.scroller, this));
    this.scroller.addEventListener(
      'scroll',
      this.syncScroll(this, this.scroller)
    );
    window.addEventListener('resize', this.handleResize.bind(this));

    Object.assign(this.style, {
      display: this.hasOverflow ? 'block' : 'none',
      overflowX: 'scroll',
    });
  }

  attributeChangedCallback(name, oldValue, newValue) {
    if (name === 'hidden') {
      this.style.display = newValue ? 'none' : 'block';
    }
  }

  disconnectedCallback() {
    this.proxy.remove();

    this.scroller.removeEventListener(
      'scroll',
      this.syncScroll(this.scroller, this)
    );
    this.scroller.removeEventListener(
      'scroll',
      this.syncScroll(this, this.scroller)
    );

    window.removeEventListener('resize', this.handleResize.bind(this));
  }

  handleResize() {
    this.proxy.style.width = this.content.getBoundingClientRect().width + 'px';

    if (this.hasOverflow) {
      this.removeAttribute('hidden');
    } else {
      this.setAttribute('hidden', 'true');
    }
  }

  syncScroll(a, b) {
    return () => {
      if (this.ignoreScrollEvent) {
        return false;
      }

      if (this.animation) {
        cancelAnimationFrame(this.animation);
      }

      this.animation = requestAnimationFrame(() => {
        this.ignoreScrollEvent = true;
        a.scrollLeft = b.scrollLeft;
        this.ignoreScrollEvent = false;
      });
    };
  }
}

customElements.define('craft-proxy-scrollbar', CraftProxyScrollbar);
