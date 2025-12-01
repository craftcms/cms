import {LitElement, html, css} from 'lit';
import {property} from 'lit/decorators.js';
import componentStyles from './spinner.styles.js';

export default class CraftSpinner extends LitElement {
  static override styles = [componentStyles];

  protected override render() {
    return html`
      <div tabindex="-1">
        <div class="spinner"></div>
        <span class="message visually-hidden"><slot /></span>
      </div>
    `;
  }
}

if (!customElements.get('craft-spinner')) {
  customElements.define('craft-spinner', CraftSpinner);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-spinner': CraftSpinner;
  }
}
