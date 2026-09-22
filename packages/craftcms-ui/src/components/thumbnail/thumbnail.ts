import {property, state} from 'lit/decorators.js';
import type {CSSResultGroup, PropertyValues} from 'lit';
import {html, LitElement, nothing} from 'lit';
import {classMap} from 'lit/directives/class-map.js';
import {ifDefined} from 'lit/directives/if-defined.js';
import visuallyHiddenStyles from '@src/styles/visually-hidden.styles.js';
import styles from './thumbnail.styles.js';

/**
 * @summary Displays an image thumbnail with an optional checkered backing and
 * rounded corners. Images are lazy-loaded natively via the browser.
 *
 * When no `src` is provided the default slot is rendered instead, so an `<img>`
 * or `<svg>` can be supplied directly and still receive the checkered/rounded
 * styling.
 *
 * @slot - Custom thumbnail content (e.g. an `<img>` or `<svg>`), used when
 *   `src` is not set.
 *
 * @csspart thumbnail - The outer thumbnail wrapper.
 * @csspart image - The rendered `<img>` element (only when `src` is set).
 * @csspart cover - The frozen-frame canvas overlay, rendered in place of an
 *   animated source's motion (see `animated`).
 *
 * @cssproperty [--c-thumbnail-size=calc(34rem / 16)] - Overall size of the thumbnail box.
 * @cssproperty [--c-thumbnail-radius=--c-radius-full] - Corner radius applied when `rounded` is set.
 * @cssproperty [--c-thumbnail-checker-size=8px] - Size of a single checker square.
 * @cssproperty [--c-thumbnail-checker-color=hsl(211 13% 65% / 0.25)] - Color of the checker squares.
 */
export default class CraftThumbnail extends LitElement {
  static override styles: CSSResultGroup = [visuallyHiddenStyles, styles];

  /** Image source URL. When omitted, the default slot is rendered instead. */
  @property() src: string | null = null;

  /** How the image fills the thumbnail box. Letterbox backgrounds come from the server. */
  @property({reflect: true}) mode: 'crop' | 'fit' | 'stretch' | 'letterbox' =
    'fit';

  private svgAspectRatios = new Map<SVGSVGElement, string | null>();

  /** Candidate image sources for responsive rendering. */
  @property() srcset: string | null = null;

  /** Source sizes for the responsive image. */
  @property() sizes: string | null = null;

  /** Accessible alternative text for the image. */
  @property() alt = '';

  /** Intrinsic image width, used to reserve space and reduce layout shift. */
  @property({type: Number}) width: number | null = null;

  /** Intrinsic image height, used to reserve space and reduce layout shift. */
  @property({type: Number}) height: number | null = null;

  /** Native browser loading strategy for the image. Defaults to `lazy`. */
  @property() loading: 'lazy' | 'eager' = 'lazy';

  /**
   * Whether to render a checkered pattern behind the image, to show where a
   * transparent image ends. Off by default, and set only for the images that
   * warrant it — `Asset::hasCheckeredThumb()` decides on the server.
   */
  @property({type: Boolean, reflect: true}) checkered = false;

  /** Whether to round the corners of the image. */
  @property({type: Boolean, reflect: true}) rounded = false;

  /**
   * Whether the source may be an animated image (e.g. GIF/WEBP). Server-rendered
   * element thumbnails set this via `HasThumbnails::couldHaveAnimatedThumb()`; when
   * absent, a `.gif`/`.webp` `src`/`srcset` is treated as animated too.
   */
  @property({type: Boolean, reflect: true}) animated = false;

