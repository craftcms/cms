import {css, html, LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import CraftInput from '../input/input.js';
import CraftInputDate from '../input-date/input-date.js';
import CraftInputTime, {
  type DisabledTimeRange,
} from '../input-time/input-time.js';

const booleanAttribute = {
  fromAttribute: (value: string | null) => value !== 'false',
  toAttribute: (value: boolean) => String(value),
};

export default class CraftInputDateTime extends LitElement {
  @property() name?: string;

  @property({attribute: 'date-value'}) dateValue = '';

  @property({attribute: 'time-value'}) timeValue = '';

  @property() locale?: string;

  @property() timezone?: string;

  @property({attribute: 'show-date', converter: booleanAttribute})
  showDate = true;

  @property({attribute: 'show-time', converter: booleanAttribute})
  showTime = true;

  @property({type: Boolean, attribute: 'show-timezone'})
  showTimezone = false;

  @property() min?: string;

  @property() max?: string;

  @property({attribute: 'min-time'}) minTime?: string;

  @property({attribute: 'max-time'}) maxTime?: string;

  @property({type: Number, attribute: 'minute-increment'}) minuteIncrement = 30;

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

  @property({type: Boolean}) disabled = false;

  @property({type: Boolean}) readonly = false;

  @property({type: Boolean}) required = false;

  @property({attribute: 'described-by'}) describedBy?: string;

  static override styles = css`
    :host {
      display: flex;
      flex-flow: row wrap;
      align-items: center;
      gap: var(--c-spacing-xs);
    }
  `;

  override render() {
    return html`<slot></slot>`;
  }

  override connectedCallback() {
    super.connectedCallback();
    this.addEventListener('model-value-changed', this.#onModelValueChanged);
  }

  override disconnectedCallback() {
    this.removeEventListener('model-value-changed', this.#onModelValueChanged);
    super.disconnectedCallback();
  }

  override updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);

    this.#syncDateInput();
    this.#syncTimeInput();
    this.#syncTimezoneInput();
    this.#syncHiddenInput('locale', this.locale, true);
    this.#syncHiddenInput('timezone', this.timezone, !this.showTimezone);
  }

  #syncDateInput() {
    const input = this.#input('craft-input-date', 'date', this.showDate);

    if (!(input instanceof CraftInputDate)) {
      return;
    }

    this.#configureInput(input, 'date', this.dateValue, this.required);
    input.min = this.min;
    input.max = this.max;
    this.#appendInput(input);
  }

  #syncTimeInput() {
    const input = this.#input('craft-input-time', 'time', this.showTime);

    if (!(input instanceof CraftInputTime)) {
      return;
    }

    this.#configureInput(input, 'time', this.timeValue, this.required);
    input.min = this.minTime;
    input.max = this.maxTime;
    input.minuteIncrement = this.minuteIncrement;
    input.disabledTimeRanges = this.disabledTimeRanges;
    input.forceRoundTime = this.forceRoundTime;
    this.#appendInput(input);
  }

  #syncTimezoneInput() {
    const input = this.#input('craft-input', 'timezone', this.showTimezone);

    if (!input) {
      return;
    }

    this.#configureInput(input, 'timezone', this.timezone ?? '', false);
    this.#appendInput(input);
  }

  #input(tagName: string, part: string, visible: boolean): CraftInput | null {
    const input = [...this.children].find(
      (child): child is CraftInput =>
        child.tagName.toLowerCase() === tagName && child instanceof CraftInput
    );

    if (!visible) {
      input?.remove();
      return null;
    }

    const currentInput =
      input ?? (document.createElement(tagName) as CraftInput);
    currentInput.dataset.dateTimePart = part;

    return currentInput;
  }

  #appendInput(input: CraftInput) {
    if (input.parentElement === this) {
      return;
    }

    // The inputs lead, in the order they're synced (date, time, timezone).
    // Appending would instead put them *after* whatever the consumer slotted
    // in — a clear button, say, which belongs after the inputs it clears in
    // reading and tab order, not before them.
    this.insertBefore(input, this.#firstSlottedChild());
  }

  /**
   * The first child this component doesn't own, or `null` when it owns them
   * all — i.e. where its own inputs stop and slotted content begins.
   */
  #firstSlottedChild(): Element | null {
    return (
      [...this.children].find(
        (child) =>
          !(child instanceof HTMLElement) ||
          (child.dataset.dateTimePart === undefined &&
            child.dataset.dateTimeMetadata === undefined)
      ) ?? null
    );
  }

  #configureInput(
    input: CraftInput,
    part: string,
    value: string,
    required: boolean
  ) {
    input.name = this.name ? `${this.name}[${part}]` : '';
    input.disabled = this.disabled;
    input.readOnly = this.readonly;
    input.required = required;

    if (input.modelValue !== value) {
      input.modelValue = value;
    }

    if (this.describedBy) {
      input.setAttribute('aria-describedby', this.describedBy);
    } else {
      input.removeAttribute('aria-describedby');
    }
  }

  #onModelValueChanged = (event: Event) => {
    const input = event.target;

    if (
      (event as CustomEvent).detail?.initialize ||
      !(input instanceof CraftInput)
    ) {
      return;
    }

    const value = String(input.modelValue ?? '');

    switch (input.dataset.dateTimePart) {
      case 'date':
        this.dateValue = value;
        break;
      case 'time':
        this.timeValue = value;
        break;
      case 'timezone':
        this.timezone = value;
    }
  };

  #syncHiddenInput(part: string, value: string | undefined, output: boolean) {
    const name = this.name ? `${this.name}[${part}]` : undefined;
    const input = [...this.children].find(
      (child): child is HTMLInputElement =>
        child instanceof HTMLInputElement &&
        child.type === 'hidden' &&
        child.dataset.dateTimeMetadata === part
    );

    if (!name || !output) {
      input?.remove();
      return;
    }

    const metadataInput = input ?? document.createElement('input');
    metadataInput.type = 'hidden';
    metadataInput.dataset.dateTimeMetadata = part;
    metadataInput.name = name;
    metadataInput.value = value ?? '';

    if (!input) {
      this.append(metadataInput);
    }
  }
}

if (!customElements.get('craft-input-date-time')) {
  customElements.define('craft-input-date-time', CraftInputDateTime);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-input-date-time': CraftInputDateTime;
  }
}
