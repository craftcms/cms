import WaIcon from '@awesome.me/webawesome/dist/components/icon/icon.js';
import {css} from 'lit';
import {property} from 'lit/decorators.js';

/**
 * craft-icon is just an alias to wa-icon from web awesome.
 *
 * Anything you can do over there you can do here.
 */
export default class CraftIcon extends WaIcon {
  @property({reflect: true}) appearance?: 'plain' | 'badge' = 'plain';

  override connectedCallback() {
    super.connectedCallback();

    if (this.appearance === 'badge' && !this.getAttribute('data-color')) {
      this.setAttribute('data-color', 'warning');
    }
  }

  static override get styles() {
    return [
      WaIcon.styles,
      css`
        :host {
          font-size: 0.8em;
        }

        :host([appearance~='badge']) {
          border: 1px solid var(--c-color-border-quiet);
          color: var(--c-color-on-quiet);
          background-color: var(--c-color-fill-quiet);
          border-radius: var(--c-radius-sm);
          width: 1.6em;
          height: 1.6em;

          svg {
            width: 0.9em;
          }
        }
      `,
    ];
  }
}

// Alias to `craft-icon`
if (!customElements.get('craft-icon')) {
  customElements.define('craft-icon', CraftIcon);
}
