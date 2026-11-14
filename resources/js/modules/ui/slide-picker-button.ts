import {css, html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';
import {classMap} from 'lit/directives/class-map.js';
import {ifDefined} from 'lit/directives/if-defined.js';
import type CraftSlidePicker from '@craftcms/ui/components/slide-picker/slide-picker';
import '@craftcms/ui/components/button/button';
import '@craftcms/ui/components/popover/popover';
import '@craftcms/ui/components/slide-picker/slide-picker';
import '@craftcms/ui/components/tooltip/tooltip';
import {defineElement} from '@/common/web-components/defineElement';

/**
 * @summary A plain button that shows a value as a small, static copy of a
 * `craft-slide-picker`, and opens a larger one in a popover to change it.
 *
 * For a slide picker that sits in a dense row, such as a widget's column
 * count or a field layout element's width, where full-size segments don't
 * fit. The button is the target, so the segments drawn in it can stay small.
 *
 * @fires change - The value changed in the popover's slide picker.
 */
export default class CraftSlidePickerButton extends LitElement {
  static override styles = css`
    :host {
      display: inline-flex;
    }

    .segments {
      display: inline-flex;
      pointer-events: none;
    }

    .segment {
      box-sizing: border-box;
      inline-size: calc(9rem / 16);
      block-size: 1rem;
      border: 1px solid var(--c-color-neutral-border-quiet);
      border-inline-start-width: 0;
      background-color: var(
        --c-slide-picker-segment-fill,
        var(--c-color-neutral-fill-quiet)
      );
    }

    .segment:first-child {
      border-inline-start-width: 1px;
      border-start-start-radius: var(--c-radius-sm);
      border-end-start-radius: var(--c-radius-sm);
    }

    .segment:last-child {
      border-start-end-radius: var(--c-radius-sm);
      border-end-end-radius: var(--c-radius-sm);
    }

    .segment.is-active {
      background-color: var(
        --c-slide-picker-segment-active-fill,
        color-mix(
          in oklab,
          var(--c-color-neutral-fill-loud),
          var(--c-color-neutral-fill-normal) 70%
        )
      );
      border-color: var(--c-color-neutral-border-loud);
    }

    .segment.is-active:not(.is-last-active) {
      border-inline-end-color: var(--c-color-neutral-border-quiet);
    }

    craft-popover::part(popup) {
      min-width: 0;
    }

    .content {
      display: grid;
      justify-items: center;
      gap: var(--c-spacing-xs);
      inline-size: min-content;
    }

    craft-slide-picker {
      --c-slide-picker-segment-width: calc(40rem / 16);
      --c-slide-picker-segment-height: calc(32rem / 16);
    }

    .label {
      font-size: var(--text-sm);
      text-align: center;
      overflow-wrap: break-word;
    }

    @media (forced-colors: active) {
      .segment {
        background: Canvas;
        border-color: ButtonBorder;
      }

      .segment.is-active {
        background: Highlight;
        border-color: Highlight;
      }
    }
  `;

  /** Labels the slide picker in the popover, and names the button by default. */
  @property() label = '';
  /**
   * Names the button, when it needs more context than `label`, such as the
   * name of the item it sizes. Should start with `label`.
   */
  @property({attribute: 'button-label'}) buttonLabel = '';
  /** The selected value. */
  @property({type: Number}) value = 0;
  /** Lowest selectable value. */
  @property({type: Number}) min = 0;
  /** Highest selectable value. */
  @property({type: Number}) max = 100;
  /** Spacing between selectable values. */
  @property({type: Number}) step = 10;
  /** Unit shown and announced after the value, as `craft-slide-picker` takes it. */
  @property({attribute: 'value-unit'}) valueUnit = '';
  /** Formats the value, for when a number and a unit are not enough. */
  @property({attribute: false}) valueLabel?: (value: number) => string;
  /** Passed to the button; see `craft-button`'s `flush`. */
  @property() flush?: string;
  /** Stops the button opening the popover. */
  @property({type: Boolean, reflect: true}) disabled = false;
  /** Opens the popover with a slide picker that can't be changed. */
  @property({type: Boolean, reflect: true}) readonly = false;

  #valueText(value: number): string {
    if (this.valueLabel) {
      return this.valueLabel(value);
    }
    const unit = this.valueUnit.trim();
    const separator = /^\p{L}/u.test(unit) ? ' ' : '';
    return unit ? `${value}${separator}${unit}` : `${value}`;
  }

  #onLabelClick = (): void => {
    this.renderRoot.querySelector('craft-slide-picker')?.focus();
  };

  #onChange = (event: Event): void => {
    event.stopPropagation();
    this.value = (event.target as CraftSlidePicker).value;
    this.dispatchEvent(new Event('change', {bubbles: true}));
  };

  override render() {
    const steps: number[] = [];
    for (let value = this.min; value <= this.max; value += this.step) {
      steps.push(value);
    }
    const valueText = this.#valueText(this.value);

    return html`
      <craft-popover placement="bottom">
        <craft-button
          id="invoker"
          slot="invoker"
          type="button"
          variant="plain"
          size="small"
          flush=${ifDefined(this.flush)}
          aria-label=${`${this.buttonLabel || this.label} ${valueText}`}
          ?disabled=${this.disabled}
        >
          <span class="segments" aria-hidden="true">
            ${steps.map(
              (value) =>
                html`<span
                  class=${classMap({
                    segment: true,
                    'is-active': value <= this.value,
                    'is-last-active': value === this.value,
                  })}
                ></span>`
            )}
          </span>
        </craft-button>
        <div class="content" slot="content-body">
          <craft-slide-picker
            labelled-by="label"
            value-unit=${this.valueUnit}
            .valueLabel=${this.valueLabel}
            .min=${this.min}
            .max=${this.max}
            .step=${this.step}
            .value=${this.value}
            ?readonly=${this.readonly}
            @change=${this.#onChange}
          ></craft-slide-picker>
          <span id="label" class="label" @click=${this.#onLabelClick}
            >${this.label}</span
          >
        </div>
      </craft-popover>
      <craft-tooltip for="invoker">${valueText}</craft-tooltip>
    `;
  }
}

defineElement('craft-slide-picker-button', CraftSlidePickerButton);

declare global {
  interface HTMLElementTagNameMap {
    'craft-slide-picker-button': CraftSlidePickerButton;
  }
}
