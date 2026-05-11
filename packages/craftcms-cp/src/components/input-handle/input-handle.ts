import {css} from 'lit';
import {CraftInput} from '@src/index';
import {property} from 'lit/decorators.js';

export default class CraftInputHandle extends CraftInput {
  static override get styles() {
    return [
      ...super.styles,
      css`
        .input-group__input {
          font-family: var(--c-font-mono);
          font-size: 0.9em;
        }
      `,
    ];
  }

  @property({reflect: true})
  override autocorrect = false;

  @property({reflect: true, type: String})
  override autocapitalize = 'off';

  override firstUpdated(changedProperties: Map<string, unknown>) {
    super.firstUpdated(changedProperties);

    this._inputNode?.setAttribute(
      'autocorrect',
      this.autocorrect ? 'on' : 'off'
    );
    this._inputNode?.setAttribute('autocapitalize', this.autocapitalize);
  }
}

if (!customElements.get('craft-input-handle')) {
  customElements.define('craft-input-handle', CraftInputHandle);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-input-handle': CraftInputHandle;
  }
}
