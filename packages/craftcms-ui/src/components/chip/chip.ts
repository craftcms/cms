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
import {LightDomController} from '@src/controllers/LightDomController';

/** Which of the chip's parts have something to show. */
type FilledParts = Record<
  'prefix' | 'thumbnail' | 'icon' | 'status' | 'body' | 'suffix',
  boolean
>;

/**
 * @summary A container that pairs a label with an optional
 * leading prefix — a thumbnail, an icon, or a status dot — and a trailing
 * suffix, usually an action button. Chips represent a single entity in a
 * list: an entry, an asset, a user, a category, etc.
 *
 * The prefix and suffix regions are only rendered when there is content for
 * them, so a chip with nothing but a label renders neither. The suffix is
 * rendered when the `suffix` slot is filled with an element that has content
 * of its own, so an empty placeholder waiting for actions takes up no space.
 * The prefix is rendered when the
 * `prefix`, `icon`, `thumbnail`, or `status` slot is filled, or when the
 * `icon` attribute or `show-status` is set.
 *
 * The `prefix` slot comes first in the prefix region, before the built-in
 * `thumbnail`, `icon`, and `status` slots, so custom leading content (a
 * checkbox or a badge, say) doesn't displace them.
 *
 * On connect the chip stamps `data-color="white"` on itself so it reads as a
 * raised surface by default, filled with `--c-surface-raised` so it follows
 * the theme. Set `data-color` yourself to override it. Because the attribute
 * lands on the chip, an ancestor's `data-color` no longer reaches it — color
 * the chip directly instead.
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
 * @cssproperty --c-chip-shadow - Box shadow. Defaults to `--c-shadow-sm`.
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
   */
  @property() size: SizeValue | 'auto' = 'small';

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

  override connectedCallback(): void {
    super.connectedCallback();

    if (!this.getAttribute('data-color')) {
      this.setAttribute('data-color', 'white');
    }
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
      aria-label=${this.selectLabel ?? t('Select')}
      @click=${this.#onSelectClick}
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
      ${filled.status
        ? html`<slot
            class="cp-chip__status"
            name="status"
            part="status"
          ></slot>`
        : nothing}
    </div>`;
  }

  protected override firstUpdated(_changedProperties: PropertyValues) {
    super.firstUpdated(_changedProperties);
    this.#thumbLoader.load(this);
  }

  override render() {
    const filled = this.#filledParts();
    const renderPrefix =
      filled.prefix || filled.thumbnail || filled.icon || filled.status;

    return html`
      <div
        part="chip"
        class="${classMap({
          'cp-chip': true,
          'cp-chip--small': this.size === 'small',
          'cp-chip--medium': this.size === 'medium',
          'cp-chip--large': this.size === 'large',
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
        ${filled.body
          ? html`<slot class="cp-chip__body" part="body"></slot>`
          : nothing}
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
