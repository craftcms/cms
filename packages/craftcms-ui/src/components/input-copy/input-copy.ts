import CraftInput from '../input/input.js';
import {html} from 'lit';
import {property} from 'lit/decorators.js';
import {t} from '@src/utilities/translate';
import styles from './input-copy.styles.js';
import '../copy-button/copy-button.js';
import type CraftCopyButton from '../copy-button/copy-button.js';

/**
 * @summary A read-only text input with an integrated copy-to-clipboard button
 * in the suffix. The value displayed in the textbox and the value sent to the
 * clipboard can be set independently via `value` and `copy-value`.
 *
 * @event craft-copy - Emitted when the value is copied to the clipboard.
 * @event craft-error - Emitted when the value could not be copied to the clipboard.
 *
 * @slot label - Field label.
 * @slot help-text - Supplementary help text.
 * @slot feedback - Validation feedback.
 */
export default class CraftInputCopy extends CraftInput {
  static override get styles() {
    return [...super.styles, styles];
  }

  /**
   * Value sent to the clipboard when the copy button is clicked. When omitted,
   * the input's displayed value is used instead.
   */
  @property({type: String, attribute: 'copy-value'})
  copyValue?: string;

  protected _renderSuffix() {
    return html`<craft-copy-button
      tooltip-label="${t('Copy')}"
    ></craft-copy-button>`;
  }

  /**
   * Adds the copy button to the field, in the suffix slot.
   */
  override get slots() {
    return {
      ...super.slots,
      // Render as a direct host child so the copy button itself carries
      // slot="suffix", rather than being nested in SlotMixin's wrapper div.
      suffix: () => ({
        template: this._renderSuffix(),
        renderAsDirectHostChild: true,
      }),
    };
  }

  override connectedCallback() {
    super.connectedCallback();
    this.readOnly = true;
  }

  protected _copyButton(): CraftCopyButton | null {
    return this.querySelector<CraftCopyButton>('craft-copy-button');
  }

  protected _syncCopyButton() {
    const btn = this._copyButton();
    if (!btn) return;
    btn.value = this.copyValue ?? String(this.modelValue ?? '');
  }

  override updated(changedProperties: Map<string | symbol, unknown>) {
    super.updated(changedProperties);
    // Keep read-only state enforced even if the attribute is removed at runtime.
    this.readOnly = true;
    this._syncCopyButton();
  }
}

if (!customElements.get('craft-input-copy')) {
  customElements.define('craft-input-copy', CraftInputCopy);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-input-copy': CraftInputCopy;
  }
}
