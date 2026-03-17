import type {CSSResultGroup} from 'lit';
import {html} from 'lit';
import {LionDialog} from '@lion/ui/dialog.js';
import styles from './modal.styles';

/**
 * @summary Modal that extends the LionDialog web component
 */
export default class CraftModal extends LionDialog {
  static override get styles(): CSSResultGroup {
    return [super.styles ?? [], styles];
  }

  override render() {
    return html`
      <slot name="invoker"></slot>
      <div id="overlay-content-node-wrapper">
        <div class="modal-window">
          <slot name="content"></slot>
        </div>
      </div>
    `;
  }
}

if (!customElements.get('craft-modal')) {
  customElements.define('craft-modal', CraftModal);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-modal': CraftModal;
  }
}
