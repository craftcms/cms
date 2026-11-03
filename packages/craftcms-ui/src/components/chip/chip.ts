import {property} from 'lit/decorators.js';
import type {CSSResultGroup, PropertyValues} from 'lit';
import {html, LitElement, nothing} from 'lit';
import styles from './chip.styles.js';
import {classMap} from 'lit/directives/class-map.js';
import {Appearance, type AppearanceValue} from '@src/constants/appearances';
import {Variant, type VariantValue} from '@src/constants/variants';
import {ThumbnailLoader} from '@src/utilities/thumbnail-loader';
import {t} from '@src/utilities/translate';
import variantsStyles from '@src/styles/variants.styles.js';
import {LightDomController} from '@src/controllers/LightDomController';
import {Size, type SizeValue} from '@src/constants/size';

/**
 * How big a chip's thumbnail is: `sm` suits a chip in a line of text or a
 * table cell, `lg` matches a card's thumbnail, and `xl` and `2xl` go larger
 * still.
 */
export const ChipThumbSize = {
  Sm: 'sm',
  Md: 'md',
  Lg: 'lg',
  Xl: 'xl',
  '2xl': '2xl',
} as const;

export const chipThumbSizes = Object.values(ChipThumbSize);

export type ChipThumbSizeValue =
  (typeof ChipThumbSize)[keyof typeof ChipThumbSize];

/** The chip size that suits each thumbnail size, when none is set. */
const sizeForThumbSize: Record<ChipThumbSizeValue, SizeValue> = {
  sm: Size.Small,
  md: Size.Medium,
  lg: Size.Large,
  xl: Size.Large,
  '2xl': Size.Large,
};

/**
 * Which side of the label the prefix sits on, as a logical side so it follows
 * the writing direction.
 */
export const ChipPrefixPlacement = {
  InlineStart: 'inline-start',
  BlockStart: 'block-start',
} as const;

export type ChipPrefixPlacementValue =
  (typeof ChipPrefixPlacement)[keyof typeof ChipPrefixPlacement];

/** Which of the chip's parts have something to show. */
type FilledParts = Record<
  'prefix' | 'thumbnail' | 'icon' | 'status' | 'body' | 'suffix',
  boolean
>;

