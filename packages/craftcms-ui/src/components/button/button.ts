import {LionButtonSubmit} from '@lion/ui/button.js';
import {html, nothing} from 'lit';
import {property, state, query} from 'lit/decorators.js';
import {t} from '@src/utilities/translate';
import styles from './button.styles.js';
import '../spinner/spinner.js';
import '../icon/icon.js';
import {computeAccessibleName} from 'dom-accessibility-api';
import {classMap} from 'lit/directives/class-map.js';
import variantsStyles from '@src/styles/variants.styles';

export const ButtonAppearance = {
  Solid: 'solid',
  Fill: 'fill',
  Outline: 'outline',
  Plain: 'plain',
} as const;

export const ButtonVariant = {
  Accent: 'accent',
  Neutral: 'neutral',
  Danger: 'danger',
} as const;

export type ButtonVariant = (typeof ButtonVariant)[keyof typeof ButtonVariant];
export type ButtonAppearance =
  (typeof ButtonAppearance)[keyof typeof ButtonAppearance];

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
 * @csspart link - The anchor element rendered when the button has an href.
 */
export default class CraftButton extends LionButtonSubmit {
  static override get styles() {
    return [...super.styles, variantsStyles, styles];
  }

  override connectedCallback() {
    // Set link-appropriate host state *before* Lion runs, so it skips its
    // role="button" assignment and (via type) its submit-helper wiring.
    if (this.href && !this.disabled) {
      this.type = 'button';
      this.setAttribute('role', 'presentation');
    }
    super.connectedCallback();
    this.syncLinkHostState();
  }

  override updated(changedProperties: Map<string, unknown>) {
    super.updated(changedProperties);
    if (changedProperties.has('href') || changedProperties.has('disabled')) {
      this.syncLinkHostState();
    } else if (changedProperties.has('loading')) {
      if (this.loading) {
        this.announceLoading();
      }
    }
  }

  private announceLoading() {
    this.liveRegion.textContent = t('Loading');

    if (this.announcementTimer) {
      clearTimeout(this.announcementTimer);
    }

    this.announcementTimer = setTimeout(() => {
      this.liveRegion.textContent = '';
    }, 5000);
  }

  private syncLinkHostState() {
    if (this.isLink) {
      this.setAttribute('role', 'presentation');
      this.tabIndex = -1;
      this.type = 'button';
      this.linkHostStateApplied = true;
    } else if (this.linkHostStateApplied) {
      this.setAttribute('role', 'button');
      this.type = 'submit';
      if (!this.disabled) {
        this.tabIndex = 0;
      }
      this.linkHostStateApplied = false;
    }
  }

  override async firstUpdated(changedProperties: Map<string, any>) {
    super.firstUpdated(changedProperties);

    await this.updateComplete;

    const childComponents = this.querySelectorAll('craft-icon, craft-spinner');
    await Promise.all(
      Array.from(childComponents).map((child: any) => child.updateComplete)
    );

    if (!this.accessibleName) {
      // In link mode the host is role="presentation" (name not computable on
      // it); the real accessible element is the inner anchor.
      const nameTarget = this.isLink
        ? ((this.shadowRoot?.querySelector('a.link') as HTMLElement | null) ??
          this)
        : this;
      this.accessibleName = computeAccessibleName(nameTarget);
    }

    this._hasAccessibilityError =
      !this.accessibleName || this.accessibleName.trim() === '';
  }

  /** The computed accessible name */
  @property({attribute: 'accessible-name'}) accessibleName: string;

  /** Visual appearance of the button */
  @property({reflect: true}) appearance: ButtonAppearance = 'solid';

  /**
   * Theme variant of the button. Defaults to "neutral"
   *
   * Accent: The primary action on a page
   * Neutral: Used in most cases
   * Danger: Indicates a dangerous action, when data will be removed or deleted
   * Inherit: Useful for colorable elements, button will reflect the parent theme
   */
  @property({reflect: true}) variant: ButtonVariant = 'neutral';

  /** Size of the button. Defaults to "medium" */
  @property({reflect: true}) size: 'zero' | 'small' | 'medium' | 'large' =
    'medium';

  /** The value submitted with the form or used for selection in a radio button-group */
  @property({reflect: true}) value: string;

  /** Whether the button is in a selected/active state (e.g. inside a radio button-group) */
  @property({reflect: true, type: Boolean}) override active: boolean = false;

  /** Show a spinner instead of the label */
  @property({reflect: true, type: Boolean}) loading: boolean = false;

  /** Set align-items for the content */
  @property() align: 'start' | 'end' | 'center' = 'center';

  /** Icon to be rendered within the content. */
  @property() icon: string | null = null;

  /** When set, the button renders as a link to this URL. */
  @property({reflect: true}) href: string | null = null;

  /** Anchor target (e.g. "_blank"). Forwarded to the rendered <a>. */
  @property() target: string | null = null;

  /** Anchor rel. Forwarded to the <a>; "noopener" is added for target="_blank". */
  @property() rel: string | null = null;

  /** Anchor download attribute. Forwarded to the <a>. */
  @property() download: string | null = null;

  /** Position of the icon. Defaults to "prefix" */
  @property({attribute: 'icon-position'}) iconPosition: 'prefix' | 'suffix' =
    'prefix';

  @query('[data-live-region]') liveRegion: HTMLElement;

  @state()
  private _hasAccessibilityError: boolean = false;

  private linkHostStateApplied = false;

  private announcementTimer: ReturnType<typeof setTimeout> | null = null;

  private get isLink(): boolean {
    return !!this.href && !this.disabled;
  }

  private get computedRel(): string | null {
    if (this.target === '_blank') {
      const tokens = new Set((this.rel ?? '').split(/\s+/).filter(Boolean));
      tokens.add('noopener');
      return Array.from(tokens).join(' ');
    }
    return this.rel;
  }

  override render() {
    const content = html`
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
          ${this.icon && this.iconPosition === 'prefix'
            ? html`<craft-icon name="${this.icon}"></craft-icon>`
            : nothing}
        </slot>
        <slot class="label" part="label"></slot>
        <slot name="suffix" class="suffix" part="suffix">
          ${this.icon && this.iconPosition === 'suffix'
            ? html`<craft-icon name="${this.icon}"></craft-icon>`
            : nothing}
        </slot>
      </div>
      ${this.loading
        ? html`<craft-spinner part="spinner"></craft-spinner>`
        : nothing}
      <span role="status" style="outline: 1px solid black; padding: 3px; background-color: turquoise;" data-live-region></span>
    `;

    if (this.isLink) {
      return html`
        <a
          class="link"
          part="link"
          href="${this.href}"
          target="${this.target ?? nothing}"
          rel="${this.computedRel ?? nothing}"
          download="${this.download ?? nothing}"
          >${content}</a
        >
      `;
    }

    return content;
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
