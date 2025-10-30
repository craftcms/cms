import {property, state, customElement} from 'lit/decorators.js';
import {html, css, LitElement} from 'lit';
import styles from './avatar.styles.js';
import type {CSSResultGroup} from 'lit';

/**
 * @summary Container to represent a user or object
 *
 * @cssproperty --color-start - Start color of the gradient
 * @cssproperty --color-end - End color of the gradient
 * @cssproperty --color-text - Color of the text
 * @cssproperty --size - Overall size of the avatar. Defaults to 30px.
 */
export default class CraftAvatar extends LitElement {
  static override styles: CSSResultGroup = [
    styles,
    css`
      :host {
        --color-start: red;
        --color-end: blue;
        --color-text: inherit;

        --size: 30px;
        display: contents;
      }

      .avatar {
        display: inline-flex;
        width: var(--size);
        aspect-ratio: 1;
        background-color: white;
        border-radius: var(--c-radius-full);
      }

      .avatar__text {
        line-height: 1;
        font-weight: 500;
        font-family: var(--c-font-body, sans-serif);
        text-anchor: middle;
        fill: black;
        user-select: none;
        pointer-events: none;
      }
    `,
  ];

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

  text() {
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
          role="img"
        >
          ${this.label ? html`<title>${this.label}</title>` : ''}
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
