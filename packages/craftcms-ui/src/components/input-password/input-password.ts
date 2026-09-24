import {css, html, type PropertyValues} from 'lit';
import {property, state} from 'lit/decorators.js';
import {baseFormControlStyles} from '@src/styles/form.styles';
import CraftInput from '../input/input.js';
import {t} from '@src/utilities/translate';
import '../icon/icon.js';
import '../button/button.js';

/**
 * @summary A password input with a button to reveal the value. The button
 * toggles the field between `password` and `text`, and names itself "Show" or
 * "Hide" as it goes.
 *
 * It extends `craft-input`, so everything on that control applies here too.
 *
 * @slot label - The control's label, as an alternative to the `label`
 *   attribute.
 * @slot help-text - Guidance shown below the label.
 * @slot feedback - Validation messages.
 * @slot suffix - Supplied by the component: the reveal button. Slotting your
 *   own replaces it.
 */
export default class CraftInputPassword extends CraftInput {
  /**
   * Rules for a password manager generating a password, passed through to the
   * native input's `passwordrules` attribute.
   */
  @property({attribute: 'passwordrules'}) passwordRules = '';

  @state()
  protected _visible = false;

  static override get styles() {
    return [
      ...super.styles,
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

  /**
   * Starts at `password` and flips to `text` while the value is revealed, which
   * is how the reveal button works.
   */
  override type = 'password';

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

  protected reveal = () => {
    this._visible = !this._visible;
    this.type = this._visible ? 'text' : 'password';
  };

  // Note: no leading/trailing whitespace inside the template — with
  // `renderAsDirectHostChild` every root node is appended to the host, and
  // text nodes don't get a slot attribute.
  protected renderSuffix = () => {
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

  /**
   * Adds the reveal button to the field, in the suffix slot.
   */
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
