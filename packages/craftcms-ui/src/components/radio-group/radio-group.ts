import {LionRadioGroup} from '@lion/ui/radio-group.js';
import {inputStyles} from '@src/styles/form.styles';
import {SsrChoiceGroupMixin} from '@src/mixins/SsrChoiceGroupMixin';
import {css, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {HasLabel} from '@src/mixins/HasLabel';

/**
 * @summary A group of radios sharing one name, for a choice where exactly one
 * option may be selected.
 *
 * The group owns the name and the value, so its `modelValue` is the value of
 * whichever radio is checked. Use `craft-checkbox-group` when more than one
 * option may be chosen.
 *
 * A group rendered by the server adopts the name already on its inputs, so the
 * markup keeps posting the way it did before the component upgraded.
 *
 * Set `buttons` to show the options as a row of joined buttons, like a
 * selection-mode `craft-button-group`. They're still radios, so they're
 * announced as radios and the arrow keys move the selection. An option whose
 * label has no text, such as a labeled `craft-icon`, gets a square button.
 *
 * Set `thumbnails` to show the options as illustrated tiles: each radio's
 * `thumbnail` in a button-like box, with its label below. The tiles are joined
 * in one row, like `buttons`, and come apart into wrapped tiles when the row
 * doesn't fit at `--c-radio-tile-min-size` each.
 *
 * @slot - The `craft-radio`s in the group.
 * @slot label - The group's label.
 * @slot help-text - Guidance shown below the label.
 * @slot feedback - Validation messages for the group as a whole.
 */
export default class CraftRadioGroup extends HasLabel(
  SsrChoiceGroupMixin(LionRadioGroup, 'radio')
) {
  /**
   * Shows the options as illustrated tiles rather than a stacked list, for
   * radios with a `thumbnail`. The tiles are joined in one row, and wrap as
   * separate tiles when the row doesn't fit.
   */
  @property({type: Boolean, reflect: true}) thumbnails = false;

  /**
   * Shows the options as a row of joined buttons rather than a stacked list.
   */
  @property({type: Boolean, reflect: true}) buttons = false;

  /** Whether the tiles have come apart and wrapped, because the row didn't fit. */
  private _wrapped = false;

  private _resizeObserver = new ResizeObserver(() => this._fitTiles());

  override connectedCallback() {
    super.connectedCallback();
    this._resizeObserver.observe(this);
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this._resizeObserver.disconnect();
  }

  /** Registers a radio with the group. Called by Lion as radios connect. */
  override addFormElement(child: unknown, indexToInsertAt?: number) {
    super.addFormElement(
      child as Parameters<LionRadioGroup['addFormElement']>[0],
      indexToInsertAt as number
    );
    this._syncButtons();
    this._fitTiles();
  }

  /** Unregisters a radio from the group. Called by Lion as radios disconnect. */
  override removeFormElement(child: unknown) {
    super.removeFormElement(
      child as Parameters<LionRadioGroup['removeFormElement']>[0]
    );
    this._syncButtons();
    this._fitTiles();
  }

  override updated(changed: PropertyValues) {
    super.updated(changed);
    if (changed.has('buttons') || changed.has('thumbnails')) {
      this._syncButtons();
      this._fitTiles();
    }
  }

  /**
   * Tells each radio whether to draw itself as a button or a tile, and which
   * ones sit at the ends of the row, so only those get rounded outer corners.
   * Wrapped tiles each stand alone, so each is both a start and an end.
   */
  private _syncButtons() {
    const radios = Array.from(this.formElements) as HTMLElement[];

    this.style.setProperty('--_tile-count', String(radios.length));

    radios.forEach((radio, index) => {
      const first = index === 0;
      const last = index === radios.length - 1;

      radio.toggleAttribute('data-button', this.buttons);
      radio.toggleAttribute('data-button-start', this.buttons && first);
      radio.toggleAttribute('data-button-end', this.buttons && last);
      radio.toggleAttribute('data-tile', this.thumbnails);
      radio.toggleAttribute(
        'data-tile-start',
        this.thumbnails && (this._wrapped || first)
      );
      radio.toggleAttribute(
        'data-tile-end',
        this.thumbnails && (this._wrapped || last)
      );
    });
  }

  /**
   * Wraps the tiles once the group is narrower than the joined row would be
   * with every tile at its minimum width. Neither the threshold nor the
   * group's own width depends on the layout it picks, so switching can't make
   * it switch back, even in a container that sizes to its content.
   */
  private _fitTiles() {
    let wrapped = false;

    if (this.thumbnails) {
      const radios = Array.from(this.formElements) as HTMLElement[];
      const rowWidth = radios.reduce(
        (width, radio) => width + parseFloat(getComputedStyle(radio).minWidth),
        -(radios.length - 1)
      );
      wrapped = this.clientWidth < rowWidth;
    }

    if (wrapped !== this._wrapped) {
      this._wrapped = wrapped;
      this.toggleAttribute('data-wrapped', wrapped);
      this._syncButtons();
    }
  }

  static override get styles() {
    return [
      ...super.styles,
      inputStyles,
      css`
        .input-group {
          display: grid;
          gap: var(--c-spacing-xs);
        }

        :host([thumbnails]) {
          inline-size: calc(
            var(--_tile-count, 1) * (var(--c-radio-tile-max-size) - 1px) + 1px
          );
          max-inline-size: 100%;
        }

        :host([thumbnails]) .input-group {
          grid-auto-flow: column;
          grid-auto-columns: minmax(
            var(--c-radio-tile-min-size),
            var(--c-radio-tile-max-size)
          );
          justify-content: start;
          align-items: start;
          gap: 0;
        }

        :host([thumbnails][data-wrapped]) .input-group {
          grid-auto-flow: row;
          grid-template-columns: repeat(
            auto-fill,
            minmax(var(--c-radio-tile-min-size), 1fr)
          );
          gap: var(--c-spacing-md) var(--c-spacing-sm);
        }

        :host([thumbnails]) ::slotted(:not(craft-radio)) {
          display: contents;
        }

        :host([buttons]) .input-group {
          display: flex;
          flex-wrap: wrap;
          width: fit-content;
          gap: 0;
        }

        :host([buttons]) ::slotted(:not(craft-radio)) {
          display: contents;
        }
      `,
    ];
  }
}

if (!customElements.get('craft-radio-group')) {
  customElements.define('craft-radio-group', CraftRadioGroup);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-radio-group': CraftRadioGroup;
  }
}
