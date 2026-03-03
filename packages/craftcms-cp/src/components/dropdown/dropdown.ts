import WaDropdown from '@awesome.me/webawesome/dist/components/dropdown/dropdown.js';
import WaDropdownItem from '@awesome.me/webawesome/dist/components/dropdown-item/dropdown-item.js';
import {css} from 'lit';

export default class CraftDropdown extends WaDropdown {
  static override get styles() {
    return [
      WaDropdown.styles,
      css`
        :host {
          --wa-border-style: solid;
          --wa-border-width-s: 1px;
          --wa-color-surface-raised: var(--c-surface-raised);
          --wa-color-surface-border: var(--c-border-subtle);
          --wa-border-radius-m: var(--c-radius-lg);
        }

        #menu {
          gap: 1px;
        }
      `,
    ];
  }
}

export class CraftDropdownItem extends WaDropdownItem {
  static override get styles() {
    return [
      WaDropdownItem.styles,
      css`
        @layer components.dropdown-item {
          :host {
            --wa-font-weight-action: 400;
            --wa-space-s: var(--c-spacing-sm);
            --wa-color-neutral-fill-normal: var(--c-color-neutral-fill-quiet);
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            border-radius: var(--c-radius-sm);
            padding-block: calc(var(--c-spacing, 0.25rem) * 1);
            padding-inline: calc(var(--c-spacing, 0.25rem) * 2.5);
          }
        }
      `,
    ];
  }
}

if (!customElements.get('craft-dropdown')) {
  customElements.define('craft-dropdown', CraftDropdown);
}

if (!customElements.get('craft-dropdown-item')) {
  customElements.define('craft-dropdown-item', CraftDropdownItem);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-dropdown': CraftDropdown;
    'craft-dropdown-item': CraftDropdownItem;
  }
}
