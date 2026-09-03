import {html, LitElement, nothing} from 'lit';
import {property, state} from 'lit/decorators.js';
import styles from './action-item.styles.js';
import {type AsyncState, AsyncStates} from '@src/types';
import variantsStyles from '@src/styles/variants.styles';
import {LightDomController} from '@src/controllers/LightDomController';
import a11yErrorStyles from '@src/styles/a11y-error.styles.js';
import {classMap} from 'lit/directives/class-map.js';

import '../shortcut/shortcut.js';
import '../icon/icon.js';
import '../spinner/spinner.js';
import {
  type ActionFeedback,
  type BaseAction,
  type FeedbackData,
  normalizeAction,
  runAction,
} from '@src/actions';
import {Variant, type VariantValue} from '@src/constants/variants';

/** Anything that takes focus, and so cannot sit inside the item's own button. */
const INTERACTIVE = [
  'a[href]',
  'button',
  'input',
  'select',
  'textarea',
  '[tabindex]:not([tabindex="-1"])',
  'craft-button',
  'craft-action-item',
  'craft-checkbox',
  'craft-switch',
].join(', ');

/**
 * @summary A single entry in a menu, rendered as a button or — when `href` is
 * set — as a link. Action items carry an optional leading icon, a label, an
 * optional trailing shortcut or suffix, and can run an action on click.
 *
 * When `action` is set the item runs it on click and reports progress in
 * place: a spinner while an HTTP action is in flight, then a check or a cross,
 * before returning to its label. `feedback` supplies the messages and
 * `feedback-duration` controls how long they stay.
 *
 * @slot - The item's label.
 * @slot icon - Leading artwork, replacing the icon `icon` would render.
 * @slot checkmark - The checked indicator, shown when `type` is `checkbox`.
 *   Defaults to a check icon while `checked` is set.
 * @slot suffix - Trailing content, shown before the shortcut.
 *
 * @fires {CustomEvent} craft-state-change - Emitted whenever the item's
 *   action moves between idle, loading, success, and error. `detail` carries
 *   the new `state`, the action's `actionType`, and any feedback data.
 */
export default class CraftActionItem extends LitElement {
  static override styles = [variantsStyles, a11yErrorStyles, styles];

  /**
   * Delegate focus into the shadow root, so `host.focus()` (used by
   * `craft-action-menu`'s keyboard navigation) lands on the internal
   * button/anchor and native Enter/Space activation keeps working.
   */
  static override shadowRootOptions = {
    ...LitElement.shadowRootOptions,
    delegatesFocus: true,
  };

  /** Name of the icon to render before the label. */
  @property() icon: string | null = null;

  /**
   * Optional Craft color name used to tint the item's icon. The icon renders
   * with `currentColor`, so we set the icon element's `color` to a matching
   * `--c-color-*` token.
   */
  @property({attribute: 'icon-color'}) iconColor: string | null = null;

  /** Renders the item as a link to this URL instead of as a button. */
  @property() href: string | null = null;

  /** Prevents the item from being activated, and dims it. */
  @property({type: Boolean}) disabled: boolean = false;

  /**
   * The semantic color group the item draws its tokens from. `danger` is the
   * one to reach for on a destructive entry.
   */
  @property({reflect: true}) variant: VariantValue = Variant.Neutral;

  /** Whether a `checkbox` item is checked. Ignored for a `button` item. */
  @property({type: Boolean}) checked: boolean = false;

  /**
   * Marks the item as the current one — the entry a menu opens onto, or the
   * option already in effect. Reflected so it can be styled from outside.
   */
  @property({type: Boolean, reflect: true}) active: boolean = false;

  /**
   * `checkbox` reserves room for a checkmark before the icon, so a list of
   * options stays aligned whether or not each one is checked.
   */
  @property() type: 'button' | 'checkbox' = 'button';

  /**
   * The action to run when the item is activated. Accepts an action
   * descriptor or a URL string. A descriptor's own `confirm` prompts before
   * the action runs.
   */
  @property({type: Object}) action: BaseAction | string | null = null;

  /** Messages shown in place of the label after an action succeeds or fails. */
  @property({type: Object}) feedback: ActionFeedback | null = null;

  /** How long feedback stays before the item returns to idle, in milliseconds. */
  @property({type: Number, attribute: 'feedback-duration'})
  feedbackDuration: number = 1000;

  @state() private state: AsyncState = AsyncStates.Idle;
  @state() private feedbackMessage: string | null = null;

  /**
   * A keyboard shortcut shown at the end of the item. Either a plain string
   * (`"S"`, `"ctrl+k"`) or an object naming the modifiers. Display only — the
   * item does not bind the key.
   */
  @property({
    converter: {
      fromAttribute(value: string | null) {
        if (value === null) return null;

        // Try to parse as JSON object first
        try {
          const parsed = JSON.parse(value);
          if (typeof parsed === 'object' && parsed !== null) {
            return parsed;
          }
        } catch {
          // Not JSON — treat as plain string shortcut
        }

        return value; // plain string like "k" or "ctrl+k"
      },
      toAttribute(value) {
        if (value === null) return null;
        if (typeof value === 'string') return value;
        return JSON.stringify(value);
      },
    },
  })
  shortcut: string | {alt?: boolean; shift?: boolean; key: string} | null =
    null;

