import {LionButtonSubmit} from '@lion/ui/button.js';
import {html, nothing} from 'lit';
import {property, state} from 'lit/decorators.js';
import styles from './button.styles.js';
import '../spinner/spinner.js';
import '../icon/icon.js';
import {computeAccessibleName} from 'dom-accessibility-api';
import {classMap} from 'lit/directives/class-map.js';

/**
 * @summary Interactive element that triggers an action or event.
 * @since 1.0
 *
 * @dependency craft-spinner
 *
 * @slot - The button's label.
 * @slot prefix - Content to display before the label (typically an icon).
 * @slot suffix - Content to display after the label (typically an icon).
 *
 * @csspart content - The button's content wrapper.
 * @csspart prefix - The button's prefix slot.
 * @csspart label - The button's label slot.
 * @csspart suffix - The button's suffix slot.
 * @csspart spinner - Spinner that shows when the button is in a loading state.
 */
export default class CraftButton extends LionButtonSubmit {
  static override get styles() {
    return [...super.styles, styles];
  }

  override async firstUpdated(changedProperties: Map<string, any>) {
    super.firstUpdated(changedProperties);

    await this.updateComplete;

    const childComponents = this.querySelectorAll('craft-icon, craft-spinner');
    await Promise.all(
      Array.from(childComponents).map((child: any) => child.updateComplete)
    );

    if (!this.accessibleName) {
      this.accessibleName = computeAccessibleName(this);
    }

    this._hasAccessibilityError =
      !this.accessibleName || this.accessibleName.trim() === '';
  }

  /** The computed accessible name */
  @property() accessibleName: string;

  /** Visual appearance of the button */
  @property({reflect: true}) appearance:
    | 'accent'
    | 'plain'
    | 'filled'
    | 'dashed' = 'accent';

  /**
   * Theme variant of the button. Defaults to "default"
   *
   * Primary: The primary action on a page
   * Default: Used in most cases
   * Danger: Indicates a dangerous action, when data will be removed or deleted
   * Inherit: Useful for colorable elements, button will reflect the parent theme
   */
  @property({reflect: true}) variant:
    | 'primary'
    | 'default'
    | 'danger'
    | 'inherit' = 'default';

  /** Size of the button. Defaults to "medium" */
  @property({reflect: true}) size: 'zero' | 'small' | 'medium' | 'large' =
    'medium';

  /** Show a spinner instead of the label */
  @property({reflect: true, type: Boolean}) loading: boolean = false;

  /** Set align-items for the content */
  @property() align: 'start' | 'end' | 'center' = 'center';

  @property() icon: string | null = null;

  @state()
  private _hasAccessibilityError: boolean = false;

  override render() {
    return html`
      <!--@TODO need to figure this out-->
      <!--<div role="status" class="sr-only"></div>-->
      <div
        class="${classMap({
          'button-content': true,
          'button-content--start': this.align === 'start',
          'button-content--end': this.align === 'end',
          'a11y-error': this._hasAccessibilityError,
        })}"
        part="content"
      >
        <slot name="prefix" class="prefix" part="prefix">
          ${this.icon
            ? html`<craft-icon name="${this.icon}"></craft-icon>`
            : nothing}
        </slot>
        <slot class="label" part="label"></slot>
        <slot name="suffix" class="suffix" part="suffix"></slot>
      </div>
      ${this.loading
        ? html`<craft-spinner part="spinner"></craft-spinner>`
        : nothing}
    `;
  }
}

if (!customElements.get('craft-button')) {
  customElements.define('craft-button', CraftButton);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-button': CraftButton;
  }
}
