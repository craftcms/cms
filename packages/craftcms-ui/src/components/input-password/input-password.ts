import {css, html, type PropertyValues} from 'lit';
import {property, state} from 'lit/decorators.js';
import {LionInput} from '@lion/ui/input.js';
import {inputStyles, baseFormControlStyles} from '@src/styles/form.styles';
import {t} from '@src/utilities/translate';
import '../icon/icon.js';
import '../button/button.js';

export default class CraftInputPassword extends LionInput {
  @property({attribute: 'passwordrules'}) passwordRules = '';

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
          border: none;
        }

        ::slotted(.form-control) {
          ${baseFormControlStyles}
          --_input-end-end-radius: var(
            --c-input-radius,
            var(--c-radius-sm)
          ) !important;
          --_input-start-end-radius: var(
            --c-input-radius,
            var(--c-radius-sm)
          ) !important;
        }
      `,
    ];
  }

  constructor() {
    super();
    this.type = 'password';
  }

  override connectedCallback() {
    super.connectedCallback();
    this.#syncPasswordRules();
  }

  override updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);

    if (changedProperties.has('passwordRules')) {
      this.#syncPasswordRules();
    }
  }

  #syncPasswordRules() {
    if (this.passwordRules) {
      this._inputNode?.setAttribute('passwordrules', this.passwordRules);

      return;
    }

    this._inputNode?.removeAttribute('passwordrules');
  }

  reveal = () => {
    this._visible = !this._visible;
    this.type = this._visible ? 'text' : 'password';
  };

  // Note: no leading/trailing whitespace inside the template — with
  // `renderAsDirectHostChild` every root node is appended to the host, and
  // text nodes don't get a slot attribute.
  renderSuffix = () => {
    return html`<craft-button
      type="button"
      icon
      size="small"
      variant="plain"
      @click="${this.reveal}"
    >
      <span class="icon"
        >${this._visible
          ? html`<craft-icon
              name="eye-slash"
              label="${t('Hide')}"
            ></craft-icon>`
          : html`<craft-icon name="eye" label="${t('Show')}"></craft-icon>`}
      </span>
    </craft-button>`;
  };

  override get slots() {
    return {
      ...super.slots,
      // Render as a direct host child so the button itself carries
      // slot="suffix", rather than being nested in SlotMixin's wrapper div.
      suffix: () => {
        return {
          template: this.renderSuffix(),
          renderAsDirectHostChild: true,
        };
      },
    };
  }
}

if (!customElements.get('craft-input-password')) {
  customElements.define('craft-input-password', CraftInputPassword);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-input-password': CraftInputPassword;
  }
}