  protected renderShortcut() {
    if (typeof this.shortcut === 'string') {
      return html`<craft-shortcut>${this.shortcut}</craft-shortcut>`;
    }

    if (this.shortcut !== null) {
      return html`<craft-shortcut
        ?alt="${this.shortcut.alt ?? false}"
        ?shift="${this.shortcut.shift ?? false}"
        >${this.shortcut.key}</craft-shortcut
      >`;
    }

    return nothing;
  }

  override connectedCallback() {
    super.connectedCallback();
    this.addEventListener('click', this);
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this.removeEventListener('click', this);
  }

  /**

   * Moves the item into an async state, optionally with feedback to announce.
   * Called by the item itself while running an action; call it directly when
   * driving the item from outside.

   */

  setState(state: AsyncState, detail: FeedbackData = {}) {
    this.state = state;
    this.feedbackMessage = detail.message ?? null;

    this.dispatchEvent(
      new CustomEvent('craft-state-change', {
        bubbles: true,
        composed: true,
        detail: {
          state,
          actionType: normalizeAction(this.action)?.type,
          ...detail,
        },
      })
    );
  }

  /**

   * The DOM `EventListener` interface, so the item can be passed to
   * `addEventListener()` directly. Runs the item's action.

   */

  async handleEvent(event: Event) {
    if (this.disabled) {
      event.preventDefault();
      return;
    }

    const action = normalizeAction(this.action);

    if (event.type === 'click' && action) {
      // Only show loading spinner for http requests
      if (action.type === 'http') {
        this.setState(AsyncStates.Loading);
      }

      try {
        await runAction(action, {trigger: this, sourceEvent: event});
        this.setState(AsyncStates.Success, this.feedback?.success);
      } catch (error: any) {
        this.setState(AsyncStates.Error, {
          message: error.message,
          ...(this.feedback?.error || {}),
        });
      } finally {
        setTimeout(() => {
          this.setState(AsyncStates.Idle);
        }, this.feedbackDuration);
      }
    }
  }

  protected renderCheckbox() {
    return html`<span class="action-item__check">
      <slot name="checkmark">
        ${this.checked ? html`<craft-icon name="check"></craft-icon>` : nothing}
      </slot>
    </span>`;
  }

  protected renderIcon() {
    switch (this.state) {
      case AsyncStates.Loading:
        return html`<craft-spinner style="--size: 0.8em"></craft-spinner>`;
      case AsyncStates.Success:
        return html`<craft-icon
          name="check"
          style="color: var(--c-color-success-on-normal)"
        ></craft-icon>`;
      case AsyncStates.Error:
        return html`<craft-icon
          name="xmark"
          style="color: var(--c-color-danger-on-normal)"
        ></craft-icon>`;
      default:
        return html`
          <slot name="icon">
            ${this.icon
              ? html`<craft-icon
                  name="${this.icon}"
                  style="${this.iconColor
                    ? `color: var(--c-color-${this.iconColor}-on-normal, currentColor)`
                    : nothing}"
                ></craft-icon>`
              : nothing}
          </slot>
        `;
    }
  }

  protected renderPrefix() {
    const hasIcon = !!this.querySelector('[slot="icon"]') || !!this.icon;

    return html`
      ${this.type === 'checkbox' ? this.renderCheckbox() : nothing}
      ${hasIcon
        ? html`<div class="action-item__icon">${this.renderIcon()}</div>`
        : nothing}
    `;
  }

  /**
   * Whether something focusable has been slotted into `suffix`. The slot sits
   * inside the item's own button or link, so a control there nests a control
   * inside a control — a shape assistive technology cannot present, and one
   * that swallows the click meant for the item.
   *
   * Flagged the way `craft-button` flags a button with no accessible name:
   * loudly, in the place it went wrong, because it is a mistake to fix rather
   * than a state to handle.
   */
  @state() private _hasNestedControl = false;

  /**
   * Re-checks whenever the light DOM moves, rather than on `slotchange`:
   * content is commonly slotted in after the item mounts, and a menu built at
   * runtime is exactly where this mistake gets made.
   */
  private _lightDom = new LightDomController(this, {
    onChange: () => this._checkSuffixForControls(),
  });

  private _checkSuffixForControls() {
    this._hasNestedControl = Array.from(this.children).some(
      (child) => child.slot === 'suffix' && child.matches(INTERACTIVE)
    );
  }

  protected renderBody() {
    return html`
      ${this.renderPrefix()}

      <span class="action-item__label">
        ${this.feedbackMessage ? this.feedbackMessage : html`<slot></slot>`}
      </span>

      <span
        class="${classMap({
          'action-item__suffix': true,
          'a11y-error': this._hasNestedControl,
        })}"
      >
        <slot name="suffix"></slot>
        ${this.shortcut ? this.renderShortcut() : nothing}
      </span>
    `;
  }

  override render() {
    return this.href
      ? html`
          <a
            class="${classMap({
              'action-item': true,
              'action-item--checkbox': this.type === 'checkbox',
            })}"
            href="${this.href}"
          >
            ${this.renderBody()}
          </a>
        `
      : html`
          <button
            type="button"
            class="${classMap({
              'action-item': true,
              'action-item--checkbox': this.type === 'checkbox',
            })}"
            ?disabled="${this.disabled}"
          >
            ${this.renderBody()}
          </button>
        `;
  }
}

if (!customElements.get('craft-action-item')) {
  customElements.define('craft-action-item', CraftActionItem);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-action-item': CraftActionItem;
  }
}
