import {css, html, LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import CraftInput from '../input/input.js';
import CraftInputDate from '../input-date/input-date.js';
import CraftInputTime, {
  type DisabledTimeRange,
} from '../input-time/input-time.js';

/**
 * Default-on boolean: only the exact string `"false"` turns it off, so
 * `show-date` behaves like a config flag rather than a bare HTML boolean
 * attribute (where mere presence would mean true).
 */
const booleanAttribute = {
  fromAttribute: (value: string | null) => value !== 'false',
  toAttribute: (value: boolean) => String(value),
};

/**
 * @summary A paired date and time field. It owns a `craft-input-date`, a
 * `craft-input-time`, and optionally a timezone input, creating them as its
 * own light-DOM children and keeping their values in step.
 *
 * Give it a `name` and it submits the parts under that name — `name[date]`,
 * `name[time]`, `name[timezone]`, and `name[locale]` — so the server receives
 * one coherent value. The locale always goes along, and the timezone is
 * carried by a hidden input whenever it is not shown as a control.
 *
 * The component has no label or field chrome of its own. Wrap it in
 * `craft-field` for a label, instructions, and error handling.
 *
 * @slot - Content placed after the date and time inputs, such as a button
 *   that clears them. The component's own inputs always lead, so slotted
 *   controls stay after the inputs they act on in reading and tab order.
 */
export default class CraftInputDateTime extends LitElement {
  /**
   * Base name for the submitted values. Each part is posted under it as
   * `name[date]`, `name[time]`, `name[timezone]`, and `name[locale]`. Without
   * a name nothing is submitted.
   */
  @property() name?: string;

  /** Value of the date input, as `YYYY-MM-DD`. */
  @property({attribute: 'date-value'}) dateValue = '';

  /** Value of the time input, as `HH:MM`. */
  @property({attribute: 'time-value'}) timeValue = '';

  /**
   * Locale the inputs format against. Always submitted as `name[locale]`, so
   * the server can read the parts back the way they were entered.
   */
  @property() locale?: string;

  /**
   * The timezone the value belongs to. Shown as an editable input when
   * `show-timezone` is set, and submitted as a hidden input otherwise.
   */
  @property() timezone?: string;

  /**
   * Whether the date input is shown. On by default — set `show-date="false"`
   * to drop it and leave a time-only field. Any other value counts as on.
   */
  @property({attribute: 'show-date', converter: booleanAttribute})
  showDate = true;

  /**
   * Whether the time input is shown. On by default — set `show-time="false"`
   * to drop it and leave a date-only field. Any other value counts as on.
   */
  @property({attribute: 'show-time', converter: booleanAttribute})
  showTime = true;

  /**
   * Whether the timezone is editable. Unlike `show-date` and `show-time` this
   * is an ordinary boolean attribute: absent means off, present means on.
   */
  @property({type: Boolean, attribute: 'show-timezone'})
  showTimezone = false;

  /** Earliest selectable date, as `YYYY-MM-DD`. */
  @property() min?: string;

  /** Latest selectable date, as `YYYY-MM-DD`. */
  @property() max?: string;

  /** Earliest selectable time, as `HH:MM`. */
  @property({attribute: 'min-time'}) minTime?: string;

  /** Latest selectable time, as `HH:MM`. */
  @property({attribute: 'max-time'}) maxTime?: string;

  /** Spacing between the time input's suggestions, in minutes. */
  @property({type: Number, attribute: 'minute-increment'}) minuteIncrement = 30;

  /**
   * Time ranges that cannot be chosen, as a JSON array of `[start, end]`
   * pairs of `HH:MM` times. Passed through to the time input, which enforces
   * them with validation.
   */
  @property({
    attribute: 'disabled-time-ranges',
    converter: {
      // An absent attribute is `null` and an empty one is `''`; neither is
      // valid JSON, so both have to fall back rather than reach `JSON.parse`.
      fromAttribute: (value) => (value ? JSON.parse(value) : []),
      toAttribute: (value) => JSON.stringify(value),
    },
  })
  disabledTimeRanges: DisabledTimeRange[] = [];

  /** Snaps a typed time onto the nearest `minute-increment` step. */
  @property({type: Boolean, attribute: 'force-round-time'})
  forceRoundTime = false;

  /** Disables every input the component owns. */
  @property({type: Boolean}) disabled = false;

  /** Makes every input the component owns read-only. */
  @property({type: Boolean}) readonly = false;

  /** Marks the date and time inputs as required. */
  @property({type: Boolean}) required = false;

  /**
   * Id of the element describing this field, applied as `aria-describedby` to
   * each input the component owns. `craft-field` supplies this for you.
   */
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
