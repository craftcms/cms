import {property, state} from 'lit/decorators.js';
import type {CSSResultGroup} from 'lit';
import {html, LitElement, nothing} from 'lit';
import styles from './avatar.styles.js';

/**
 * @summary Stands in for a user or object with their initials on a gradient
 * disc. Draws initials rather than loading a picture, so it works where there
 * may not be one — use `craft-thumbnail` when there is.
 *
 * Without a `label` there are no initials to draw and nothing to announce, so
 * the disc falls back to a question mark and is hidden from assistive
 * technology rather than exposed as an unnamed image.
 *
 * @cssproperty [--color-start=red] - Start color of the gradient
 * @cssproperty [--color-end=blue] - End color of the gradient
 * @cssproperty [--color-text=currentColor] - Color of the text
 * @cssproperty [--size=calc(30rem / 16)] - Overall size of the avatar. Defaults to 30px.
 */
export default class CraftAvatar extends LitElement {
  static override styles: CSSResultGroup = [styles];

  /** Accessible label for the avatar. */
  @property() label: string | null = null;

  /** Unique ID for the svg gradient */
  @state()
  private _gradientId: string | null = null;

  override connectedCallback() {
    super.connectedCallback();

    this._gradientId = `avatar-gradient-${Math.random()
      .toString(36)
      .slice(2, 8)}`;
  }

  private text() {
    if (!this.label) {
      return '?';
    }

    const segments = this.label.split(' ');
    return segments.map((segment) => segment.charAt(0).toUpperCase()).join('');
  }

  override render() {
    return html`
      <span class="avatar">
        <svg
          viewBox="0 0 100 100"
          xmlns="http://www.w3.org/2000/svg"
          role="${this.label ? 'img' : nothing}"
          aria-hidden="${this.label ? nothing : 'true'}"
        >
          ${this.label ? html`<title>${this.label}</title>` : nothing}
          <defs>
            <linearGradient
              id="${this._gradientId}"
              x1="0"
              y1="1"
              x2="1"
              y2="0"
            >
              <stop offset="0%" style="stop-color:var(--color-start)"></stop>
              <stop offset="100%" style="stop-color:var(--color-end)"></stop>
            </linearGradient>
          </defs>
          <circle
            cx="50"
            cy="50"
            r="50"
            fill="url(#${this._gradientId})"
            opacity="0.25"
          ></circle>
          <text class="avatar__text" x="50" y="64" font-size="44">
            ${this.text()}
          </text>
        </svg>
      </span>
    `;
  }
}

if (!customElements.get('craft-avatar')) {
  customElements.define('craft-avatar', CraftAvatar);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-avatar': CraftAvatar;
  }
}
