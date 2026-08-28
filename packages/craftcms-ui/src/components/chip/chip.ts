import {property, state} from 'lit/decorators.js';
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
   * How much vertical space the chip takes. `small` adds a small amount of
   * block padding, and `medium` applies a minimum height. `large` is accepted,
   * but has no styles of its own and renders the same as an unset `size`.
   * Leave it unset to size the chip from its content.
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

    if (!this.getAttribute('data-color')) {
      this.setAttribute('data-color', 'white');
    }

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

  renderPrefix() {
    const showStatus =
      this.showStatus || !!this.querySelector('[slot="status"]');

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
    // Read so Lit re-renders when the light DOM changes; see `lightDom`.
    void this.lightDom;

    // query the element Light DOM children for slotted elements
    const renderPrefix =
      !!this.querySelector('[slot="prefix"]') ||
      !!this.querySelector('[slot="icon"]') ||
      !!this.querySelector('[slot="status"]') ||
      !!this.querySelector('[slot="thumbnail"]') ||
      this.showStatus ||
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
