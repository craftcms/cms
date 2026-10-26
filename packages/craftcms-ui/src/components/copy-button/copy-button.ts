import {t} from '@src/utilities/translate';
import {property, query, state} from 'lit/decorators.js';
import {html, LitElement, nothing} from 'lit';
import type {CSSResultGroup} from 'lit';
import styles from './copy-button.styles.js';
import '../tooltip/tooltip.js';
import type CraftTooltip from '../tooltip/tooltip.js';
import '../button/button.js';
import {ButtonVariant} from '../button/button.js';
import type CraftButton from '../button/button.js';

const STATUS_ICONS = {
  rest: 'copy',
  copying: 'copy',
  success: 'check',
  error: 'xmark-large',
} as const;

/**
 * @summary Copy values to the clipboard on click.
 *
 * Renders a `craft-button`, small and plain by default, so it sits in an
 * input's suffix like any other button there.
 *
 * @event craft-copy - Emitted when the value is copied to the clipboard.
 * @event craft-error - Emitted when the value could not be copied to the clipboard.
 *
 * @slot - The button's label. Leave it empty for an icon-only button.
 *
 * @csspart button - The `craft-button`.
 */
export default class CraftCopyButton extends LitElement {
  static override styles: CSSResultGroup = [styles];

  @state() protected isCopying = false;

  @state() protected status: 'rest' | 'copying' | 'success' | 'error' = 'rest';

  @state() protected hasLabel = false;

  @query('craft-tooltip') protected tooltipEl!: CraftTooltip;

  /** Value to copy on click */
  @property({type: String}) value = '';

  /** Prevents copying, and dims the button. */
  @property({type: Boolean}) disabled = false;

  /** The length of time to show feedback before restoring the default trigger. */
  @property({attribute: 'feedback-duration', type: Number}) feedbackDuration =
    1000;

  /**
   * Text shown in the button's tooltip, naming what will be copied.
   */
  @property({attribute: 'tooltip-label'})
  tooltipLabel: string | null = null;

  /** The button's size. */
  @property({reflect: true}) size: CraftButton['size'] = 'small';

  /** The button's visual style. */
  @property({reflect: true}) variant: ButtonVariant = ButtonVariant.Plain;

  /**
   * Copies `value` to the clipboard and shows the outcome. Resolves once the
   * status has been shown.
   */
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

  /**
   * Shows the copy outcome for a moment, then returns the button to rest.
   */
  async showStatus(status: 'success' | 'error') {
    clearTimeout(this.#restTimer);
    this.status = status;
    await this.updateComplete;
    this.tooltipEl?.repositionOverlay();

    this.#restTimer = setTimeout(() => {
      this.status = 'rest';
      this.tooltipEl?.hide();
    }, this.feedbackDuration);
  }

  #restTimer?: ReturnType<typeof setTimeout>;

  constructor() {
    super();

    this.addEventListener('craft-copy', () => {
      this.showStatus('success');
    });

    this.addEventListener('craft-error', () => {
      this.showStatus('error');
    });
  }

  override connectedCallback() {
    super.connectedCallback();

    if (!this.id) {
      this.id = `copy-${Math.floor(Math.random() * 100000000)}`;
    }
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    clearTimeout(this.#restTimer);
    this.status = 'rest';
  }

  /** The tooltip shows the outcome while it's up, then the label again. */
  get #tooltipText(): string {
    if (this.status === 'success') return t('Copied');
    if (this.status === 'error') return t('Copy failed');
    return this.tooltipLabel || t('Copy');
  }

  #onSlotChange(event: Event) {
    const slot = event.target as HTMLSlotElement;
    this.hasLabel = slot
      .assignedNodes({flatten: true})
      .some((node) => node instanceof Element || !!node.textContent?.trim());
  }

  override render() {
    // An icon-only button is left empty, so craft-button squares it off.
    return html`
      <craft-tooltip for="${this.id}">${this.#tooltipText}</craft-tooltip>
      <craft-button
        type="button"
        id="${this.id}"
        part="button"
        size="${this.size}"
        variant="${this.variant}"
        icon="${STATUS_ICONS[this.status]}"
        aria-label="${this.hasLabel ? nothing : t('Copy to clipboard')}"
        ?disabled=${this.disabled}
        @click="${this.copyValue}"
        >${this.hasLabel
          ? html`<slot @slotchange=${this.#onSlotChange}></slot>`
          : nothing}</craft-button
      >
      ${this.hasLabel
        ? nothing
        : html`<slot hidden @slotchange=${this.#onSlotChange}></slot>`}
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
