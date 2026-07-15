import {LionCollapsible} from '@lion/ui/collapsible.js';
import {css, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import '../button/button.js';

/**
 * Very simple disclosure trigger.
 *
 * Allows you to wrap a button[type="button"] and target an element to toggle the `data-state` attribute on.
 * Set `aria-expanded` on the button
 *
 * Without a slotted invoker, renders a default one from the `label`
 * attribute:
 *
 *     <craft-button type="button" appearance="plain" icon="chevron-down">${label}</craft-button>
 */
export default class CraftDisclosure extends LionCollapsible {
  static override get styles() {
    return [
      ...super.styles,
      css`
        ::slotted([slot='content']) {
          margin-block-start: var(--c-spacing-lg);
        }
      `,
    ];
  }

  /** Text for the default invoker button (). */
  @property() label = '';

  private __defaultInvoker: HTMLElement | null = null;

  override connectedCallback() {
    // Lion wires the click listener and aria state onto the slotted invoker
    // during connect, so the default one has to exist in the light DOM first.
    this.__ensureDefaultInvoker();
    super.connectedCallback();
  }

  protected override updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);

    if (changedProperties.has('label') && this.__defaultInvoker) {
      this.__defaultInvoker.textContent = this.label;
    }
  }

  private __ensureDefaultInvoker() {
    if (this._invokerNode) {
      return;
    }

    const button = document.createElement('craft-button');
    button.slot = 'invoker';
    button.setAttribute('type', 'button');
    button.setAttribute('appearance', 'plain');
    button.setAttribute('icon', 'chevron-down');
    button.textContent = this.label;

    this.__defaultInvoker = button;
    this.prepend(button);
  }
}

if (!customElements.get('craft-disclosure')) {
  customElements.define('craft-disclosure', CraftDisclosure);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-disclosure': CraftDisclosure;
  }
}
