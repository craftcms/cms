import {property} from 'lit/decorators.js';
import type {PropertyValues} from 'lit';
import CraftInput from '../input/input.js';

export type DisabledTimeRange = [string, string];

export default class CraftInputTime extends CraftInput {
  @property({type: Number, attribute: 'minute-increment'})
  minuteIncrement = 30;

  @property({
    attribute: 'disabled-time-ranges',
    converter: {
      fromAttribute: (value) => JSON.parse(value ?? '[]'),
      toAttribute: (value) => JSON.stringify(value),
    },
  })
  disabledTimeRanges: DisabledTimeRange[] = [];

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
