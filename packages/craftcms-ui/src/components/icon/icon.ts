import {
  css,
  html,
  LitElement,
  nothing,
  type PropertyValues,
  type TemplateResult,
} from 'lit';
import {property, state} from 'lit/decorators.js';
import {resolveIcon} from '../../utilities/icons.js';

/**
 * @summary An SVG icon, fetched by name from the CP's published icon assets.
 * Icons inherit `currentColor` and size with the surrounding text, so they sit
 * in a label or a button without being sized by hand.
 *
 * Slot your own `<svg>` instead of naming one when the icon set does not cover
 * what you need — slotted content takes over and the named icon is not
 * fetched.
 *
 * An icon is decorative unless you say otherwise: without a `label` it is
 * `aria-hidden`, and with one it becomes `role="img"` with that name. Icon-only
 * buttons need the label; an icon beside text does not.
 *
 * @slot - Your own icon markup, used in place of the named one.
 */
export default class CraftIcon extends LitElement {
  /** Icon name, optionally prefixed with a variant (e.g. `custom-icons/foo`). */
  @property({reflect: true}) name?: string;

  /**
   * Icon family to resolve the name against. Defaults to `classic`, which is
   * what the CP's own icons use.
   */
  @property({reflect: true}) family?: string;

  /** Weight within the family. Defaults to `solid`. */
  @property({reflect: true}) variant?: string;

  /**
   * A description of the icon for assistive devices. If omitted, the icon is
   * treated as presentational.
   */
  @property() label?: string;

  /**
   * `badge` draws the icon on a coloured disc, for a marker that has to read
   * as a status rather than as decoration. It defaults the disc to the warning
   * colour; set `data-color` for another.
   */
  @property({reflect: true}) appearance?: 'plain' | 'badge' = 'plain';

  @state() private _svg: TemplateResult | typeof nothing = nothing;

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

  #iconParams(): {name: string; family: string; variant: string} | null {
    if (!this.name) {
      return null;
    }

    // 'classic'/'solid' mirror the defaults the Web Awesome-era icon library
    // resolver applied; getIconUrl's own defaults differ for direct callers.
    return {
      name: this.name,
      family: this.family ?? 'classic',
      variant: this.variant ?? 'solid',
    };
  }

  async #loadIcon() {
    const icon = this.#iconParams();

    if (icon === null) {
      this._svg = nothing;
      return;
    }

    let svg: TemplateResult | typeof nothing;

    try {
      svg = await resolveIcon(icon.name, icon.family, icon.variant);
    } catch (error) {
      // oxlint-disable-next-line no-console
      console.error(error);
      svg = nothing;
    }

    const currentIcon = this.#iconParams();

    // The icon may have changed while the fetch was in flight.
    if (
      currentIcon === null ||
      icon.name !== currentIcon.name ||
      icon.family !== currentIcon.family ||
      icon.variant !== currentIcon.variant
    ) {
      return;
    }

    this._svg = svg;
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

        :host([data-color]) {
          color: var(--c-color-fill-loud);
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
