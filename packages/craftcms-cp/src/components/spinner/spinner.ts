import {html, LitElement} from 'lit';
import {property, query} from 'lit/decorators.js';
import componentStyles from './spinner.styles.js';
import {classMap} from 'lit/directives/class-map.js';

export default class CraftSpinner extends LitElement {
  static override styles = [componentStyles];

  @property({reflect: true})
  visible: boolean = true;

  @query('.wrapper')
  wrapper!: HTMLElement | null;

  show() {
    this.visible = true;
    this.dispatchEvent(new CustomEvent('show'));
  }

  hide() {
    this.visible = false;
    this.dispatchEvent(new CustomEvent('hide'));
  }

  override focus() {
    this.wrapper?.focus();
  }

  protected override render() {
    return html`
      <div
        tabindex="-1"
        class="${classMap({
          wrapper: true,
          hidden: !this.visible,
        })}"
      >
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
