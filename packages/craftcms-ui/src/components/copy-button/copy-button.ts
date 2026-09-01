import {t} from '@src/utilities/translate';
import {property, query, state} from 'lit/decorators.js';
import {html, LitElement} from 'lit';
import type {CSSResultGroup} from 'lit';
import styles from './copy-button.styles.js';
import '../tooltip/tooltip.js';
import type CraftTooltip from '../tooltip/tooltip.js';
import '../visually-hidden/visually-hidden.js';

const animations = {
  'icon.in': {
    keyframes: [
      {scale: 0.25, opacity: 0.25},
      {scale: 1, opacity: 1},
    ],
    options: {duration: 100},
  },
  'icon.out': {
    keyframes: [
      {scale: 1, opacity: 1},
      {scale: 0.25, opacity: 0.25},
    ],
    options: {duration: 100},
  },
};

/**
 * @summary Copy values to the clipboard on click.
 *
 * @event craft-copy - Emitted when the value is copied to the clipboard.
 * @event craft-error - Emitted when the value could not be copied to the clipboard.
 *
 * @slot - The default slot.
 *
 * @csspart button - The main button element.
 */
export default class CraftCopyButton extends LitElement {
  static override styles: CSSResultGroup = [styles];

  @state() isCopying = false;

  @state() status: 'rest' | 'copying' | 'success' | 'error' = 'rest';

  @query('slot[name="copy-icon"]') copyIconEl!: HTMLSlotElement;
  @query('slot[name="success-icon"]') successIconEl!: HTMLSlotElement;
  @query('slot[name="error-icon"]') errorIconEl!: HTMLSlotElement;
  @query('craft-tooltip') tooltipEl!: CraftTooltip;

  /** Value to copy on click */
  @property({type: String}) value = '';

  @property({type: Boolean}) disabled = false;

  /** The length of time to show feedback before restoring the default trigger. */
  @property({attribute: 'feedback-duration', type: Number}) feedbackDuration =
    1000;

  @property({attribute: 'tooltip-label'})
  tooltipLabel: string | null = null;

  async copyValue() {
    if (this.status === 'copying' || this.disabled) {
      return;
    }

    this.status = 'copying';

    try {
      await navigator.clipboard.writeText(this.value);
      this.dispatchEvent(
        new CustomEvent('craft-copy', {
          bubbles: true,
          cancelable: false,
          composed: true,
          detail: {
            value: this.value,
          },
        })
      );
    } catch (error) {
      this.dispatchEvent(
        new CustomEvent('craft-error', {
          cancelable: false,
          composed: true,
          bubbles: true,
        })
      );
    } finally {
      this.isCopying = false;
    }
  }

  async showStatus(status: 'success' | 'error') {
    const statusIcon =
      status === 'success' ? this.successIconEl : this.errorIconEl;
    this.tooltipLabel = status === 'success' ? 'Copied' : 'Copy failed';
    await this.updateComplete;
    this.tooltipEl?.repositionOverlay();

    // Animate the copy icon out
    await statusIcon.animate(
      animations['icon.out'].keyframes,
      animations['icon.out'].options
    );
    this.copyIconEl.hidden = true;

    // Animate the status icon in
    statusIcon.hidden = false;
    await statusIcon.animate(
      animations['icon.in'].keyframes,
      animations['icon.in'].options
    );

    this.status = status;

    // Put everything back
    setTimeout(async () => {
      // Animate the status icon out
      await statusIcon.animate(
        animations['icon.out'].keyframes,
        animations['icon.out'].options
      );
      statusIcon.hidden = true;

      // Animate the copy icon in
      this.copyIconEl.hidden = false;
      await this.copyIconEl.animate(
        animations['icon.in'].keyframes,
        animations['icon.in'].options
      );

      this.status = 'rest';
      this.tooltipLabel = 'Copy';
      this.tooltipEl?.hide();
    }, this.feedbackDuration);
  }
  override connectedCallback() {
    super.connectedCallback();

    this.tooltipLabel = this.getAttribute('tooltip-label') || t('Copy');

    if (!this.id) {
      this.id = `copy-${Math.floor(Math.random() * 100000000)}`;
    }

    this.addEventListener('craft-copy', () => {
      this.showStatus('success');
    });

    this.addEventListener('craft-error', () => {
      this.showStatus('error');
    });
  }

  override render() {
    return html`
      <craft-tooltip for="${this.id}">${this.tooltipLabel}</craft-tooltip>
      <button
        type="button"
        id="${this.id}"
        @click="${this.copyValue}"
        ?disabled=${this.disabled}
        class="copy-button"
        part="button"
      >
        <slot></slot>
        <slot name="copy-icon">
          <span class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
              <path
                d="M288 448H64V224h64v-64H64c-35.3 0-64 28.7-64 64v224c0 35.3 28.7 64 64 64h224c35.3 0 64-28.7 64-64v-64h-64v64zM160 130h64v92h-64v-92zm288 0h64v92h-64v-92zM290 352v-64h92v64h-92zm0-288V0h92v64h-92zM224 98V64h34V0h-34c-35.3 0-64 28.7-64 64v34M414 64h34v34h64V64c0-35.3-28.7-64-64-64h-34m34 254v34h-34v64h34c35.3 0 64-28.7 64-64v-34M258 288h-34v-34h-64v34c0 35.3 28.7 64 64 64h34"
              />
            </svg>
          </span>
        </slot>

        <slot name="success-icon" part="success-icon" hidden>
          <craft-icon name="check"></craft-icon>
        </slot>

        <slot name="error-icon" part="error-icon" hidden>
          <craft-icon name="x"></craft-icon>
        </slot>

        <craft-visually-hidden>Copy to clipboard</craft-visually-hidden>
      </button>
    `;
  }
}

if (!customElements.get('craft-copy-button')) {
  customElements.define('craft-copy-button', CraftCopyButton);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-copy-button': CraftCopyButton;
  }
}
