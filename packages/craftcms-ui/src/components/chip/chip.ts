import {property} from 'lit/decorators.js';
import type {CSSResultGroup, PropertyValues} from 'lit';
import {html, LitElement, nothing} from 'lit';
import styles from './chip.styles.js';
import {classMap} from 'lit/directives/class-map.js';
import {Appearance, type AppearanceValue} from '@src/constants/appearances';
import {Variant, type VariantValue} from '@src/constants/variants';
import type {SizeValue} from '@src/constants/size';
import {ThumbnailLoader} from '@src/utilities/thumbnail-loader';
import {t} from '@src/utilities/translate';
import variantsStyles from '@src/styles/variants.styles.js';
import {
  hasSlotted,
  LightDomController,
} from '@src/controllers/LightDomController';

/**
 * @summary A container that pairs a label with an optional
 * leading prefix — a thumbnail, an icon, or a status dot — and a trailing
 * suffix, usually an action button. Chips represent a single entity in a
 * list: an entry, an asset, a user, a category, etc.
 *
 * The prefix and suffix regions are only rendered when there is content for
 * them, so a chip with nothing but a label renders neither. The suffix is
 * rendered when the `suffix` slot is filled. The prefix is rendered when the
 * `prefix`, `icon`, `thumbnail`, or `status` slot is filled, or when the
 * `icon` attribute or `show-status` is set.
 *
 * Filling the `prefix` slot replaces the entire prefix region. Use it to
 * supply your own leading content; the built-in `thumbnail`, `icon`, and
 * `status` slots are ignored when it is present.
 *
 * On connect the chip stamps `data-color="white"` on itself so it reads as a
 * raised surface by default. Set `data-color` yourself to override it. Because
 * the attribute lands on the chip, an ancestor's `data-color` no longer
 * reaches it — colour the chip directly instead.
 *
 * @slot - The chip's label.
 * @slot prefix - Leading content. Replaces the built-in prefix region, so the
 *   `thumbnail`, `icon`, and `status` slots are ignored when this is filled.
 * @slot thumbnail - A thumbnail image for the prefix. Requires `show-thumb`.
 *   Without it, the slot is not rendered and its content does not appear.
 * @slot icon - Icon content for the prefix, as an alternative to the `icon`
 *   attribute. Only rendered when `icon` is set.
 * @slot status - A status indicator for the prefix. Rendered whenever this
 *   slot is filled, or when `show-status` is set.
 * @slot suffix - Trailing content, shown after the label. Typically an action
 *   button or menu.
 *
 * @csspart chip - The outer chip wrapper.
 * @csspart prefix - The prefix container.
 * @csspart suffix - The suffix container.
 *
 * @cssproperty --c-chip-height - Minimum height of the chip's regions. Unset
 *   by default, so the chip is sized by its padding and content.
 * @cssproperty --c-chip-radius - Corner radius. Defaults to `--c-radius-md`.
 * @cssproperty --c-chip-spacing-inline - Inline (horizontal) padding. Defaults to `0`.
 * @cssproperty --c-chip-spacing-block - Block (vertical) padding. Defaults to `--c-spacing-sm`.
 * @cssproperty --c-chip-shadow - Box shadow. Defaults to `--c-shadow-sm`.
 * @cssproperty --c-chip-border-width - Border width. Defaults to `1px`.
 * @cssproperty --c-chip-border-style - Border style. Defaults to `solid`.
 */
export default class CraftChip extends LitElement {
  static override styles: CSSResultGroup = [variantsStyles, styles];

  /**
   * How much room the chip gives its contents. Each step sets the padding
   * around the label and the size of the thumbnail in the prefix — `small`
   * is tight enough for a chip in a table cell, `large` suits one standing on
   * its own.
   */
  @property() size: SizeValue = 'small';

  /**
   * The semantic color group the chip draws its tokens from. It is combined
   * with `appearance`, which determines how those tokens are applied.
   */
  @property({reflect: true}) variant: VariantValue | null = null;

  /**
   * How prominently the variant color is applied. `plain` removes the chip's
   * border, background, padding, and shadow, leaving the label and prefix
   * inline with the surrounding content.
   */
  @property({reflect: true}) appearance: AppearanceValue =
    Appearance.OutlineFill;

  /**
   * The name of an icon to render in the prefix. This is a shorthand for
   * filling the `icon` slot, and setting it is what causes that slot to be
   * rendered.
   */
  @property() icon: string | null = null;

  /** Renders the `status` slot within the prefix. */
  @property({attribute: 'show-status', type: Boolean})
  showStatus: boolean = false;

  /** Renders the `thumbnail` slot within the prefix. */
  @property({attribute: 'show-thumb', type: Boolean}) showThumb: boolean =
    false;

  /**
   * Renders a checkbox before the prefix, for chips within a multi-select
   * list. The checkbox is named by `select-label`, falling back to a generic
   * "Select".
   */
  @property({type: Boolean}) selectable: boolean = false;

  /**
   * Accessible name for the `selectable` checkbox. Set it to name the entity
   * the chip stands for, so a list of chips does not read as a run of
   * identically labelled checkboxes.
   */
  @property({attribute: 'select-label'}) selectLabel: string | null = null;

  #thumbLoader = new ThumbnailLoader();

  /**
   * Re-renders the chip when its light DOM changes, so the slot checks in
   * `render()` run again. A `<slot>` that isn't rendered can't report a change
   * of its own, and chips are commonly filled in after their first render —
   * `addActionsToChip()` injects an action menu into `[slot="suffix"]` long
   * after the chip mounts.
   */
  #lightDom = new LightDomController(this);

  override connectedCallback(): void {
    super.connectedCallback();

    if (!this.getAttribute('data-color')) {
      this.setAttribute('data-color', 'white');
    }
  }

  protected renderPrefix() {
    const showStatus = this.showStatus || hasSlotted(this, 'status');

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
        ${showStatus
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
    const renderPrefix =
      hasSlotted(this, 'prefix', 'icon', 'status', 'thumbnail') ||
      this.showStatus ||
      this.icon;
    const renderSuffix = hasSlotted(this, 'suffix');

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
          'cp-chip--show-status': this.showStatus,
        })}"
      >
        ${this.selectable
          ? html` <input
              type="checkbox"
              aria-label="${this.selectLabel ?? t('Select')}"
            />`
          : nothing}
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
