import {property} from 'lit/decorators.js';
import type {CSSResultGroup} from 'lit';
import {html, LitElement, nothing} from 'lit';
import {classMap} from 'lit/directives/class-map.js';
import {ifDefined} from 'lit/directives/if-defined.js';
import styles from './thumbnail.styles.js';

/**
 * Boolean attribute converter that treats the string `"false"` as `false`.
 * Lit's built-in boolean converter is presence-based, so `checkered="false"`
 * would otherwise be truthy — this lets server-rendered markup disable the
 * attribute with `checkered="false"`.
 */
const defaultTrueBoolean = {
  fromAttribute: (value: string | null): boolean =>
    value !== null && value !== 'false',
  toAttribute: (value: boolean): string | null => (value ? '' : null),
};

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
 *
 * @cssproperty [--c-thumbnail-size=calc(34rem / 16)] - Overall size of the thumbnail box.
 * @cssproperty [--c-thumbnail-radius=--c-radius-full] - Corner radius applied when `rounded` is set.
 * @cssproperty [--c-thumbnail-checker-size=8px] - Size of a single checker square.
 * @cssproperty [--c-thumbnail-checker-color=hsl(211 13% 65% / 0.25)] - Color of the checker squares.
 */
export default class CraftThumbnail extends LitElement {
  static override styles: CSSResultGroup = [styles];

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

  /** Whether to render a checkered pattern behind the image. Enabled by default. */
  @property({reflect: true, converter: defaultTrueBoolean})
  checkered = true;

  /** Whether to round the corners of the image. */
  @property({type: Boolean, reflect: true}) rounded = false;

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
              class="thumbnail__image"
              part="image"
              src="${this.src}"
              srcset="${ifDefined(this.srcset ?? undefined)}"
              sizes="${ifDefined(this.sizes ?? undefined)}"
              width="${ifDefined(this.width ?? undefined)}"
              height="${ifDefined(this.height ?? undefined)}"
              alt="${this.alt}"
              loading="${this.loading}"
              decoding="async"
            />`
          : nothing}
        <slot @slotchange=${this.updateSlottedSvgs}></slot>
      </div>
    `;
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