/**
 * @summary A container that pairs a label with an optional
 * leading prefix — a thumbnail or an icon — a status dot beside the label,
 * and a trailing suffix, usually an action button. Chips represent a single
 * entity in a list: an entry, an asset, a user, a category, etc.
 *
 * Each slot is only rendered when it has content, so an empty part takes up
 * no space. An empty `div` doesn't count as content, so a placeholder
 * waiting to be filled — an action menu, say — doesn't either. The
 * `thumbnail` slot also needs `show-thumb`, and the `icon` attribute fills
 * the `icon` slot on its own.
 *
 * The `prefix` slot comes first in the prefix region, before the built-in
 * `thumbnail` and `icon` slots, so custom leading content (a badge, say)
 * doesn't displace them. The status sits with the label instead, closer to
 * it than the other parts are to each other, since it describes the label.
 *
 * Without a `data-color` the chip has no fill of its own: it takes its
 * border and text from the colors around it, so in a colored card it takes
 * the card's color, and elsewhere the neutral ones. Set `data-color` for a
 * chip in a color of its own, such as `white` for a raised surface.
 *
 * Any `<craft-button>` placed in the chip is given `inherit`, so its neutral
 * variants pick up the chip's color. This includes buttons added after the
 * chip mounts and ones nested in other slotted content, such as an action
 * menu's invoker.
 *
 * @slot - The chip's label.
 * @slot prefix - Leading content, shown before the thumbnail, icon, and
 *   status.
 * @slot thumbnail - A thumbnail image for the prefix. Requires `show-thumb`.
 *   Without it, the slot is not rendered and its content does not appear.
 * @slot icon - Icon content for the prefix, as an alternative to the `icon`
 *   attribute. Only rendered when `icon` is set.
 * @slot status - A status indicator for the prefix. Rendered whenever this
 *   slot is filled, or when `show-status` is set.
 * @slot suffix - Trailing content, shown after the label. Typically an action
 *   button or menu.
 *
 * @event craft-selection-change - The selection checkbox was toggled.
 *   `detail.selected` is the new state, and `detail.shiftKey` whether Shift
 *   was held on the click before it, for range selection.
 *
 * @csspart chip - The outer chip wrapper.
 * @csspart prefix - The prefix container.
 * @csspart suffix - The suffix container.
 *
 * @cssproperty --c-chip-height - Minimum height of the chip's regions. Unset
 *   by default, so the chip is sized by its padding and content.
 * @cssproperty --c-chip-radius - Corner radius. Defaults to `--c-radius-md`.
 * @cssproperty --c-chip-gap - Space between the chip's parts, the same at
 *   every size. Defaults to `--c-spacing-md`.
 * @cssproperty --c-chip-spacing-inline - Inline (horizontal) padding. Defaults to `0`.
 * @cssproperty --c-chip-spacing-block - Block (vertical) padding. Defaults to `--c-spacing-sm`.
 * @cssproperty --c-chip-fill - Background color. Defaults to
 *   `--c-surface-raised`. A `variant` fills with its own color instead.
 * @cssproperty --c-chip-text - Label color. Defaults to `--c-text-default`.
 * @cssproperty --c-chip-border-color - Border color. Defaults to
 *   `--c-color-neutral-border-quiet`.
 * @cssproperty --c-chip-shadow - Box shadow. Defaults to the theme's
 *   `--c-shadow-xs`.
 * @cssproperty --c-chip-border-width - Border width. Defaults to `1px`.
 * @cssproperty --c-chip-border-style - Border style. Defaults to `solid`.
 *
 * Label links aren't underlined until hovered, whether slotted straight into
 * the chip or nested in a `craft-truncate`, which picks up the chip's
 * `--c-truncate-link-decoration` and `--c-truncate-link-hover-decoration`.
 */
export default class CraftChip extends LitElement {
  static override styles: CSSResultGroup = [variantsStyles, styles];

  /**
   * How much room the chip gives its contents. Each step sets the padding
   * around the label and the size of the thumbnail in the prefix — `small`
   * is tight enough for a chip in a table cell, `large` suits one standing on
   * its own. `small` is also as tall as a small suffix button, so adding one
   * doesn't change its height; `auto` has the same padding without that
   * minimum, for a chip as compact as its content allows.
   *
   * Left unset, it follows `thumb-size`: `small` for `sm`, `medium` for `md`,
   * and `large` from `lg` up. Without either, it's `small`.
   */
  @property() size: SizeValue | 'auto' | null = null;

  /**
   * The size of the thumbnail in the prefix, overriding the one `size` gives
   * it: from `sm`, which suits a table cell, through `lg`, a card's
   * thumbnail, to `xl` and `2xl`. Sets the chip's `size` too, unless that's
   * set itself.
   */
  @property({attribute: 'thumb-size', reflect: true})
  thumbSize: ChipThumbSizeValue | null = null;

