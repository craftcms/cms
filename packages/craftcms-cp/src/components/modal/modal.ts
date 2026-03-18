import type {CSSResultGroup} from 'lit';
import {html, LitElement} from 'lit';
import {OverlayMixin, withModalDialogConfig} from '@lion/ui/overlays.js';
import styles from './modal.styles';

/**
 * @summary Modal that extends the LionDialog web component
 */
export default class CraftModal extends OverlayMixin(LitElement) {
  static override get styles(): CSSResultGroup {
    return [super.styles ?? [], styles];
  }

  // @ts-ignore
  _defineOverlayConfig() {
    return {
      ...withModalDialogConfig(),
    };
  }

  override firstUpdated() {
    const contentNode = this._overlayContentNode;
    console.log(contentNode);
    contentNode.setAttribute('aria-label', 'My Dialog');
  }

  override render() {
    return html`
      <slot name="invoker"></slot>
      <div id="overlay-content-node-wrapper">
        <slot name="content"></slot>
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
