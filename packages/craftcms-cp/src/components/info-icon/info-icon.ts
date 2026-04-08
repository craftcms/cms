import {t} from '@src/utilities/translate';
import {html, LitElement} from 'lit';
import {property, query, queryAssignedElements, state} from 'lit/decorators.js';

import '../button/button';
import '../icon/icon';
import '../tooltip/tooltip';
import '../visually-hidden/visually-hidden';

export default class CraftInfoIcon extends LitElement {
  @property() label = t('More Info');

  @property() icon = 'circle-info';

  @property({type: Boolean, reflect: true}) disabled = false;

  @property() override id: string;

  @state() status = '';

  @query('c-tooltip') tooltip!: HTMLElement;

  #eventController = new AbortController();

  override connectedCallback() {
    super.connectedCallback();

    // Recreate event controller if it was aborted
    if (this.#eventController.signal.aborted) {
      this.#eventController = new AbortController();
    }

    if (!this.id) {
      this.id = `info-icon-${Math.random().toString(36).slice(2, 8)}`;
    }

    const {signal} = this.#eventController;

    this.addEventListener(
      'wa-after-show',
      () => {
        this.status = '';
        setTimeout(() => {
          this.status = 'Some new status';
        }, 200);
      },
      {signal}
    );

    this.addEventListener(
      'wa-after-hide',
      () => {
        this.status = '';
      },
      {signal}
    );
  }

  override disconnectedCallback() {
    this.#eventController.abort();
    super.disconnectedCallback();
  }

  override render() {
    return html`
      <div class="cp-info-icon">
        <craft-visually-hidden role="status">
          ${this.status}
        </craft-visually-hidden>
        
        <craft-button
          type="button"
          icon
          size="zero"
          appearance="plain"
          id="${this.id}"
        >
          <craft-icon name="${this.icon}" label="${this.label}"></craft-icon>
        </craft-button>

        <c-tooltip trigger="click" for="${this.id}"><slot></slot></c-tooltip>
      </div>
    `;
  }
}

if (!customElements.get('craft-info-icon')) {
  customElements.define('craft-info-icon', CraftInfoIcon);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-info-icon': CraftInfoIcon;
  }
}
