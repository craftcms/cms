import {css, html, LitElement} from 'lit';
import {state} from 'lit/decorators.js';
import {LionInput} from '@lion/ui/input.js';
import {inputStyles} from '../../styles/form.styles.js';
import '../icon/icon.js';
import '../button/button.js';

export default class CraftInputPassword extends LionInput {
  @state()
  protected _visible = false;

  static override get styles() {
    return [
      ...super.styles,
      inputStyles,
      css`
        .input-group__container {
          position: relative;
        }

        .input-group__suffix {
          position: absolute;
          inset-inline-end: var(--c-input-spacing-inline);
          inset-block-start: 50%;
          transform: translateY(calc(-50%));
        }
      `,
    ];
  }

  constructor() {
    super();
    this.type = 'password';
  }

  reveal = () => {
    this._visible = !this._visible;
    this.type = this._visible ? 'text' : 'password';
  };

  renderSuffix = () => {
    return html`
      <craft-button
        icon
        size="small"
        variant="plain"
        @click="${this.reveal}"
        appearance="plain"
      >
        <span class="icon"
          >${this._visible
            ? html`<craft-icon name="eye-slash"></craft-icon>`
            : html`<craft-icon name="eye"></craft-icon>`}
        </span>
      </craft-button>
    `;
  };

  override get slots() {
    return {
      ...super.slots,
      suffix: () => {
        return {
          template: this.renderSuffix(),
        };
      },
    };
  }
}
