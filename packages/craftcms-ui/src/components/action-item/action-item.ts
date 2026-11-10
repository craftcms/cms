import {html, LitElement, nothing} from 'lit';
import {property, state} from 'lit/decorators.js';
import styles from './action-item.styles.js';
import {type AsyncState, AsyncStates} from '@src/types';
import hostStyles from '@src/styles/host.styles';
import variantsStyles from '@src/styles/variants.styles';
import {LightDomController} from '@src/controllers/LightDomController';
import a11yErrorStyles from '@src/styles/a11y-error.styles.js';
import visuallyHiddenStyles from '@src/styles/visually-hidden.styles.js';
import {linkTarget, newTabCue} from '@src/utilities/external-links';
import {cancelIdle, whenIdle} from '@src/utilities/idle';
import {classMap} from 'lit/directives/class-map.js';
import {ifDefined} from 'lit/directives/if-defined.js';

import '../shortcut/shortcut.js';
import {HasShortcut} from '@src/mixins/HasShortcut';
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
 * @attr shortcut - A keyboard shortcut shown at the end of the item: a key
 *   (`"S"`), or JSON naming its modifiers (`'{"key": "S", "shift": true}'`).
 *   Display only. Supplied by the `HasShortcut` mixin.
 *
 * @csspart control - The item's `<button>`, or `<a>` when `href` is set.
 *
 * @fires {CustomEvent} craft-state-change - Emitted whenever the item's
 *   action moves between idle, loading, success, and error. `detail` carries
 *   the new `state`, the action's `actionType`, and any feedback data.
 */
export default class CraftActionItem extends HasShortcut(LitElement) {
  static override styles = [
    hostStyles,
    variantsStyles,
    a11yErrorStyles,
    visuallyHiddenStyles,
    styles,
  ];

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
   * Optional Craft color name used to tint the item's icon, in the color's
   * loud tone. Black and white icons take the default text color, so they
   * stay visible in both themes. A custom property name (`--…`) uses the
   * color it holds instead, such as one that means something on its own.
   */
  @property({attribute: 'icon-color'}) iconColor: string | null = null;

  /**
   * The icon's color: the color's loud tone, which stays vibrant in both
   * themes. Black and white are the same in both themes, so one of them would
   * vanish against the surface; they take the default text color instead.
   */
  #iconColorValue(color: string): string {
    if (color.startsWith('--')) {
      return `var(${color}, currentColor)`;
    }