  /** The size the chip renders at: its own, or the one its thumbnail suits. */
  get #size(): SizeValue | 'auto' {
    return (
      this.size ??
      (this.thumbSize ? sizeForThumbSize[this.thumbSize] : null) ??
      Size.Small
    );
  }

  /**
   * Which side of the label the prefix (thumbnail or icon) sits on.
   * `block-start` stacks it above the label, centered, for a tile in a grid;
   * the checkbox and suffix then share a row along the top.
   */
  @property({attribute: 'prefix-placement', reflect: true})
  prefixPlacement: ChipPrefixPlacementValue = ChipPrefixPlacement.InlineStart;

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
   * How the prefix and suffix line up against the label on the cross axis.
   * `center` suits a single-line label. With several lines in the label,
   * `start` keeps the prefix and suffix against the first line and `end`
   * against the last.
   */
  @property({attribute: 'align-items'}) alignItems: 'start' | 'center' | 'end' =
    'center';

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
   * Stretches the chip to fill its parent, rather than sizing it to its
   * content. Either way, a label too long for the parent truncates.
   */
  @property({type: Boolean, reflect: true, attribute: 'full-width'})
  fullWidth: boolean = false;

  /** Whether the chip is selected. Only meaningful alongside `selectable`. */
  @property({type: Boolean, reflect: true}) selected: boolean = false;

  /**
   * Accessible name for the `selectable` checkbox. Set it to name the entity
   * the chip stands for, so a list of chips does not read as a run of
   * identically labeled checkboxes.
   */
  @property({attribute: 'select-label'}) selectLabel: string | null = null;

  /**
   * Disables the `selectable` checkbox, e.g. while the list it's in is busy,
   * or for an entity that can't be selected.
   */
  @property({attribute: 'select-disabled', type: Boolean})
  selectDisabled: boolean = false;

  /**
   * The modifier state of the click that preceded `change`, captured because
   * `change` itself doesn't carry it and range selection needs it.
   */
  #selectShiftKey = false;

  #thumbLoader = new ThumbnailLoader();

  /**
   * Re-renders the chip when its light DOM changes, so the slot checks in
   * `render()` run again. A `<slot>` that isn't rendered can't report a change
   * of its own, and chips are commonly filled in after their first render —
   * `addActionsToChip()` injects an action menu into `[slot="suffix"]` long
   * after the chip mounts.
   *
   * The same changes are when a button can arrive, so each one is also the
   * cue to have the chip's buttons inherit its palette.
   */
  #lightDom = new LightDomController(this, {
    characterData: true,
    onChange: () => this.#inheritButtons(),
  });

  /**
   * Sets `inherit` on the chip's buttons so they take its palette rather than
   * the neutral one, which would stand out against a colored chip. Buttons
   * inside a nested chip are left to that chip.
   */
  #inheritButtons(): void {
    for (const button of this.querySelectorAll('craft-button:not([inherit])')) {
      if (button.closest('craft-chip') === this) {
        button.toggleAttribute('inherit', true);
      }
    }
  }

  #onSelectClick(event: MouseEvent): void {
    this.#selectShiftKey = event.shiftKey;

    // Ticking the box is the checkbox's business, not a click on the chip body;
    // stop it here rather than making every host filter it back out.
    event.stopPropagation();
  }

  /**
   * Presses on the checkbox stay with it too: a host that selects on mousedown
   * would toggle the item, then the checkbox's own click would toggle it back.
   */
  #onSelectPress(event: MouseEvent): void {
    event.stopPropagation();
  }

  #onSelectChange(event: Event): void {
    const {checked} = event.target as HTMLInputElement;

    this.selected = checked;
    this.dispatchEvent(
      new CustomEvent('craft-selection-change', {
        detail: {selected: checked, shiftKey: this.#selectShiftKey},
        bubbles: true,
        composed: true,
      })
    );
  }

  protected renderSelect() {
    return html`<input
      type="checkbox"
      class="cp-chip__select"
      part="select"
      .checked=${this.selected}
      ?disabled=${this.selectDisabled}
      aria-label=${this.selectLabel ?? t('Select')}
      @click=${this.#onSelectClick}
      @mousedown=${this.#onSelectPress}
      @mouseup=${this.#onSelectPress}
      @change=${this.#onSelectChange}
    />`;
  }

  /**
   * Whether a slot has anything to show. A part rendered empty would still
   * take a gap, so only filled slots are rendered. Templates often render a
   * wrapper div into a slot whether or not they have anything to put in it, so
   * an empty div doesn't count; any other element does, since an image or a
   * custom element can draw something without children. `''` is the default
   * slot, which text fills too.
   */
  #hasContent(name: string): boolean {
    return Array.from(this.childNodes).some((node) => {
      if (node instanceof Element) {
        return (
          node.slot === name &&
          !(
            node.localName === 'div' &&
            node.childElementCount === 0 &&
            node.textContent.trim() === ''
          )
        );
      }

      return (
        name === '' &&
        node.nodeType === Node.TEXT_NODE &&
        node.textContent!.trim() !== ''
      );
    });
  }

  #filledParts(): FilledParts {
    return {
      prefix: this.#hasContent('prefix'),
      thumbnail: this.showThumb && this.#hasContent('thumbnail'),
      icon: !!this.icon || this.#hasContent('icon'),
      status: this.#hasContent('status'),
      body: this.#hasContent(''),
      suffix: this.#hasContent('suffix'),
    };
  }

  /**
   * Which part of the chip comes first, so the spacing before it can suit it.
   */
  #leadingPart(
    filled: FilledParts
  ): 'select' | 'prefix' | 'thumbnail' | 'icon' | 'status' | 'body' {
    if (this.selectable) {
      return 'select';
    }

    for (const part of ['prefix', 'thumbnail', 'icon', 'status'] as const) {
      if (filled[part]) {
        return part;
      }
    }

    return 'body';
  }

  protected renderPrefix(filled: FilledParts) {
    return html`<div class="cp-chip__prefix">
      ${filled.prefix
        ? html`<slot name="prefix" part="prefix"></slot>`
        : nothing}
      ${filled.thumbnail
        ? html`<slot
            class="cp-chip__thumbnail"
            name="thumbnail"
            part="thumbnail"
          ></slot>`
        : nothing}
      ${filled.icon
        ? html`<slot class="cp-chip__icon" name="icon" part="icon"
            >${this.icon
              ? html`<craft-icon name="${this.icon}"></craft-icon>`
              : nothing}</slot
          >`
        : nothing}
    </div>`;
  }

  /**
   * The label, with the status beside it rather than in the prefix: a status
   * describes the label, so the two sit closer together than the other parts.
   */
  protected renderMain(filled: FilledParts) {
    return html`<div class="cp-chip__main">
      ${filled.status
        ? html`<slot
            class="cp-chip__status"
            name="status"
            part="status"
          ></slot>`
        : nothing}
      ${filled.body
        ? html`<slot class="cp-chip__body" part="body"></slot>`
        : nothing}
    </div>`;
  }

  override connectedCallback() {
    super.connectedCallback();

    if (this.hasUpdated) {
      this.#thumbLoader = new ThumbnailLoader();
      this.#thumbLoader.load(this);
    }
  }

  override disconnectedCallback() {
    this.#thumbLoader.destroy();
    super.disconnectedCallback();
  }

  protected override firstUpdated(_changedProperties: PropertyValues) {
    super.firstUpdated(_changedProperties);
    this.#thumbLoader.load(this);
  }

  override render() {
    const filled = this.#filledParts();
    const renderPrefix = filled.prefix || filled.thumbnail || filled.icon;

    return html`
      <div
        part="chip"
        class="${classMap({
          'cp-chip': true,
          'cp-chip--small': this.#size === 'small',
          'cp-chip--medium': this.#size === 'medium',
          'cp-chip--large': this.#size === 'large',
          [`cp-chip--thumb-${this.thumbSize}`]: this.thumbSize !== null,
          'cp-chip--stacked':
            this.prefixPlacement === ChipPrefixPlacement.BlockStart,
          'cp-chip--has-header':
            this.prefixPlacement === ChipPrefixPlacement.BlockStart &&
            (this.selectable || filled.suffix),
          'cp-chip--align-start': this.alignItems === 'start',
          'cp-chip--align-end': this.alignItems === 'end',
          'cp-chip--plain': this.appearance === Appearance.Plain,
          'cp-chip--selectable': this.selectable,
          'cp-chip--show-thumb': this.showThumb,
          'cp-chip--show-status': this.showStatus,
          [`cp-chip--leads-with-${this.#leadingPart(filled)}`]: true,
        })}"
      >
        ${this.selectable ? this.renderSelect() : nothing}
        ${renderPrefix ? this.renderPrefix(filled) : nothing}
        ${filled.status || filled.body ? this.renderMain(filled) : nothing}
        ${filled.suffix
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
