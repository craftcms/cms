import { type CSSResultGroup, html, LitElement, nothing } from "lit";
import { property, state } from "lit/decorators.js";
import styles from "./callout.styles.js";
import "../icon/icon.js";
import { Appearance, type AppearanceValue } from "@src/constants/appearances";
import { Variant, type VariantValue } from "@src/constants/variants";
import variantsStyles from "@src/styles/variants.styles.js";
import { classMap } from "lit/directives/class-map.js";

export default class CraftCallout extends LitElement {
  static override styles: CSSResultGroup = [variantsStyles, styles];

  /** Variant style of the callout */
  @property({ reflect: true }) variant: VariantValue = Variant.Neutral;

  /**
   * Appearance style of the callout
   * @TODO maybe drop "outline"?
   */
  @property({ reflect: true }) appearance: AppearanceValue =
    Appearance.OutlineFill;

  /** Title of the callout */
  @property() override title: string = "";

  /** Icon to display in the callout */
  @property() icon: string | null = null;

  @property({ type: Boolean, attribute: "hide-icon" }) hideIcon: boolean =
    false;

  @property({ reflect: true })
  rounded: "all" | "start" | "end" | "none" = "all";

  @property({ reflect: true, type: Boolean })
  inline: boolean = false;

  /**
   * The icon actually rendered: the one that was set, or the variant's
   * default. Held in state so the icon and the layout around it are decided
   * together — a callout with no icon to show collapses the icon column
   * rather than leaving a gap where one would have gone.
   */
  @state() private resolvedIcon: string | null = null;

  protected override willUpdate(): void {
    this.resolvedIcon = this.icon ?? this.getDefaultIcon();
  }

  getDefaultIcon() {
    switch (this.variant) {
      case Variant.Info:
        return "lightbulb";
      case Variant.Success:
        return "circle-check";
      case Variant.Warning:
        return "circle-exclamation";
      case Variant.Danger:
        return "triangle-exclamation";
      default:
        return null;
    }
  }

  protected override render(): unknown {
    const hasTitle = this.title || !!this.querySelector('[slot="title"]');
    // A slotted icon counts even when there's no name to resolve — it's the
    // consumer bringing their own artwork.
    const hasIcon =
      !this.hideIcon &&
      (!!this.resolvedIcon || !!this.querySelector('[slot="icon"]'));

    return html`
      <div
        data-variant="${this.variant}"
        class="${classMap({
          callout: true,
          "callout--hide-icon": !hasIcon,
          "callout--title": hasTitle,
        })}"
      >
        ${hasIcon
          ? html`<slot name="icon" class="callout__icon">
              ${this.resolvedIcon
                ? html`<craft-icon
                    name="${this.resolvedIcon}"
                    style="font-size: 0.9em"
                  ></craft-icon>`
                : nothing}
            </slot>`
          : nothing}
        ${hasTitle
          ? html`<div class="callout__title">
              <slot name="title">${this.title}</slot>
            </div>`
          : nothing}

        <div class="callout__description">
          <slot></slot>
        </div>

        <div class="callout__action"><slot name="action"></slot></div>
      </div>
    `;
  }
}

if (!customElements.get("craft-callout")) {
  customElements.define("craft-callout", CraftCallout);
}
