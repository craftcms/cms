import {property} from 'lit/decorators.js';
import type {PropertyValues} from 'lit';
import CraftInput from '../input/input.js';
import {jsonAttribute} from '@src/utilities/converters';

/** A `[start, end]` pair of `HH:MM` times that cannot be selected. */
export type DisabledTimeRange = [string, string];

/**
 * @summary A time input. `craft-input` with its `type` fixed to `time`, so it
 * carries the same label, help text, validation, and slots, and renders the
 * browser's own time picker.
 *
 * Values are `HH:MM`, and `min` and `max` take the same form. On top of the
 * native field it adds a stepping interval, a set of unselectable ranges, and
 * optional rounding. To edit a date and a time together, use
 * `craft-input-date-time`.
 */
export default class CraftInputTime extends CraftInput {
  /**
   * Spacing between the picker's suggestions, in minutes. Applied to the
   * native input as `step`.
   */
  @property({type: Number, attribute: 'minute-increment'})
  minuteIncrement = 30;

  /**
   * Ranges that cannot be selected, as a JSON array of `[start, end]` pairs of
   * `HH:MM` times — `[["12:00", "13:00"]]` blocks the lunch hour. The start
   * is inclusive and the end exclusive. A time inside a range fails
   * validation rather than being unselectable in the picker.
   */
  @property({
    attribute: 'disabled-time-ranges',
    converter: jsonAttribute<DisabledTimeRange[]>(() => []),
  })
  disabledTimeRanges: DisabledTimeRange[] = [];

  /**
   * Snaps a typed or picked time onto the nearest `minute-increment` step when
   * the field changes.
   */
  @property({type: Boolean, attribute: 'force-round-time'})
  forceRoundTime = false;

  constructor() {
    super();
    this.type = 'time';
  }

  override connectedCallback() {
    super.connectedCallback();
    this.addEventListener('change', this.#onChange);
  }

  override disconnectedCallback() {
    this.removeEventListener('change', this.#onChange);
    super.disconnectedCallback();
  }

  override updated(changedProperties: PropertyValues) {
    if (changedProperties.has('minuteIncrement')) {
      this.step = this.minuteIncrement * 60;
    }

    super.updated(changedProperties);
    this.#validate();
  }

  #onChange(event: Event) {
    if (event.target === this || !(event.target instanceof HTMLInputElement)) {
      return;
    }

    if (this.forceRoundTime) {
      const rounded = this.#round(event.target.value);
      event.target.value = rounded;
      this.modelValue = rounded;
      this.value = rounded;
    }

    this.#validate();
  }

  #validate() {
    const input = this._inputNode;

    if (!input) {
      return;
    }

    const seconds = this.#seconds(input.value);
    const disabled =
      seconds !== null &&
      this.disabledTimeRanges.some(([start, end]) => {
        const startSeconds = this.#seconds(start);
        const endSeconds = this.#seconds(end);

        return (
          startSeconds !== null &&
          endSeconds !== null &&
          seconds >= startSeconds &&
          seconds < endSeconds
        );
      });

    input.setCustomValidity(disabled ? 'This time is unavailable.' : '');
  }

  #round(value: string): string {
    const seconds = this.#seconds(value);

    if (seconds === null) {
      return value;
    }

    const increment = Math.max(1, this.minuteIncrement) * 60;
    const rounded = Math.min(
      86399,
      Math.round(seconds / increment) * increment
    );
    const hours = Math.floor(rounded / 3600);
    const minutes = Math.floor((rounded % 3600) / 60);

    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
  }

  #seconds(value: string): number | null {
    const match = /^(\d{1,2}):(\d{2})(?::(\d{2}))?$/.exec(value);

    if (!match) {
      return null;
    }

    return (
      Number(match[1]) * 3600 + Number(match[2]) * 60 + Number(match[3] ?? 0)
    );
  }
}

if (!customElements.get('craft-input-time')) {
  customElements.define('craft-input-time', CraftInputTime);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-input-time': CraftInputTime;
  }
}
