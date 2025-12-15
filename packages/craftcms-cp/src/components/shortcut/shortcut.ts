import {css, html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';

/**
 * Display keyboard shortcut helpers
 *
 * Cmd/Ctrl/Super is implied and the OS is automatically detected via the
 * navigator.platform string.
 *
 * @slot - Non-modifier key
 */
export default class CraftShortcut extends LitElement {
  static override styles = css`
    :host {
      display: inline-flex;
    }

    .shortcut {
      font-size: 0.9em;
      padding: 0 var(--c-spacing-sm);
      background-color: var(--c-color-neutral-bg-subtle);
      border: 1px solid var(--c-color-neutral-border-subtle);
      border-radius: var(--c-radius-sm);
      box-shadow: var(--c-shadow-sm);
    }
  `;

  /** Display help text for alt */
  @property({type: Boolean})
  alt: boolean = false;

  /** Display help text for shift */
  @property({type: Boolean})
  shift: boolean = false;

  @property()
  os: 'Mac' | 'Windows' | 'Linux' | 'Unknown' = 'Unknown';

  constructor() {
    super();
    this.os = this.detectOS();
  }

  override connectedCallback() {
    super.connectedCallback();
    if (this.os === 'Unknown') {
      this.os = this.detectOS();
    }
  }

  private detectOS() {
    const platform = navigator.platform.toLowerCase();
    if (platform.includes('mac') || /iphone|ipad|ipod/.test(platform)) {
      return 'Mac';
    } else if (platform.includes('win')) {
      return 'Windows';
    } else if (platform.includes('linux')) {
      return 'Linux';
    }

    return 'Unknown';
  }

  private renderShortcutPrefix() {
    switch (this.os) {
      case 'Mac':
        return `${this.alt ? '⌥' : ''}${this.shift ? '⇧' : ''}⌘`;
      case 'Linux':
        return `Super+${this.alt ? 'Alt+' : ''}${this.shift ? 'Shift+' : ''}`;
      default:
        return `Ctrl+${this.alt ? 'Alt+' : ''}${this.shift ? 'Shift+' : ''}`;
    }
  }

  override render() {
    return html`<span class="shortcut"
      >${this.renderShortcutPrefix()}<slot></slot
    ></span>`;
  }
}

if (!customElements.get('craft-shortcut')) {
  customElements.define('craft-shortcut', CraftShortcut);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-shortcut': CraftShortcut;
  }
}
