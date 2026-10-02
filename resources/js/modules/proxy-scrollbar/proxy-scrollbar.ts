/**
 * Proxy scrollbar
 *
 * Displays a scrollbar that is synced with another element — a proxy that
 * mirrors the horizontal scroll position of a `scroller` containing overflow
 * `content`.
 *
 * Ported verbatim (jQuery-free already) out of the legacy CP bundle
 * (`CraftProxyScrollbar.js`).
 *
 * @example
 * <craft-proxy-scrollbar scroller="#foo" content="#foo .inner"></craft-proxy-scrollbar>
 */
export class ProxyScrollbar extends HTMLElement {
  static observedAttributes = ['hidden'];

  private ignoreScrollEvent = false;
  private animation: number | false = false;

  scroller: HTMLElement | null = null;
  content: HTMLElement | null = null;
  proxy: HTMLDivElement | null = null;

  get hasOverflow(): boolean {
    return (this.content?.scrollWidth ?? 0) > (this.scroller?.clientWidth ?? 0);
  }

  connectedCallback(): void {
    this.ignoreScrollEvent = false;
    this.animation = false;

    this.scroller = document.querySelector(this.getAttribute('scroller') ?? '');
    this.content = document.querySelector(this.getAttribute('content') ?? '');

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

  attributeChangedCallback(
    name: string,
    _oldValue: string | null,
    newValue: string | null
  ): void {
    if (name === 'hidden') {
      this.style.display = newValue ? 'none' : 'block';
    }
  }

  disconnectedCallback(): void {
    this.proxy?.remove();

    this.scroller?.removeEventListener(
      'scroll',
      this.syncScroll(this.scroller, this)
    );
    this.scroller?.removeEventListener(
      'scroll',
      this.syncScroll(this, this.scroller)
    );

    window.removeEventListener('resize', this.handleResize.bind(this));
  }

  handleResize(): void {
    if (!this.proxy || !this.content) {
      return;
    }

    this.proxy.style.width = this.content.getBoundingClientRect().width + 'px';

    if (this.hasOverflow) {
      this.removeAttribute('hidden');
    } else {
      this.setAttribute('hidden', 'true');
    }
  }

  syncScroll(a: HTMLElement, b: HTMLElement): () => void {
    return () => {
      if (this.ignoreScrollEvent) {
        return;
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
