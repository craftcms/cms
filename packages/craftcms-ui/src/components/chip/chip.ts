import {property, state} from 'lit/decorators.js';
import type {CSSResultGroup, PropertyValues} from 'lit';
import {html, LitElement, nothing} from 'lit';
import styles from './chip.styles.js';
import {classMap} from 'lit/directives/class-map.js';
import {Appearance, type AppearanceValue} from '@src/constants/appearances';
import {Variant, type VariantValue} from '@src/constants/variants';
import type {SizeValue} from '@src/constants/size';
import {ThumbnailLoader} from '@src/utilities/thumbnail-loader';

/**
 * @summary A compact, inline element that pairs a label with an optional
 * prefix (icon, status indicator, thumbnail, …) and suffix (e.g. an action
 * button). Used for element chips, status chips, and similar UI.
 *
 * The prefix is only rendered when the `prefix` or `icon` slot is filled or the
 * `icon` attribute is set; the suffix is only rendered when the `suffix` slot is
 * filled.
 *
 * @slot - The chip's body/label content.
 * @slot prefix - Content shown before the body, e.g. a status indicator or
 *   thumbnail. Takes precedence over the `icon` slot/attribute.
 * @slot icon - Custom icon content shown in the prefix, as an alternative to the
 *   `icon` attribute.
 * @slot suffix - Content shown after the body, e.g. an action button.
 *
 * @csspart chip - The outer chip wrapper.
 * @csspart prefix - The prefix container.
 * @csspart suffix - The suffix container.
 *
 * @cssproperty --c-chip-height - Minimum height of the chip. Defaults to `--c-size-control-sm`.
 * @cssproperty --c-chip-radius - Corner radius. Defaults to `--c-radius-md`.
 * @cssproperty --c-chip-spacing-inline - Inline (horizontal) padding. Defaults to `0`.
 * @cssproperty --c-chip-spacing-block - Block (vertical) padding. Defaults to `--c-spacing-sm`.
 * @cssproperty --c-chip-shadow - Box shadow. Defaults to `--c-shadow-sm`.
 * @cssproperty --c-chip-border-width - Border width. Defaults to `1px`.
 * @cssproperty --c-chip-border-style - Border style. Defaults to `solid`.
 */
export default class CraftChip extends LitElement {
  static override styles: CSSResultGroup = [styles];

  /** Size of the chip. */
  @property() size: SizeValue | '' = '';

  /** Variant of the chip. `plain` will render with no border or padding */
  @property({reflect: true}) variant: VariantValue = Variant.Neutral;

  /** Appearance of the chip. Defaults to `outline-fill`. */
  @property({reflect: true}) appearance: AppearanceValue =
    Appearance.OutlineFill;

  /** Shortcut for adding an icon as the prefix */
  @property() icon: string | null = null;

  @property({attribute: 'show-indicators', type: Boolean})
  showIndicators: boolean = false;
  @property({attribute: 'show-status', type: Boolean})
  showStatus: boolean = false;
  @property({attribute: 'show-thumb', type: Boolean}) showThumb: boolean =
    false;
  /** Whether the chip offers a selection checkbox. */
  @property({type: Boolean}) selectable: boolean = false;

  /** Whether the chip is selected. Only meaningful alongside `selectable`. */
  @property({type: Boolean, reflect: true}) selected: boolean = false;

  /** Accessible name for the selection checkbox. */
  @property({attribute: 'select-label'}) selectLabel: string | null = null;

  /**
   * The modifier state of the click that preceded `change`, captured because
   * `change` itself doesn't carry it and range selection needs it.
   */
  #selectShiftKey = false;

  #thumbLoader = new ThumbnailLoader();

