import {LionButtonSubmit} from '@lion/ui/button.js';
import {html, nothing} from 'lit';
import {property, state, query} from 'lit/decorators.js';
import {t} from '@src/utilities/translate';
import styles from './button.styles.js';
import visuallyHiddenStyles from '@src/styles/visually-hidden.styles.js';
import '../spinner/spinner.js';
import '../icon/icon.js';
import {computeAccessibleName} from 'dom-accessibility-api';
import {classMap} from 'lit/directives/class-map.js';
import {type BaseAction, normalizeAction, runAction} from '@src/actions';

export const ButtonVariant = {
  Primary: 'primary',
  Danger: 'danger',
  DangerPlain: 'danger-plain',
  Solid: 'solid',
  Fill: 'fill',
  Outline: 'outline',
  Dashed: 'dashed',
  Plain: 'plain',
  Link: 'link',
  None: 'none',
} as const;

export type ButtonVariant = (typeof ButtonVariant)[keyof typeof ButtonVariant];

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
    return [...super.styles, visuallyHiddenStyles, styles];
  }

  /**
   * Defaults to `button` rather than Lion's `submit`: a plain action button is
   * far the more common case, and Lion gates its submit and reset wiring
   * behind this, so the default also skips that work.
   */
  override type = 'button';

  constructor() {
    super();
    // We extend LionButtonSubmit so `type="submit"`/`"reset"` Just Work inside
    // forms, but a plain action button is the far more common case — and Lion
    // gates all of its submit/reset helper wiring behind `type`, so defaulting
    // to "button" also skips that runtime cost. (LionButtonSubmit's constructor
    // sets this to "submit"; we override it after super().)
    this.type = 'button';
  }

  override connectedCallback() {
    // Set link-appropriate host state *before* Lion runs, so it skips its
    // role="button" assignment and (via type) its submit-helper wiring.
    if (this.href && !this.disabled) {
      this.originalType = this.type;
      this.type = 'button';
      this.setAttribute('role', 'presentation');
      // Mark link state as applied so syncLinkHostState() below re-applies the
      // rest (tabindex) without re-capturing the (now-overwritten) type.
      this.linkHostStateApplied = true;
    }
    super.connectedCallback();
    this.syncLinkHostState();
    this.addEventListener('click', this.#handleActionClick);
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this.removeEventListener('click', this.#handleActionClick);

    if (this.announcementTimer) {
      clearTimeout(this.announcementTimer);
      this.announcementTimer = null;
    }
  }

  #handleActionClick = async (event: Event) => {
    const action = normalizeAction(this.action);

    if (!action || this.disabled) {
      return;
    }

    event.preventDefault();

    // Only show the spinner for http requests, matching craft-action-item.
    if (action.type === 'http') {
      this.loading = true;
    }

    try {
      await runAction(action, {trigger: this, sourceEvent: event});
    } finally {
      this.loading = false;
    }
  };
  override updated(changedProperties: Map<string, unknown>) {
    super.updated(changedProperties);
    if (changedProperties.has('href') || changedProperties.has('disabled')) {
      this.syncLinkHostState();
    }

    if (changedProperties.has('loading')) {
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
      if (!this.linkHostStateApplied) {
        // Capture the caller's intended type before we force "button" for the
        // link, so we can restore it if `href` is later removed.
        this.originalType = this.type;
      }
      this.setAttribute('role', 'presentation');
      this.tabIndex = -1;
      this.type = 'button';
      this.linkHostStateApplied = true;
    } else if (this.linkHostStateApplied) {
      this.setAttribute('role', 'button');
      this.type = this.originalType ?? 'button';
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

    // In link mode the host is role="presentation" (name not computable on
    // it); the real accessible element is the inner anchor.
    const nameTarget = this.isLink
      ? ((this.shadowRoot?.querySelector('a.link') as HTMLElement | null) ??
        this)
      : this;
    this._accessibleName = computeAccessibleName(nameTarget);

    this._hasAccessibilityError = this._accessibleName.trim() === '';
  }

  /**
   * The button's visual style. Defaults to "fill" (neutral fill).
   */
  @property({reflect: true}) variant: ButtonVariant = ButtonVariant.Fill;

  /**
   * Adopt the ambient colorable palette (from a `[data-color]` / colorable
   * ancestor) instead of the neutral palette. Only
   * affects the neutral variants; `primary` and `danger` stay stable.
   */
  @property({reflect: true, type: Boolean}) inherit: boolean = false;

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

  /**
   * Declarative action to run when the button is clicked, as a JSON `action`
   * attribute — the same primitives `craft-action-item` supports
   * (`http`/`event`/`clipboard`/`download`, run via `runAction()`). A raw
   * JSON string is accepted too (Vue's in-DOM compiler sets attribute
   * values as string properties on upgraded elements).
   */
  @property({type: Object}) action: BaseAction | string | null = null;

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

  @query('[data-live-region]') protected liveRegion: HTMLElement;

  /**
   * The name the button actually computes to, kept only so a button that ends
   * up nameless can be flagged.
   *
   * Deliberately internal: it records a name, it does not apply one. Exposing
   * it as an attribute invited consumers to "name" a button by setting it,
   * which silenced the warning below while leaving nothing in the DOM for a
   * screen reader. Name an icon-only button with `aria-label` on the host, or
   * with a `label` on the icon it contains.
   */
  @state()
  private _accessibleName: string = '';

  @state()
  private _hasAccessibilityError: boolean = false;

  private linkHostStateApplied = false;

  /** The caller's `type` before link mode forced it to "button". */
  private originalType: string | null = null;

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
      <span class="cp-visually-hidden" role="status" data-live-region></span>
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
