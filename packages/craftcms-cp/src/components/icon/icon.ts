import {css, html, LitElement, nothing, type PropertyValues} from 'lit';
import {property, state} from 'lit/decorators.js';
import {getIconUrl} from '../../utilities/icons.js';

/**
 * Module-level cache of icon fetches, keyed by URL. Failed fetches are
 * evicted so they can retry on the next request.
 */
const iconCache = new Map<string, Promise<SVGElement | null>>();

async function requestIcon(url: string): Promise<SVGElement | null> {
  try {
    const response = await fetch(url, {mode: 'cors'});

    if (!response.ok) {
      return null;
    }

    const container = document.createElement('div');
    container.innerHTML = await response.text();
    const svg = container.firstElementChild;

    if (svg?.tagName?.toLowerCase() !== 'svg') {
      return null;
    }

    return svg as SVGElement;
  } catch {
    return null;
  }
}

/**
 * craft-icon renders an SVG icon fetched from the CP's published icon assets
 * (see `getIconUrl` for the URL contract).
 *
 * In addition to name-based rendering, you may pass an inline `<svg>` (or
 * other element) into the default slot to render your own icon. When the slot
 * has assigned content, the slotted markup is rendered and the fetched
 * (name-based) icon is suppressed.
 */
export default class CraftIcon extends LitElement {
  /** Icon name, optionally prefixed with a variant (e.g. `custom-icons/foo`). */
  @property({reflect: true}) name?: string;

  @property({reflect: true}) family?: string;

  @property({reflect: true}) variant?: string;

  /**
   * A description of the icon for assistive devices. If omitted, the icon is
   * treated as presentational.
   */
  @property() label?: string;

  @property({reflect: true}) appearance?: 'plain' | 'badge' = 'plain';

  @state() private _svg: SVGElement | null = null;

  @state() private _hasSlottedContent = false;

  override connectedCallback() {
    super.connectedCallback();

    if (this.appearance === 'badge' && !this.getAttribute('data-color')) {
      this.setAttribute('data-color', 'warning');
    }

    // Slotted content wins over the fetched icon. `slotchange` keeps this in
    // sync later; seed it here so the first render is already correct.
    this._hasSlottedContent = this.childElementCount > 0;
  }

  #iconUrl(): string | null {
    if (!this.name) {
      return null;
    }

    // 'classic'/'solid' mirror the defaults the Web Awesome-era icon library
    // resolver applied; getIconUrl's own defaults differ for direct callers.
    return getIconUrl(
      this.name,
      this.family ?? 'classic',
      this.variant ?? 'solid'
    );
  }

  async #loadIcon() {
    const url = this.#iconUrl();

    if (url === null) {
      this._svg = null;
      return;
    }

    let request = iconCache.get(url);
    if (!request) {
      request = requestIcon(url);
      iconCache.set(url, request);
    }

    const svg = await request;

    if (svg === null) {
      iconCache.delete(url);
    }

    // The icon may have changed while the fetch was in flight.
    if (url !== this.#iconUrl()) {
      return;
    }

    if (svg === null) {
      this._svg = null;
      return;
    }

    const clone = svg.cloneNode(true) as SVGElement;
    clone.setAttribute('fill', 'currentColor');
    clone.setAttribute('part', 'svg');
    this._svg = clone;
  }

  #applyLabel() {
    const hasLabel = typeof this.label === 'string' && this.label.length > 0;

    if (hasLabel) {
      this.setAttribute('role', 'img');
      this.setAttribute('aria-label', this.label!);
      this.removeAttribute('aria-hidden');
    } else {
      this.removeAttribute('role');
      this.removeAttribute('aria-label');
      this.setAttribute('aria-hidden', 'true');
    }
  }

  protected override firstUpdated() {
    this.#applyLabel();
  }

  protected override updated(changed: PropertyValues) {
    super.updated(changed);

    if (
      changed.has('name') ||
      changed.has('family') ||
      changed.has('variant')
    ) {
      void this.#loadIcon();
    }

    if (changed.has('label') && this.hasUpdated) {
      this.#applyLabel();
    }
  }

  private _handleSlotChange(event: Event) {
    const slot = event.target as HTMLSlotElement;
    this._hasSlottedContent = slot.assignedElements({flatten: true}).length > 0;
  }

  override render() {
    return html`
      <slot @slotchange=${this._handleSlotChange}></slot>
      ${this._hasSlottedContent ? nothing : this._svg}
    `;
  }

  static override get styles() {
    return [
      css`
        :host {
          box-sizing: content-box;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          vertical-align: -0.125em;
          width: 1.25em;
          height: 1em;
          font-size: 0.8em;
        }

        svg,
        ::slotted(svg) {
          height: 1em;
          width: auto;
          overflow: visible;
        }

        :host([appearance~='badge']) {
          border: 1px solid var(--c-color-border-quiet);
          color: var(--c-color-on-quiet);
          background-color: var(--c-color-fill-quiet);
          border-radius: var(--c-radius-sm);
          width: 1.6em;
          height: 1.6em;

          svg {
            width: 0.9em;
          }
        }

        :host([appearance~='badge']) ::slotted(svg) {
          width: 0.9em;
        }
      `,
    ];
  }
}

// Alias to `craft-icon`
if (!customElements.get('craft-icon')) {
  customElements.define('craft-icon', CraftIcon);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-icon': CraftIcon;
  }
}
