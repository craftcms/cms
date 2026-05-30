import {LionSelectRich} from '@lion/ui/select-rich.js';
import {html} from 'lit';
import {property} from 'lit/decorators.js';
import styles from './select-rich.styles.js';
import CraftSelectInvoker from './select-invoker.js';
import '../option/option.js';
import '../icon/icon.js';

export default class CraftSelectRich extends LionSelectRich {
  static override get styles() {
    return [...super.styles, styles];
  }

  static override get scopedElements() {
    return {
      ...super.scopedElements,
      'lion-select-invoker': CraftSelectInvoker,
    };
  }

  @property({reflect: true, type: Boolean}) small = false;

  /**
   * Override Lion's default which sets a fixed pixel width on the invoker.
   * Use min-width instead so the invoker can grow with its content.
   */
  override async _alignInvokerWidth() {
    await this.updateComplete;

    if (!this._overlayCtrl?.content) {
      return;
    }

    const initContentDisplay = this._overlayCtrl.content.style.display;
    const initContentMinWidth =
      this._overlayCtrl.contentWrapperNode.style.minWidth;
    const initContentWidth = this._overlayCtrl.contentWrapperNode.style.width;
    this._overlayCtrl.content.style.display = '';
    this._overlayCtrl.contentWrapperNode.style.minWidth = 'auto';
    this._overlayCtrl.contentWrapperNode.style.width = 'auto';
    const contentWidth =
      this._overlayCtrl.contentWrapperNode.getBoundingClientRect().width;

    if (contentWidth > 0) {
      this._invokerNode.style.minWidth = `${contentWidth}px`;
      this._invokerNode.style.width = '';
    }

    this._overlayCtrl.content.style.display = initContentDisplay;
    this._overlayCtrl.contentWrapperNode.style.minWidth = initContentMinWidth;
    this._overlayCtrl.contentWrapperNode.style.width = initContentWidth;
  }

  // eslint-disable-next-line class-methods-use-this
  override _inputGroupInputTemplate() {
    return html`
      <div class="input-group__input">
        <slot name="invoker"></slot>
        <div id="overlay-content-node-wrapper">
          <slot name="input"></slot>
          <slot id="options-outlet"></slot>
        </div>
      </div>
    `;
  }
}

if (!customElements.get('craft-select-rich')) {
  customElements.define('craft-select-rich', CraftSelectRich);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-select-rich': CraftSelectRich;
  }
}