  /**
   * Bumped whenever the light DOM changes, so the slot checks in `render()`
   * run again.
   *
   * Those checks are plain `querySelector()` calls rather than `slotchange`
   * listeners: a `<slot>` that isn't rendered can't report a change, so the
   * first thing slotted into an empty prefix or suffix would never appear.
   * Chips are commonly filled in after their first render — `addActionsToChip()`
   * injects an action menu into `[slot="suffix"]` long after the chip mounts.
   */
  @state() private lightDom = 0;

  #observer = new MutationObserver(() => this.lightDom++);

  override connectedCallback(): void {
    super.connectedCallback();
    // Attributes included: content moves between slots by having its `slot`
    // attribute set, not only by being added or removed.
    this.#observer.observe(this, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['slot'],
    });
  }

  override disconnectedCallback(): void {
    this.#observer.disconnect();
    super.disconnectedCallback();
  }

  #onSelectClick(event: MouseEvent): void {
    this.#selectShiftKey = event.shiftKey;

    // Ticking the box is the checkbox's business, not a click on the chip body;
    // stop it here rather than making every host filter it back out.
    event.stopPropagation();
  }

  #onSelectChange(event: Event): void {
    const {checked} = event.target as HTMLInputElement;

    this.selected = checked;
    this.dispatchEvent(
      new CustomEvent('selected-change', {
        detail: {selected: checked, shiftKey: this.#selectShiftKey},
        bubbles: true,
        composed: true,
      })
    );
  }

  renderSelect() {
    return html`<input
      type="checkbox"
      class="cp-chip__select"
      part="select"
      .checked=${this.selected}
      aria-label=${this.selectLabel ?? nothing}
      @click=${this.#onSelectClick}
      @change=${this.#onSelectChange}
    />`;
  }

  renderPrefix() {
    return html`<div class="cp-chip__prefix" part="prefix">
      <slot name="prefix">
        ${this.showThumb
          ? html`<slot class="cp-chip__thumbnail" name="thumbnail"></slot>`
          : nothing}
        ${this.icon
          ? html`<slot class="cp-chip__icon" name="icon"
              ><craft-icon name="${this.icon}"></craft-icon
            ></slot>`
          : nothing}
        ${this.showStatus
          ? html`<slot class="cp-chip__status" name="status"></slot>`
          : nothing}
      </slot>
    </div>`;
  }

  protected override firstUpdated(_changedProperties: PropertyValues) {
    super.firstUpdated(_changedProperties);
    this.#thumbLoader.load(this);
  }

  override render() {
    // Read so Lit re-renders when the light DOM changes; see `lightDom`.
    void this.lightDom;

    // query the element Light DOM children for slotted elements
    const renderPrefix =
      !!this.querySelector('[slot="prefix"]') ||
      !!this.querySelector('[slot="icon"]') ||
      !!this.querySelector('[slot="thumbnail"]') ||
      !!this.querySelector('[slot="indicator"]') ||
      !!this.querySelector('[slot="status"]') ||
      this.icon;
    const renderSuffix = !!this.querySelector('[slot="suffix"]');

    return html`
      <div
        part="chip"
        class="${classMap({
          'cp-chip': true,
          'cp-chip--small': this.size === 'small',
          'cp-chip--medium': this.size === 'medium',
          'cp-chip--large': this.size === 'large',
          'cp-chip--plain': this.appearance === Appearance.Plain,
          'cp-chip--selectable': this.selectable,
          'cp-chip--show-thumb': this.showThumb,
          'cp-chip--show-indicators': this.showIndicators,
          'cp-chip--show-status': this.showStatus,
        })}"
      >
        ${this.selectable ? this.renderSelect() : nothing}
        ${renderPrefix ? this.renderPrefix() : nothing}
        <slot class="cp-chip__body"></slot>
        ${renderSuffix
          ? html`<slot
              name="suffix"
              class="cp-chip__suffix"
              part="suffix"
            ></slot>`
          : nothing}
      </div>
    `;
  }
}

if (!customElements.get('craft-chip')) {
  customElements.define('craft-chip', CraftChip);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-chip': CraftChip;
  }
}
