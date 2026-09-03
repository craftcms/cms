import {html, LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import styles from './reorder-button.styles.js';
import {t} from '@src/utilities/translate';
import '../action-menu/action-menu.js';
import '../action-item/action-item.js';
import '../button/button.js';
import '../icon/icon.js';

export type ReorderPosition = 'first' | 'middle' | 'last';
export type ReorderDirection = 'up' | 'down';
export type ReorderOrientation = 'vertical' | 'horizontal';

/**
 * @summary A drag handle that also exposes "Move up"/"Move down" actions via a
 * menu, for reordering an item within a list. Horizontal lists get "Move
 * forward"/"Move backward" instead (see `orientation`).
 * @since 1.0
 *
 * @dependency craft-action-menu
 * @dependency craft-action-item
 * @dependency craft-button
 * @dependency craft-icon
 *
 * @fires {CustomEvent<{direction: ReorderDirection}>} craft-reorder - Emitted when the
 *   user chooses a move action. `event.detail.direction` is `'up'` or `'down'`
 *   regardless of orientation: `'up'` always means toward the start of the list
 *   ("Move forward" when horizontal) and `'down'` toward the end.
 */
export default class CraftReorderButton extends LitElement {
  static override styles = [styles];

  /** Accessible label for the drag handle. Defaults to a localized "Reorder". */
  @property() label: string | null = null;

  /**
   * The item's position within its list. Disables "Move up" at `first` and
   * "Move down" at `last`.
   */
  @property({reflect: true}) position: ReorderPosition = 'middle';

  /**
   * The list's flow direction. `vertical` (default) renders Move up/down;
   * `horizontal` renders Move forward/backward, with left/right arrows that
   * honor the closest writing direction (`dir` attribute, falling back to the
   * document direction) so "forward" always points toward the start of the
   * list. `position` semantics are unchanged: `first` disables the
   * up/forward action, `last` the down/backward one.
   */
  @property({reflect: true}) orientation: ReorderOrientation = 'vertical';

  /** Theme variant forwarded to the underlying invoker button. */
  @property({reflect: true}) variant: string = 'plain';

  /**
   * Disables the button: blocks pointer interaction (so it can't open the menu or
   * act as a drag handle), prevents reorder actions, and visually dims it. Defaults
   * to `false`.
   */
  @property({reflect: true, type: Boolean}) disabled = false;

  override updated(changed: PropertyValues<this>) {
    super.updated(changed);
    if (changed.has('disabled')) {
      if (this.disabled) {
        this.setAttribute('aria-disabled', 'true');
      } else {
        this.removeAttribute('aria-disabled');
      }
    }
  }

  private _reorder(direction: ReorderDirection) {
    if (this.disabled) {
      return;
    }

    if (
      (direction === 'up' && this.position === 'first') ||
      (direction === 'down' && this.position === 'last')
    ) {
      return;
    }

    this.dispatchEvent(
      new CustomEvent<{direction: ReorderDirection}>('craft-reorder', {
        detail: {direction},
        bubbles: true,
        composed: true,
      })
    );
  }

  /** Whether the closest writing direction is RTL (same lookup as `craft-field`). */
  private _isRtl(): boolean {
    const dir =
      (this.closest('[dir]') as HTMLElement | null)?.getAttribute('dir') ??
      document.documentElement.getAttribute('dir');
    return dir?.toLowerCase() === 'rtl';
  }

  override render() {
    const label = this.label ?? t('Reorder');
    const horizontal = this.orientation === 'horizontal';
    const rtl = horizontal && this._isRtl();
    // "up" = toward the start of the list; on horizontal lists that's the
    // inline-start side, so the arrows flip with the writing direction.
    const upIcon = horizontal
      ? rtl
        ? 'arrow-right'
        : 'arrow-left'
      : 'arrow-up';
    const downIcon = horizontal
      ? rtl
        ? 'arrow-left'
        : 'arrow-right'
      : 'arrow-down';
    const upLabel = horizontal ? t('Move forward') : t('Move up');
    const downLabel = horizontal ? t('Move backward') : t('Move down');

    return html`
      <craft-action-menu ?disabled="${this.disabled}">
        <craft-button
          slot="invoker"
          type="button"
          icon
          size="small"
          variant="${this.variant}"
          ?disabled="${this.disabled}"
        >
          <craft-icon label="${label}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
              <path
                fill="currentColor"
                d="M71.3 295.6c-21.9-21.9-21.9-57.3 0-79.2s57.3-21.9 79.2 0 21.9 57.3 0 79.2-57.4 21.9-79.2 0Zm113.1-113.1c-21.9-21.9-21.9-57.3 0-79.2s57.3-21.9 79.2 0c21.9 21.9 21.9 57.3 0 79.2s-57.3 21.8-79.2 0Zm0 147c21.9-21.9 57.3-21.9 79.2 0 21.9 21.9 21.9 57.3 0 79.2s-57.3 21.9-79.2 0c-21.9-21.8-21.9-57.3 0-79.2Zm113.1-113.1c21.9-21.9 57.3-21.9 79.2 0s21.9 57.3 0 79.2-57.3 21.9-79.2 0c-21.8-21.9-21.8-57.3 0-79.2Z"
              />
            </svg>
          </craft-icon>
        </craft-button>

        <div slot="content">
          <craft-action-item
            icon="${upIcon}"
            ?disabled="${this.position === 'first'}"
            @click="${() => this._reorder('up')}"
            data-action="moveUp"
            command="--move-up"
            >${upLabel}</craft-action-item
          >
          <craft-action-item
            icon="${downIcon}"
            ?disabled="${this.position === 'last'}"
            @click="${() => this._reorder('down')}"
            data-action="moveDown"
            command="--move-down"
            >${downLabel}</craft-action-item
          >
        </div>
      </craft-action-menu>
    `;
  }
}

if (!customElements.get('craft-reorder-button')) {
  customElements.define('craft-reorder-button', CraftReorderButton);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-reorder-button': CraftReorderButton;
  }
}
