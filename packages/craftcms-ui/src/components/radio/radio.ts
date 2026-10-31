import {LionRadio} from '@lion/ui/radio-group.js';
import {css, html, type PropertyValues} from 'lit';
import {SsrChoiceInputMixin} from '@src/mixins/SsrChoiceInputMixin';

/**
 * @summary One option within a `craft-radio-group`. A radio outside a group
 * has nothing to be exclusive against, so it is always used in one.
 *
 * Built on Lion's radio, and like `craft-checkbox` it keeps the `checked`,
 * `disabled`, and `name` of a server-rendered input rather than resetting them
 * on upgrade.
 *
 * In a `craft-radio-group` with `buttons`, it's drawn as one of a row of joined
 * buttons, with the native input stretched transparently over it. The input
 * still takes the clicks, focus and keyboard, so it stays a radio.
 *
 * In a `craft-radio-group` with `thumbnails`, it's drawn as a tile: its
 * `thumbnail` illustration in a button-like box, with the label below it. The
 * native input is stretched over the whole tile in the same way.
 *
 * @slot label - The option's label, as an alternative to the `label`
 *   attribute.
 * @slot help-text - Guidance shown below the label. Not shown on a button or a
 *   tile.
 * @slot thumbnail - An illustration of the option, such as an `<img>` with an
 *   empty `alt`, since the label already names the option. Shown only in a
 *   `craft-radio-group` with `thumbnails`.
 *
 * @csspart thumbnail - The box around the illustration, on a tile.
 */
export default class CraftRadio extends SsrChoiceInputMixin(LionRadio) {
  override render() {
    return html`<div class="choice-field__thumbnail" part="thumbnail">
        <slot name="thumbnail"></slot>
      </div>
      ${super.render()}`;
  }

