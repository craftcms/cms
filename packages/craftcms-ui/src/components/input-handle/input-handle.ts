import {css} from 'lit';
import {property} from 'lit/decorators.js';
import CraftInput from '@src/components/input/input.js';

/**
 * @summary A handle input — `craft-input` in a monospace face, with the
 * browser's autocorrection and auto-capitalisation turned off.
 *
 * Handles are typed exactly and read character by character, so the two
 * conveniences a browser applies to prose actively get in the way. Everything
 * else comes from `craft-input`.
 */
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

  /**
   * Whether the browser may autocorrect the value. Off here, unlike on
   * `craft-input`. Serialised as `on`/`off` rather than as a bare boolean
   * attribute, matching the native attribute it drives.
   */
  @property({
    reflect: true,
    converter: {
      fromAttribute: (value: string | null) => value !== 'off',
      toAttribute: (value: boolean) => (value ? 'on' : 'off'),
    },
  })
  override autocorrect = false;

  /**
   * The native `autocapitalize` mode. `off` here, unlike on `craft-input`.
   */
  @property({reflect: true, type: String})
  override autocapitalize = 'off';

  override updated(changedProperties: Map<string, unknown>) {
    super.updated(changedProperties);

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
