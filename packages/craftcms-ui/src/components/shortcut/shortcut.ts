import {css, html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';
import visuallyHiddenStyles from '@src/styles/visually-hidden.styles';

/** A modifier key, as shown and as named for assistive technology. */
interface ModifierKey {
  label: string;
  name: string;
}

/**
 * Display keyboard shortcut helpers
 *
 * Cmd/Ctrl/Super is implied and the OS is automatically detected via the
 * navigator.platform string. Each key is its own `<kbd>` keycap.
 *
 * @slot - Non-modifier key
 *
 * @cssproperty [--c-shortcut-shadow=var(--c-shadow-sm)] - The keycaps' shadow.
 */
export default class CraftShortcut extends LitElement {
  static override styles = [
    visuallyHiddenStyles,
    css`
      :host {
        display: inline-flex;
      }

      .shortcut {
        display: inline-flex;
        gap: 0.25em;
        font-size: 0.9em;
      }

      .shortcut__key {
        padding-block: 0;
        padding-inline: var(--c-spacing-sm);
        font: inherit;
        background-color: var(--c-color-neutral-fill-quiet);
        border: 1px solid var(--c-color-neutral-border-quiet);
        border-radius: var(--c-radius-sm);
        box-shadow: var(--c-shortcut-shadow, var(--c-shadow-sm));
      }
    `,
  ];

  /** Display help text for alt */
  @property({type: Boolean})
  alt: boolean = false;

  /** Display help text for shift */
  @property({type: Boolean})
  shift: boolean = false;

  /**
   * Which platform's modifier symbols to render. Detected from the browser
   * when not set, so a Mac shows ⌘ and Windows shows Ctrl.
   */
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

  /** The modifier keys, in the platform's usual order. */
  private modifierKeys(): ModifierKey[] {
    if (this.os === 'Mac') {
      return [
        ...(this.alt ? [{label: '⌥', name: 'Option'}] : []),
        ...(this.shift ? [{label: '⇧', name: 'Shift'}] : []),
        {label: '⌘', name: 'Command'},
      ];
    }

    const primary = this.os === 'Linux' ? 'Super' : 'Ctrl';

    return [
      primary,
      ...(this.alt ? ['Alt'] : []),
      ...(this.shift ? ['Shift'] : []),
    ].map((key) => ({label: key, name: key}));
  }

  override render() {
    return html`<span class="shortcut"
      >${this.modifierKeys().map((key) =>
        key.label === key.name
          ? html`<kbd class="shortcut__key">${key.label}</kbd>`
          : html`<kbd class="shortcut__key"
              ><span aria-hidden="true">${key.label}</span
              ><span class="cp-visually-hidden">${key.name}</span></kbd
            >`
      )}<kbd class="shortcut__key"><slot></slot></kbd
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