  static override get styles() {
    return [
      ...super.styles,
      css`
        /*
         * Match the checkbox's gaps: space between the input and its label, but
         * none between rows, since the (often empty) help text wraps onto its own
         */
        :host {
          gap: 0 var(--c-spacing-md);
        }

        .choice-field__thumbnail {
          display: none;
        }

        :host([data-tile]) {
          --_radius: var(--c-radius-lg);
          --_padding: calc(var(--c-spacing) * 3);
          position: relative;
          display: grid;
          grid-template-columns: minmax(0, 1fr);
          justify-items: center;
          align-content: start;
          gap: var(--c-spacing-xs);
          min-inline-size: var(--c-radio-tile-min-size);
          text-align: center;
        }

        :host([data-tile]) .choice-field__thumbnail {
          display: grid;
          place-items: center;
          box-sizing: border-box;
          inline-size: 100%;
          padding: var(--_padding);
          border: 1px solid var(--c-form-control-border-color);
          background-color: transparent;
        }

        :host([data-tile]:not([data-tile-start])) {
          margin-inline-start: -1px;
        }

        :host([data-tile]:not([data-tile-start])) .choice-field__thumbnail {
          border-inline-start-color: var(--c-color-neutral-border-quiet);
        }

        :host([data-tile]:not([data-tile-end])) .choice-field__thumbnail {
          border-inline-end-color: var(--c-color-neutral-border-quiet);
        }

        :host([data-tile][data-tile-start]) .choice-field__thumbnail {
          border-start-start-radius: var(--_radius);
          border-end-start-radius: var(--_radius);
        }

        :host([data-tile][data-tile-end]) .choice-field__thumbnail {
          border-start-end-radius: var(--_radius);
          border-end-end-radius: var(--_radius);
        }

        :host([data-tile]) .choice-field__label {
          font-size: var(--c-text-sm);
          color: var(--c-text-quiet);
        }

        :host([data-tile]) .choice-field__help-text {
          display: none;
        }

        @media (hover: hover) {
          :host([data-tile]:not([checked]):not([disabled]):hover) {
            z-index: 1;
          }

          :host([data-tile]:not([checked]):not([disabled]):hover)
            .choice-field__thumbnail {
            background-color: color-mix(
              in oklab,
              var(--c-color-neutral-fill-quiet),
              var(--c-color-mix-hover)
            );
          }
        }

        :host([data-tile][checked]) {
          z-index: 1;
        }

        :host([data-tile][checked]) .choice-field__thumbnail {
          padding: calc(var(--_padding) - 1px);
          border: 2px solid var(--c-color-accent-border-normal);
          background-color: var(--c-color-accent-fill-quiet);
        }

        :host([data-tile][checked]) .choice-field__label {
          color: var(--c-text-default);
          font-weight: bold;
        }

        :host([data-tile][focused-visible]) {
          z-index: 2;
        }

        :host([data-tile][focused-visible]) .choice-field__thumbnail {
          outline: var(--c-focus-outline-width) var(--c-focus-outline-style)
            var(--c-color-focus-outline);
          outline-offset: 1px;
        }

        :host([data-tile][disabled]) {
          opacity: 0.5;
        }

        @media (forced-colors: active) {
          :host([data-tile]) .choice-field__thumbnail {
            border-color: ButtonBorder;
          }

          :host([data-tile][checked]) .choice-field__thumbnail {
            border-color: Highlight;
          }

          :host([data-tile][checked]) .choice-field__label {
            color: CanvasText;
          }
        }

        :host([data-button]) {
          --_radius: var(--c-button-radius, var(--c-form-control-radius));
          position: relative;
          display: inline-flex;
          flex-wrap: nowrap;
          align-items: center;
          justify-content: center;
          gap: 0;
          box-sizing: border-box;
          min-height: var(--c-size-control-md);
          min-width: var(--c-size-control-md);
          padding-inline: var(--c-form-control-spacing-inline);
          border: 1px solid var(--c-form-control-border-color);
          border-radius: 0;
          background-color: transparent;
          color: var(--c-color-neutral-on-quiet);
          white-space: nowrap;
        }

        :host([data-button]:not([data-button-start])) {
          margin-inline-start: -1px;
          border-inline-start-color: var(--c-color-neutral-border-quiet);
        }

        :host([data-button]:not([data-button-end])) {
          border-inline-end-color: var(--c-color-neutral-border-quiet);
        }

        :host([data-button][data-button-start]) {
          border-start-start-radius: var(--_radius);
          border-end-start-radius: var(--_radius);
        }

        :host([data-button][data-button-end]) {
          border-start-end-radius: var(--_radius);
          border-end-end-radius: var(--_radius);
        }

        :host([data-button][data-icon-only]) {
          padding-inline: 0;
          aspect-ratio: 1;
        }

        :host([data-button]) .choice-field__help-text {
          display: none;
        }

        @media (hover: hover) {
          :host([data-button]:not([checked]):not([disabled]):hover) {
            z-index: 1;
            background-color: color-mix(
              in oklab,
              var(--c-color-neutral-fill-quiet),
              var(--c-color-mix-hover)
            );
          }
        }

        :host([data-button][checked]) {
          z-index: 1;
          background-color: var(--c-color-neutral-fill-loud);
          border-color: var(--c-color-neutral-border-loud);
          color: var(--c-color-neutral-on-loud);
        }

        :host([data-button][focused-visible]) {
          z-index: 2;
        }

        :host([data-button][disabled]) {
          opacity: 0.5;
        }

        @media (forced-colors: active) {
          :host([data-button]) {
            border-color: ButtonBorder;
          }

          :host([data-button][checked]) {
            background-color: Highlight;
            border-color: Highlight;
            color: HighlightText;
          }
        }
      `,
    ];
  }

  override firstUpdated(changed: PropertyValues) {
    super.firstUpdated(changed);
    this.shadowRoot
      ?.querySelector('slot[name="label"]')
      ?.addEventListener('slotchange', this._syncIconOnly);
    this._syncIconOnly();
  }

  /**
   * Marks a radio whose label has no text, such as a labeled `craft-icon`, so
   * it draws a square button in a `craft-radio-group` with `buttons`.
   */
  private _syncIconOnly = () => {
    const label = this.querySelector(':scope > [slot="label"]');
    this.toggleAttribute('data-icon-only', !label?.textContent?.trim());
  };
}

if (!customElements.get('craft-radio')) {
  customElements.define('craft-radio', CraftRadio);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-radio': CraftRadio;
  }
}
