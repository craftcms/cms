import WaTooltip from '@awesome.me/webawesome/dist/components/tooltip/tooltip.js';
import {css} from 'lit';

// export default class CraftTooltip extends LitElement {
//   override render() {
//     return html`<slot></slot>`
//   }
// }

export default class CraftTooltip extends WaTooltip {
  static override get styles() {
    return [
      WaTooltip.styles,
      css`
        :host {
          --wa-tooltip-background-color: var(--c-color-black-fill-loud);
          --wa-tooltip-border-color: var(--c-color-black-border-loud);
          --wa-tooltip-content-color: var(--c-color-black-on-loud);
          --wa-tooltip-padding: var(
            --c-tooltip-padding,
            calc(4rem / 16) calc(8rem / 16)
          );
          --wa-tooltip-arrow-size: var(--c-tooltip-arrow-size, 5px);
          --wa-tooltip-font-family: inherit;
          --wa-tooltip-font-size: var(--c-text-base);
          --wa-tooltip-font-weight: 400;
          --wa-tooltip-line-height: 1.3;
          --wa-tooltip-border-radius: var(--c-radius-sm);
        }

        &::part(base) {
          box-shadow: var(--c-shadow-md);
        }

        .body {
          color: var(--wa-tooltip-content-color);
          font-weight: var(--wa-tooltip-font-weight);
        }
      `,
    ];
  }
}

/**
 * @TODO rename this once I figure out what to do with the existing `craft-tooltip`
 */
if (!customElements.get('c-tooltip')) {
  customElements.define('c-tooltip', CraftTooltip);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-tooltip': CraftTooltip;
  }
}
