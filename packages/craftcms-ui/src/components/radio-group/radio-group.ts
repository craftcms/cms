import {inputStyles} from '@src/styles/form.styles';
import {ChoiceGroup} from '@src/mixins/ChoiceGroup';
import {formControlStyles} from '@src/mixins/FormControl';
import {css, html, LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';

export type RadioGroupAppearance =
  | 'buttons'
  | 'button-group'
  | 'cards'
  | 'card-group';

/**
 * @summary A group of radios sharing one name, for a choice where exactly one
 * option may be selected.
 *
 * The group owns the name, and its `value` is the value of whichever radio
 * is checked, or empty when none is. Use `craft-checkbox-group` when more than one
 * option may be chosen.
 *
 * A group rendered by the server adopts the name already on its inputs, so the
 * markup keeps posting the way it did before the component upgraded.
 *
 * Set `appearance` to draw the options as buttons or cards rather than a
 * stacked list. They're still radios, so they're announced as radios and the
 * arrow keys move the selection.
 *
 * - `buttons`: separate buttons.
 * - `button-group`: buttons joined in one row, like a selection-mode
 *   `craft-button-group`.
 * - `cards`: separate cards, each radio's `icon` drawn large above its label.
 * - `card-group`: cards joined in one row.
 *
 * Every appearance wraps onto more rows when the options don't fit in one. A
 * group that wraps comes apart, so it looks like its separate counterpart:
 * a `button-group` like `buttons`, and a `card-group` like `cards`.
 *
 * An option whose label has no text, such as a labeled `craft-icon`, gets a
 * square button.
 *
 * @slot - The `craft-radio`s in the group.
 * @slot label - The group's label.
 * @slot help-text - Guidance shown below the label.
 * @slot feedback - Validation messages for the group as a whole.
 *
 * @fires {Event} input - When the user selects an option.
 * @fires {Event} change - When the user selects an option.
 */
export default class CraftRadioGroup extends ChoiceGroup(LitElement, {
  choice: 'craft-radio',
  multiple: false,
}) {
  /** The value of the checked radio, or empty when none is. Setting it checks that radio. */
  declare value: string;

  /**
   * How the options are shown, instead of a stacked list: as separate
   * `buttons` or `cards`, or joined in a `button-group` or `card-group`.
   * Cards are for radios with an `icon`.
   */
  @property({reflect: true}) appearance?: RadioGroupAppearance;

  /** Whether a joined group has come apart and wrapped, because the row didn't fit. */
  private _wrapped = false;

  /**
   * Watches a zero-height probe as wide as the group, rather than the group
   * itself. Wrapping changes the group's height, and observing that would
   * report a resize caused by its own callback (a ResizeObserver loop). The
   * radios are watched too, since a button's width follows its label; wrapping
   * doesn't change a button's width, so that can't loop either.
   */
  private _resizeObserver = new ResizeObserver(() => this._fitRow());

  override render() {
    return html`${super.render()}
      <div class="fit-probe" aria-hidden="true"></div>
      <div class="row-size" aria-hidden="true"></div>`;
  }

  override connectedCallback() {
    super.connectedCallback();
    this.updateComplete.then(() => {
      const probe = this.shadowRoot?.querySelector('.fit-probe');
      if (probe && this.isConnected) {
        this._resizeObserver.observe(probe);
      }
    });
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this._resizeObserver.disconnect();
  }

  protected override _onChoicesChanged() {
    this._resizeObserver.disconnect();
    const probe = this.shadowRoot?.querySelector('.fit-probe');
    if (probe) {
      this._resizeObserver.observe(probe);
    }
    for (const radio of this.choices) {
      this._resizeObserver.observe(radio as unknown as HTMLElement);
    }
    this._syncButtons();
    this._fitRow();
  }

  override updated(changed: PropertyValues) {
    super.updated(changed);
    if (changed.has('appearance')) {
      this._syncButtons();
      this._fitRow();
    }
  }

  /**
   * Tells each radio whether to draw itself as a button or a card, and which
   * ones sit at the ends of a joined row, so only those get rounded outer
   * corners. Separate options each stand alone, so each is both a start and an
   * end.
   */
  private _syncButtons() {
    const appearance = this.appearance;
    const buttons = appearance === 'buttons' || appearance === 'button-group';
    const cards = appearance === 'cards' || appearance === 'card-group';
    const joined =
      (appearance === 'button-group' || appearance === 'card-group') &&
      !this._wrapped;
    const radios = this.choices as unknown as HTMLElement[];

    this.style.setProperty('--_card-count', String(radios.length));

    radios.forEach((radio, index) => {
      const start = !joined || index === 0;
      const end = !joined || index === radios.length - 1;

      radio.toggleAttribute('data-button', buttons);
      radio.toggleAttribute('data-button-start', buttons && start);
      radio.toggleAttribute('data-button-end', buttons && end);
      radio.toggleAttribute('data-card', cards);
      radio.toggleAttribute('data-card-start', cards && start);
      radio.toggleAttribute('data-card-end', cards && end);
    });
  }

  /**
   * Wraps a joined group once it's narrower than its joined row would be:
   * every button at its own width, or every card at its minimum. Neither the
   * threshold nor the group's own width depends on the layout it picks, so
   * switching can't make it switch back, even in a container that sizes to
   * its content.
   */
  private _fitRow() {
    let wrapped = false;
    const appearance = this.appearance;

    if (appearance === 'button-group' || appearance === 'card-group') {
      const radios = this.choices as unknown as HTMLElement[];
      const rowWidth = radios.reduce(
        (width, radio) =>
          width +
          (appearance === 'card-group'
            ? parseFloat(getComputedStyle(radio).minWidth)
            : radio.getBoundingClientRect().width),
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
      formControlStyles,
      inputStyles,
      css`
        .fit-probe {
          block-size: 0;
        }

        .input-group {
          display: grid;
          gap: var(--c-spacing-xs);
        }

        :host([appearance]) ::slotted(:not(craft-radio)) {
          display: contents;
        }

        :host([appearance='button-group']) .input-group {
          display: flex;
          flex-wrap: wrap;
          width: fit-content;
          gap: 0;
        }

        :host([appearance='buttons']) .input-group,
        :host([appearance='button-group'][data-wrapped]) .input-group {
          display: flex;
          flex-wrap: wrap;
          gap: var(--c-spacing-sm);
        }

        /*
          A row of cards is as wide as they are at their largest, so a few
          don't stretch. It's capped by the container, so a grid or flex parent
          can shrink the group and wrap it. The row's width still reaches a
          container that sizes to its content, through .row-size.
        */
        :host([appearance='cards']) {
          --_row-size: calc(
            var(--_card-count, 1) * var(--c-radio-card-max-size) +
              (var(--_card-count, 1) - 1) * var(--c-spacing-sm)
          );
        }

        :host([appearance='card-group']) {
          --_row-size: calc(
            var(--_card-count, 1) * (var(--c-radio-card-max-size) - 1px) + 1px
          );
        }

        :host([appearance='cards']),
        :host([appearance='card-group']) {
          min-inline-size: 0;
          inline-size: min(100%, var(--_row-size));
        }

        .row-size {
          block-size: 0;
          inline-size: var(--_row-size, 0);
          max-inline-size: 100%;
        }

        :host([appearance='card-group']) .input-group {
          grid-auto-flow: column;
          grid-auto-columns: minmax(
            var(--c-radio-card-min-size),
            var(--c-radio-card-max-size)
          );
          justify-content: start;
          align-items: start;
          gap: 0;
        }

        :host([appearance='cards']) .input-group,
        :host([appearance='card-group'][data-wrapped]) .input-group {
          grid-auto-flow: row;
          grid-template-columns: repeat(
            auto-fill,
            minmax(var(--c-radio-card-min-size), 1fr)
          );
          align-items: start;
          gap: var(--c-spacing-md) var(--c-spacing-sm);
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
