import type {CSSResultGroup, PropertyValues} from 'lit';
import {html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';
import {OverlayMixin, withModalDialogConfig} from '@lion/ui/overlays.js';
import styles from './modal.styles';

/**
 * @summary Modal that extends the LionDialog web component
 */
export default class CraftModal extends OverlayMixin(LitElement) {
  @property({type: String})
  name: string | null = null;

  // @ts-ignore
  _defineOverlayConfig() {
    return {
      ...withModalDialogConfig(),
    };
  }

  static override get styles(): CSSResultGroup {
    return [super.styles ?? [], styles];
  }

  /**
   * Applies an aria-label to the content node with the name property.
   */
  __setAccessibleName() {
    if (!this.name) return;

    const contentNode = this._overlayContentNode;

    if (contentNode) {
      if (!this.name) return;
      contentNode.setAttribute('aria-label', this.name);
    }
  }

  override firstUpdated(changed: PropertyValues<this>) {
    super.firstUpdated(changed);

    if (changed.has('name')) {
      this.__setAccessibleName();
    }
  }

  override updated(changed: PropertyValues<this>) {
    super.updated(changed);

    if (changed.has('name')) {
      this.__setAccessibleName();
    }
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
