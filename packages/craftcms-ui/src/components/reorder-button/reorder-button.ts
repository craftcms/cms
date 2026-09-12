import {html, LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import styles from './reorder-button.styles.js';
import {t} from '@src/utilities/translate';
import '../action-menu/action-menu.js';
import '../action-item/action-item.js';
import '../button/button.js';
import '../icon/icon.js';

export type ReorderPosition = 'first' | 'middle' | 'last' | 'only';
export type ReorderDirection = 'up' | 'down';
export type ReorderOrientation = 'vertical' | 'horizontal';

export interface ReorderAction {
  direction: ReorderDirection;
  icon: string;
  label: string;
  disabled: boolean;
}

export function getReorderPosition(
  index: number,
  itemCount: number
): ReorderPosition {
  if (itemCount === 1) {
    return 'only';
  }

  if (index === 0) {
    return 'first';
  }

  return index === itemCount - 1 ? 'last' : 'middle';
}

export function getReorderActions(
  orientation: ReorderOrientation,
  position: ReorderPosition,
  rtl = false
): [ReorderAction, ReorderAction] {
  const horizontal = orientation === 'horizontal';

  return [
    {
      direction: 'up',
      icon: horizontal ? (rtl ? 'arrow-right' : 'arrow-left') : 'arrow-up',
      label: horizontal ? t('Move forward') : t('Move up'),
      disabled: position === 'first' || position === 'only',
    },
    {
      direction: 'down',
      icon: horizontal ? (rtl ? 'arrow-left' : 'arrow-right') : 'arrow-down',
      label: horizontal ? t('Move backward') : t('Move down'),
      disabled: position === 'last' || position === 'only',
    },
  ];
}

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
 * @fires {CustomEvent<{direction: ReorderDirection}>} reorder - Emitted when the
 *   user chooses a move action. `event.detail.direction` is `'up'` or `'down'`
 *   regardless of orientation: `'up'` always means toward the start of the list
 *   ("Move forward" when horizontal) and `'down'` toward the end.
 */
export default class CraftReorderButton extends LitElement {
  static override styles = [styles];

  /** Accessible label for the drag handle. Defaults to a localized "Reorder". */
  @property() label: string | null = null;

  /**
   * The item's position within its list. Disables "Move up" at `first`, "Move
   * down" at `last`, and both at `only`.
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
      (direction === 'down' && this.position === 'last') ||
      this.position === 'only'
    ) {
      return;
    }

    this.dispatchEvent(
      new CustomEvent<{direction: ReorderDirection}>('reorder', {
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
    const [upAction, downAction] = getReorderActions(
      this.orientation,
      this.position,
      this._isRtl()
    );

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
            icon="${upAction.icon}"
            ?disabled="${upAction.disabled}"
            @click="${() => this._reorder('up')}"
            data-action="moveUp"
            command="--move-up"
            >${upAction.label}</craft-action-item
          >
          <craft-action-item
            icon="${downAction.icon}"
            ?disabled="${downAction.disabled}"
            @click="${() => this._reorder('down')}"
            data-action="moveDown"
            command="--move-down"
            >${downAction.label}</craft-action-item
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
