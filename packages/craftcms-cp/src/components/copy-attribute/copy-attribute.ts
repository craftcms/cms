import {classMap} from 'lit/directives/class-map.js';
import {property, query, state} from 'lit/decorators.js';
import '@shoelace-style/shoelace/dist/components/visually-hidden/visually-hidden.js';
import '../tooltip/tooltip.js';
import '../copy-button/copy-button.js';
import {html, LitElement} from 'lit';
import hostStyles from '../../styles/host.styles.js';
import styles from './copy-attribute.styles.js';
import type {CSSResultGroup} from 'lit';
import CraftCopyButton from '../copy-button/copy-button.js';

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
 * @summary Displays a field handle and allows quick copying
 *
 * @event craft-copy - Emitted when the value is copied to the clipboard.
 * @event craft-error - Emitted when the value could not be copied to the clipboard.
 *
 * @slot - The default slot.
 */
export default class CraftCopyAttribute extends LitElement {
  static override styles: CSSResultGroup = [hostStyles, styles];

  @state() status: 'rest' | 'success' | 'error' = 'rest';

  @query('slot[name="copy-icon"]') copyIconEl!: HTMLSlotElement;
  @query('slot[name="success-icon"]') successIconEl!: HTMLSlotElement;
  @query('slot[name="error-icon"]') errorIconEl!: HTMLSlotElement;
  @query('craft-copy-button') copyButtonEl!: CraftCopyButton;

  /** The text value to copy */
  @property({type: String})
  value: string = '';

  /** Disables the copy button. */
  @property({type: Boolean, reflect: true})
  disabled: boolean = false;

  /** The length of time to show feedback before restoring the default trigger. */
  @property({attribute: 'feedback-duration', type: Number}) feedbackDuration =
    1000;

  @property({reflect: false})
  tooltipLabel: string = 'Copy';

  constructor() {
    super();
    this.addEventListener('craft-copy', () => {
      this.showStatus('success');
    });
    this.addEventListener('craft-error', () => {
      this.showStatus('error');
    });
  }

  getId(): string {
    return `attribute-${this.value
      .replace(/([a-z])([A-Z])/g, '$1-$2')
      .replace(/[\s_]+/g, '-')
      .toLowerCase()}`;
  }

  async showStatus(status: 'success' | 'error') {
    const statusIcon =
      status === 'success' ? this.successIconEl : this.errorIconEl;
    this.tooltipLabel = status === 'success' ? 'Copied' : 'Copy failed';

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
    }, this.feedbackDuration);
  }

  override render() {
    return html`
      <c-tooltip for="${this.getId()}">${this.tooltipLabel}</c-tooltip>
      <craft-copy-button
        value="${this.value}"
        id="${this.getId()}"
        class=${classMap({
          'copy-attribute': true,
          'copy-attribute--success': this.status === 'success',
          'copy-attribute--error': this.status === 'error',
        })}
      >
        ${this.value}

        <slot name="copy-icon" part="copy-icon">
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
      </craft-copy-button>
    `;
  }
}

if (!customElements.get('craft-copy-attribute')) {
  customElements.define('craft-copy-attribute', CraftCopyAttribute);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-copy-attribute': CraftCopyAttribute;
  }
}