    return color === 'black' || color === 'white'
      ? 'var(--c-text-default)'
      : `var(--c-color-${color}-fill-loud, currentColor)`;
  }

  /** Renders the item as a link to this URL instead of as a button. */
  @property() href: string | null = null;

  /**
   * Anchor target, for `href` items that should open elsewhere. Unset, a link
   * to another host opens in a new tab.
   */
  @property() target: string | null = null;

  /** Anchor `rel`. Defaults to `noopener` when targeting a new context. */
  @property() rel: string | null = null;

  /**
   * Prevents the item from being activated, and dims it. A disabled item stays
   * focusable, and is announced as unavailable.
   */
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
   * Tells assistive tech the item is the current one in its set — the selected
   * entry of a list that picks what another part of the page shows. Sets
   * `aria-current` on the item's own button or link, which an attribute on the
   * host wouldn't reach. `active` only styles it.
   */
  @property({type: Boolean}) current: boolean = false;

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

  private _resetStateTimeout: ReturnType<typeof setTimeout> | null = null;

  override connectedCallback() {
    super.connectedCallback();
    this.addEventListener('click', this._blockDisabledClick, {capture: true});
    this.addEventListener('click', this);
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this.removeEventListener('click', this._blockDisabledClick, {
      capture: true,
    });
    this.removeEventListener('click', this);
    this._clearResetStateTimeout();
  }

  /**
   * Focuses the item's own button or link. `delegatesFocus` alone does this in
   * Chromium, but Firefox won't focus a host that has no box, and this one is
   * `display: contents`.
   */
  override focus(options?: FocusOptions) {
    const control =
      this.shadowRoot?.querySelector<HTMLElement>('[part="control"]');

    if (control) {
      control.focus(options);
    } else {
      super.focus(options);
    }
  }

  /** Lets a first render that's waiting for its closed menu go ahead. */
  #releaseFirstRender: (() => void) | null = null;

  /**
   * Holds the first render of an item in a closed menu until the browser is
   * idle. A page can carry hundreds of these, and none of them shows until
   * its menu opens, which renders them at once with {@link renderNow}.
   */
  protected override scheduleUpdate(): void | Promise<unknown> {
    const menu = this.hasUpdated
      ? null
      : this.closest<HTMLElement & {open?: boolean}>('craft-action-menu');

    if (!menu || menu.open) {
      return super.scheduleUpdate();
    }

    return new Promise<void>((resolve) => {
      const handle = whenIdle(() => this.#releaseFirstRender?.());
      this.#releaseFirstRender = () => {
        this.#releaseFirstRender = null;
        cancelIdle(handle);
        resolve();
      };
    }).then(() => super.scheduleUpdate());
  }

  /**
   * Renders an item whose first render is waiting for its closed menu, so it
   * has its size and button by the time the menu shows.
   *
   * @internal
   */
  renderNow(): void {
    if (this.#releaseFirstRender) {
      this.#releaseFirstRender();
      this.performUpdate();
    }
  }

  /**
   * Stops a disabled item's clicks before anything listening to it hears them.
   * Its button or link is only `aria-disabled`, which doesn't stop them.
   */
  private _blockDisabledClick = (event: Event) => {
    if (this.disabled) {
      event.preventDefault();
      event.stopImmediatePropagation();
    }
  };

  private _clearResetStateTimeout() {
    if (this._resetStateTimeout !== null) {
      clearTimeout(this._resetStateTimeout);
      this._resetStateTimeout = null;
    }
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
        this._clearResetStateTimeout();
        this._resetStateTimeout = setTimeout(() => {
          this._resetStateTimeout = null;
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
          name="xmark-large"
          style="color: var(--c-color-danger-on-normal)"
        ></craft-icon>`;
      default:
        return html`
          <slot name="icon">
            ${this.icon
              ? html`<craft-icon
                  name="${this.icon}"
                  style="${this.iconColor
                    ? `color: ${this.#iconColorValue(this.iconColor)}`
                    : nothing}"
                ></craft-icon>`
              : nothing}
          </slot>
        `;
    }
  }

  protected renderPrefix() {
    const hasIcon = !!this.querySelector('[slot="icon"]') || !!this.icon;
    const hasPrefix = !!this.querySelector('[slot="prefix"]');

    return html`
      ${this.type === 'checkbox' ? this.renderCheckbox() : nothing}
      ${hasPrefix ? html`<slot name="prefix" part="prefix"></slot>` : nothing}
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
        ${this.shortcutKeys
          ? html`<craft-shortcut
              ?alt="${this.shortcutKeys.alt}"
              ?shift="${this.shortcutKeys.shift}"
              >${this.shortcutKeys.key}</craft-shortcut
            >`
          : nothing}
      </span>
    `;
  }

  override render() {
    const target = linkTarget(this.href, this.target);

    return this.href
      ? html`
          <a
            part="control"
            class="${classMap({
              'action-item': true,
              'action-item--checkbox': this.type === 'checkbox',
            })}"
            href="${this.href}"
            aria-current="${this.current ? 'true' : nothing}"
            aria-disabled="${this.disabled ? 'true' : nothing}"
            aria-keyshortcuts="${this.shortcutAriaKeys ?? nothing}"
            target="${ifDefined(target ?? undefined)}"
            rel="${ifDefined(
              this.rel ?? (target === '_blank' ? 'noopener' : null) ?? undefined
            )}"
          >
            ${this.renderBody()}${!this.target && target
              ? html`<span class="cp-visually-hidden">${newTabCue()}</span>`
              : nothing}
          </a>
        `
      : html`
          <button
            type="button"
            part="control"
            class="${classMap({
              'action-item': true,
              'action-item--checkbox': this.type === 'checkbox',
            })}"
            aria-current="${this.current ? 'true' : nothing}"
            aria-keyshortcuts="${this.shortcutAriaKeys ?? nothing}"
            aria-disabled="${this.disabled ? 'true' : nothing}"
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
