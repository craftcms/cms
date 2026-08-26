import { html, LitElement, nothing } from "lit";
import { property } from "lit/decorators.js";
import type { CSSResultGroup, PropertyValues } from "lit";
import styles from "./badge.styles.js";
import { Color, type ColorValue } from "@src/constants/colors";
import "../indicator/indicator.js";
import { classMap } from "lit/directives/class-map.js";
import { Size, type SizeValue } from "@src/constants/size";

/**
 * @summary A colored status pill: a `<craft-indicator>` dot (by default)
 * followed by a label. `fill` sets the badge color from the shared `Color`
 * palette — the surface renders in the quiet tone of that color and the
 * indicator in the loud tone.
 *
 * @slot - The label content shown after the indicator.
 * @slot prefix - The leading content; defaults to a `<craft-indicator>` whose
 * fill is derived from `fill`.
 * @slot suffix - The trailing content.
 *
 * @csspart badge - The badge wrapper.
 * @csspart prefix - The leading slot, rendered before the label.
 * @csspart indicator - The default indicator shown in the prefix slot.
 * @csspart suffix - The trailing slot, rendered after the label.
 *
 * @since 1.0
 */
export default class CraftBadge extends LitElement {
  static override styles: CSSResultGroup = [styles];

  /** The badge color — a color value from `Color` (e.g. `red`, `emerald`). */
  @property({ reflect: true }) fill: ColorValue = Color.Gray;

  @property({ attribute: "no-prefix", type: Boolean }) noPrefix: boolean =
    false;

  @property() size: SizeValue = Size.Medium;

  /** The resolved color value used for the badge fill. */
  private getFill(): ColorValue {
    return this.fill;
  }

  protected override willUpdate(changed: PropertyValues<this>): void {
    // Set the colorable context from `fill` so the badge's own surface/border/
    // text colors (which read --c-color-*) reflect the chosen color.
    if (changed.has("fill")) {
      this.dataset.color = this.getFill();
    }
  }

  override render() {
    console.log({ noPrefix: this.noPrefix });
    return html`
      <span
        part="badge"
        class="${classMap({
          badge: true,
          "badge--small": this.size === Size.Small,
          "badge--large": this.size === Size.Large,
        })}"
      >
        <span class="badge__prefix">
          ${this.noPrefix
            ? nothing
            : html` <slot name="prefix" part="prefix">
                <craft-indicator
                  part="indicator"
                  fill="${this.getFill()}"
                ></craft-indicator>
              </slot>`}
        </span>
        <slot></slot>
        <span class="badge__suffix">
          <slot name="suffix" part="suffix"></slot>
        </span>
      </span>
    `;
  }
}

if (!customElements.get("craft-badge")) {
  customElements.define("craft-badge", CraftBadge);
}

declare global {
  interface HTMLElementTagNameMap {
    "craft-badge": CraftBadge;
  }
}