  private static readonly animatedExtensionPattern =
    /\.(?:gif|webp)(?=[?#\s,]|$)/i;

  private get isAnimated(): boolean {
    return (
      this.animated ||
      CraftThumbnail.animatedExtensionPattern.test(this.src ?? '') ||
      CraftThumbnail.animatedExtensionPattern.test(this.srcset ?? '')
    );
  }

  /**
   * Whether the cover canvas has actually been painted yet. Kept separate
   * from `isAnimated` so the real `<img>` stays normally visible (and
   * measurable) up through the moment it's captured — only hiding it once
   * there's a frozen frame to show instead, so nothing is ever left
   * playing underneath the cover for its transparent pixels to reveal.
   */
  @state() private frozen = false;

  /** The captured first frame, kept so a later resize can redraw with it. */
  private capturedFrame: HTMLImageElement | null = null;

  /**
   * Watches the real `<img>` for its box becoming (or staying) a real,
   * non-zero size — covers both "wasn't visible/laid out yet when it
   * loaded" (e.g. inside a closed tab or accordion) and later layout
   * changes, with one mechanism.
   */
  private resizeObserver: ResizeObserver | null = null;

  private restoreSlottedSvgs() {
    for (const [svg, aspectRatio] of this.svgAspectRatios) {
      if (aspectRatio === null) {
        svg.removeAttribute('preserveAspectRatio');
      } else {
        svg.setAttribute('preserveAspectRatio', aspectRatio);
      }
    }
    this.svgAspectRatios.clear();
  }

  private updateSlottedSvgs() {
    this.restoreSlottedSvgs();
    if (this.mode !== 'crop' && this.mode !== 'stretch') {
      return;
    }

    const slot = this.shadowRoot?.querySelector('slot');
    for (const element of slot?.assignedElements() ?? []) {
      if (element instanceof SVGSVGElement) {
        this.svgAspectRatios.set(
          element,
          element.getAttribute('preserveAspectRatio')
        );
        element.setAttribute(
          'preserveAspectRatio',
          this.mode === 'crop' ? 'xMidYMid slice' : 'none'
        );
      }
    }
  }

  override willUpdate(changedProperties: PropertyValues<this>) {
    const animatedInputsChanged =
      changedProperties.has('src') ||
      changedProperties.has('srcset') ||
      changedProperties.has('animated');

    if (animatedInputsChanged) {
      this.frozen = false;
      this.capturedFrame = null;
      this.resizeObserver?.disconnect();
      this.resizeObserver = null;

      // A `src` change already triggers its own fresh `load` event;
      // `srcset`/`animated` don't, so retry manually instead of risking a
      // permanently stale frozen/unfrozen state.
      if (!changedProperties.has('src')) {
        const image = this.shadowRoot?.querySelector('img');
        if (image?.complete) {
          this.attemptFreeze(image);
        }
      }
    }
  }

  override updated() {
    this.updateSlottedSvgs();
  }

  override connectedCallback() {
    super.connectedCallback();
    this.updateSlottedSvgs();
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this.restoreSlottedSvgs();
    this.resizeObserver?.disconnect();
  }

  override render() {
    const classes = {
      thumbnail: true,
      'thumbnail--checkered': this.checkered,
      'thumbnail--rounded': this.rounded,
      'thumbnail--crop': this.mode === 'crop',
      'thumbnail--stretch': this.mode === 'stretch',
    };

    return html`
      <div class="${classMap(classes)}" part="thumbnail">
        ${this.src
          ? html`<img
              class="${classMap({
                thumbnail__image: true,
                'cp-visually-hidden': this.frozen,
              })}"
              part="image"
              src="${this.src}"
              srcset="${ifDefined(this.srcset ?? undefined)}"
              sizes="${ifDefined(this.sizes ?? undefined)}"
              width="${ifDefined(this.width ?? undefined)}"
              height="${ifDefined(this.height ?? undefined)}"
              alt="${this.alt}"
              loading="${this.loading}"
              decoding="async"
              @load=${this.freezeFrame}
            />`
          : nothing}
        ${this.src && this.isAnimated
          ? html`<canvas
              class="${classMap({
                thumbnail__cover: true,
                'thumbnail__cover--pending': !this.frozen,
              })}"
              part="cover"
              aria-hidden="true"
              role="presentation"
            ></canvas>`
          : nothing}
        <slot @slotchange=${this.updateSlottedSvgs}></slot>
      </div>
    `;
  }

  private freezeFrame(event: Event) {
    this.attemptFreeze(event.target as HTMLImageElement);
  }

  /**
   * Loads the image's current source into a detached `Image`, which never
   * gets composited on screen and so never advances past its first frame —
   * unlike the visible `<img>`, which is actively animating by the time
   * this runs. That detached copy is what gets drawn onto the cover canvas,
   * sized to the visible image's rendered box.
   */
  private attemptFreeze(image: HTMLImageElement) {
    if (!this.isAnimated || !image.currentSrc) {
      return;
    }

    const frame = new Image();
    frame.onload = () => {
      this.capturedFrame = frame;
      this.resizeObserver?.disconnect();
      this.resizeObserver = new ResizeObserver(() => {
        if (this.capturedFrame) {
          this.paintCover(this.capturedFrame, image);
        }
      });
      this.resizeObserver.observe(image);
    };
    frame.src = image.currentSrc;
  }

  private paintCover(frame: HTMLImageElement, image: HTMLImageElement) {
    // A malformed source could still fire `load` with 0x0 dimensions; bail
    // out before the crop math below turns that into a divide-by-zero.
    if (!frame.naturalWidth || !frame.naturalHeight) {
      return;
    }

    const canvas = this.shadowRoot?.querySelector<HTMLCanvasElement>(
      'canvas.thumbnail__cover'
    );
    const ctx = canvas?.getContext('2d');

    if (!ctx) {
      return;
    }

    const width = image.clientWidth;
    const height = image.clientHeight;

    // Not laid out yet (e.g. still inside a closed tab or accordion) — skip
    // this attempt rather than freezing on a bogus 0x0 measurement. The
    // resize observer in freezeFrame() retries once it actually has a box.
    if (!width || !height) {
      return;
    }
    // Raster at the display's actual pixel density — sizing the canvas by
    // CSS pixels alone leaves it soft/blurry on high-DPI screens, since a
    // plain <img> gets that crispness for free but a canvas doesn't.
    const dpr = window.devicePixelRatio || 1;
    canvas.width = width * dpr;
    canvas.height = height * dpr;
    canvas.style.width = `${width}px`;
    canvas.style.height = `${height}px`;
    ctx.scale(dpr, dpr);

    if (this.mode === 'crop') {
      // Unlike fit/letterbox/stretch, crop's `object-fit: cover` means the
      // rendered box (clientWidth/clientHeight) doesn't share the source's
      // aspect ratio — draw only the centered region that `cover` would
      // have shown, instead of stretching the whole frame into that box.
      const scale = Math.max(
        width / frame.naturalWidth,
        height / frame.naturalHeight
      );
      const sWidth = width / scale;
      const sHeight = height / scale;
      ctx.drawImage(
        frame,
        (frame.naturalWidth - sWidth) / 2,
        (frame.naturalHeight - sHeight) / 2,
        sWidth,
        sHeight,
        0,
        0,
        width,
        height
      );
    } else {
      ctx.drawImage(frame, 0, 0, width, height);
    }

    this.frozen = true;
  }
}

if (!customElements.get('craft-thumbnail')) {
  customElements.define('craft-thumbnail', CraftThumbnail);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-thumbnail': CraftThumbnail;
  }
}
