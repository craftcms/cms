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
        wa-popup {
          --wa-z-index-tooltip: var(--c-tooltip-z-index, 1000);
          --wa-tooltip-background-color: var(
            --c-tooltip-bg,
            var(--c-surface-overlay)
          );
          --wa-tooltip-border-color: var(
            --c-tooltip-border,
            var(--c-color-neutral-border-quiet)
          );
          --wa-tooltip-content-color: var(--c-tooltip-fg, currentColor);
          --wa-tooltip-padding: var(
            --c-tooltip-padding,
            calc(4rem / 16) calc(8rem / 16)
          );
          --wa-tooltip-arrow-size: var(--c-tooltip-arrow-size, 5px);
          --wa-tooltip-font-family: inherit;
          --wa-tooltip-font-size: var(
            --c-tooltip-font-size,
            var(--c-text-base)
          );
          --wa-tooltip-font-weight: var(--c-tooltip-font-weight, 400);
          --wa-tooltip-line-height: var(--c-tooltip-line-height, 1.3);
          --wa-tooltip-border-radius: var(
            --c-tooltip-border-radius,
            var(--c-radius-sm)
          );
          font-weight: 400;
          color: var(--c-tooltip-fg, currentColor);
          box-shadow: var(--c-shadow-md);
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
