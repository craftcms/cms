import {css} from 'lit';
import {CraftInput} from '@src/index';

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

  constructor() {
    super();
    this.autocorrect = false;
  }

  override firstUpdated(changedProperties: Map<string, unknown>) {
    super.firstUpdated(changedProperties);

    this._inputNode?.setAttribute('autocapitalize', 'off');
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
