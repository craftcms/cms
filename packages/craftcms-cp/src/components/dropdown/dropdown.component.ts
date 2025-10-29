// import { property } from "lit/decorators.js";
// import { html, LitElement } from "lit";
// import { query } from "lit/decorators.js";
// import styles from "./dropdown.styles.js";
// import type { CSSResultGroup } from "lit";
// import "@awesome.me/webawesome/dist/components/popup/popup.js";
// import { classMap } from "lit/directives/class-map.js";
//
// /**
//  * @summary Short summary of the component's intended use.
//  *
//  * @event craft-event-name - Emitted as an example.
//  *
//  * @slot - The default slot.
//  * @slot example - An example slot.
//  *
//  * @csspart base - The component's base wrapper.
//  *
//  * @cssproperty --example - An example CSS custom property.
//  */
// export default class CraftDropdown extends LitElement {
//   static override styles: CSSResultGroup = [styles];
//
//   /**
//    * Indicates whether or not the dropdown is open. You can toggle this attribute to show and hide the dropdown, or you
//    * can use the `show()` and `hide()` methods and this attribute will reflect the dropdown's open state.
//    */
//   @property({ type: Boolean, reflect: true }) open = false;
//
//   /** The dropdown's size. */
//   @property({ reflect: true }) size: "small" | "medium" | "large" = "medium";
//
//   /**
//    * The placement of the dropdown menu in reference to the trigger. The menu will shift to a more optimal location if
//    * the preferred placement doesn't have enough room.
//    */
//   @property({ reflect: true }) placement:
//     | "top"
//     | "top-start"
//     | "top-end"
//     | "bottom"
//     | "bottom-start"
//     | "bottom-end"
//     | "right"
//     | "right-start"
//     | "right-end"
//     | "left"
//     | "left-start"
//     | "left-end" = "bottom-start";
//
//   /** The distance of the dropdown menu from its trigger. */
//   @property({ type: Number }) distance = 0;
//
//   /** The offset of the dropdown menu along its trigger. */
//   @property({ type: Number }) skidding = 0;
//
//   @query(".dropdown__invoker") invoker: HTMLSlotElement | undefined;
//
//   handleInvokerClick() {
//     if (this.open) {
//       this.hide();
//     } else {
//       this.show();
//       this.focusOnTrigger();
//     }
//   }
//
//   focusOnTrigger() {
//     const invoker = this.invoker?.assignedElements({ flatten: true })[0] as
//       | HTMLElement
//       | undefined;
//     if (typeof invoker?.focus === "function") {
//       invoker.focus();
//     }
//   }
//
//   async show() {
//     if (this.open) {
//       return undefined;
//     }
//
//     this.open = true;
//   }
//
//   async hide() {
//     if (!this.open) {
//       return undefined;
//     }
//
//     this.open = false;
//   }
//
//   handlePanelSlotChange() {
//     console.log("changed");
//   }
//
//   override render() {
//     let active = this.open;
//
//     return html`
//       <wa-popup
//         placement="${this.placement}"
//         distace="${this.distance}"
//         skidding="${this.skidding}"
//         ?active="${active}"
//         flip
//         flip-fallback-strategy="best-fit"
//         shift
//         shift-padding="8"
//       >
//         <slot
//           name="invoker"
//           slot="anchor"
//           @click="${this.handleInvokerClick}"
//         ></slot>
//
//         <div
//           id="panel"
//           part="panel"
//           aria-hidden=${this.open ? "false" : "true"}
//           aria-labelledby="dropdown"
//         >
//           <slot
//             class="panel"
//             @slotchange="${this.handlePanelSlotChange}"
//           ></slot>
//         </div>
//       </wa-popup>
//     `;
//   }
// }

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
          --wa-color-surface-raised: var(--c-bg-raised);
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
            --wa-color-neutral-fill-normal: var(--c-color-neutral-bg-subtle);
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
