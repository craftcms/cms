import {html, LitElement, nothing, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {classMap} from 'lit/directives/class-map.js';
import {FormAssociated} from '@src/mixins/FormAssociated';
import type {FieldControl} from '../field/field.js';
import styles from './slide-picker.styles.js';

/**
 * @summary Segmented slider control for selecting numeric values in fixed steps.
 * @since 1.0
 *
 * @attr {string} name - Field name. The control posts under this, like any
 * other input. Supplied by the `FormAssociated` mixin.
 * @attr {boolean} disabled - Whether the control responds to input. A disabled
 * control is left out of form submission. Supplied by the `FormAssociated`
 * mixin.
 *
 * @fires input - Emitted on every alteration to the value.
 * @fires change - Emitted when an alteration to the value is committed by the
 * user. Every alteration is a commit here, so it follows each `input`.
 *
 * @cssproperty --c-slide-picker-segment-fill - Fill of the segments past the
 * value. Defaults to `--c-color-neutral-fill-quiet`.
 * @cssproperty --c-slide-picker-segment-active-fill - Fill of the segments up
 * to the value. Defaults to `--c-color-neutral-fill-loud` mixed 25% toward
 * `--c-color-neutral-fill-normal`.
 * @cssproperty --c-slide-picker-segment-hover-fill - Fill of the segments up
 * to the hovered one, previewing a value. Defaults to
 * `--c-color-neutral-fill-normal`.
 */
export default class CraftSlidePicker
  extends FormAssociated(LitElement)
  implements FieldControl
{
  static override styles = [styles];

  /** Lowest selectable value. */
  @property({type: Number}) min = 0;
  /** Highest selectable value. */
  @property({type: Number}) max = 100;
  /** Spacing between selectable values. */
  @property({type: Number}) step = 10;
  /** The currently selected value. */
  @property({type: Number}) value = 0;
  /**
   * Accessible name for the slider, for when no field label or `<label for>`
   * names it. Either of those takes its place.
   */
  @property() label = '';
  /**
   * Id of the element describing the control, applied as `aria-describedby`.
   * Inside `craft-field` the field's instructions and errors are added for you.
   */
  @property({attribute: 'described-by'}) describedBy?: string;
  /**
   * Unit announced after the value — `columns`, `px`, `%`. A unit that starts
   * with a letter is separated from the value by a space; a symbol is not.
   * Without one a bare number is all a screen reader gets.
   */
  @property({attribute: 'value-unit'}) valueUnit = '';
  /**
   * Formats the announced value, for when a number and a unit are not enough.
   * A property rather than an attribute, so bind it with `.valueLabel`.
   */
  @property({attribute: false}) valueLabel?: (value: number) => string;
  /**
   * Renders the current value without allowing it to be changed. Spelled the
   * way HTML spells it, and the way the other controls do. A read-only control
   * still posts its value; a `disabled` one does not.
   */
  @property({type: Boolean, reflect: true}) readonly = false;

  /** Elements a surrounding field has added to the name and description. */
  #labelledBy = new Set<HTMLElement>();
  #describedBy = new Set<HTMLElement>();

  constructor() {
    super();
    // The host is the slider, so it takes focus and the keys itself. That
    // keeps it in the same tree as the field label and instructions that name
    // and describe it.
    this.addEventListener('keydown', this.#handleKeyDown);
  }

  /** Whether the control responds to input. */
  get #editable(): boolean {
    return !this.readonly && !this.disabled;
  }

  /**
   * Adds `element` to the slider's accessible name. `craft-field` calls this
   * with its label.
   */
  addToAriaLabelledBy(element: HTMLElement): void {
    this.#labelledBy.add(element);
    this.#syncReferences();
  }

  /**
   * Adds `element` to the slider's accessible description. `craft-field`
   * calls this with its instructions and errors.
   */
  addToAriaDescribedBy(element: HTMLElement): void {
    this.#describedBy.add(element);
    this.#syncReferences();
  }

  #syncReferences(): void {
    const ids = (elements: Set<HTMLElement>) =>
      [...elements].map((element) => {
        element.id ||= `slide-picker-ref-${crypto.randomUUID()}`;
        return element.id;
      });
    const labelledBy = ids(this.#labelledBy);
    const describedBy = [
      ...new Set([
        ...(this.describedBy?.split(/\s+/).filter(Boolean) ?? []),
        ...ids(this.#describedBy),
      ]),
    ];

    this.#setOrRemove('aria-labelledby', labelledBy.join(' '));
    this.#setOrRemove('aria-describedby', describedBy.join(' '));
    if (labelledBy.length) {
      this.removeAttribute('aria-label');
    }
  }

  #setOrRemove(attribute: string, value: string): void {
    if (value) {
      this.setAttribute(attribute, value);
    } else {
      this.removeAttribute(attribute);
    }
  }

  override _formValue(): string | null {
    return this.name ? String(this.value) : null;
  }

  override _restoreFormValue(value: string | null): void {
    this.value = this.#normalize(Number(value ?? this.min));
  }

  protected override willUpdate(changed: PropertyValues<this>) {
    super.willUpdate(changed);

    if (
      changed.has('min') ||
      changed.has('max') ||
      changed.has('step') ||
      changed.has('value')
    ) {
      const normalized = this.#normalize(this.value);
      if (normalized !== this.value) {
        this.value = normalized;
      }
    }
  }

  #values(): number[] {
    const {min, max, step} = this;
    if (step <= 0 || max < min) {
      throw new Error('Invalid craft-slide-picker range configuration.');
    }

    const totalSteps = (max - min) / step;
    if (!Number.isInteger(totalSteps)) {
      throw new Error(
        'Invalid craft-slide-picker step configuration for the provided range.'
      );
    }

    return Array.from(
      {length: totalSteps + 1},
      (_, index) => min + index * step
    );
  }

  #normalize(rawValue: number): number {
    this.#values();
    const {min, max, step} = this;
    const clamped = Math.min(Math.max(rawValue, min), max);
    const snapped = min + Math.round((clamped - min) / step) * step;
    return Math.min(Math.max(snapped, min), max);
  }

  #isRtl(): boolean {
    const dir =
      (this.closest('[dir]') as HTMLElement | null)?.getAttribute('dir') ??
      document.documentElement.getAttribute('dir');
    return dir?.toLowerCase() === 'rtl';
  }

  #valueText(value: number): string {
    if (this.valueLabel) {
      return this.valueLabel(value);
    }
    const unit = this.valueUnit.trim();
    // A word gets a space ("3 columns"); a symbol doesn't ("100%").
    const separator = /^\p{L}/u.test(unit) ? ' ' : '';
    return unit ? `${value}${separator}${unit}` : `${value}`;
  }

  #setValue(next: number, emit: boolean) {
    const normalized = this.#normalize(next);
    if (normalized === this.value) {
      return;
    }

    this.value = normalized;

    if (emit) {
      // Every step is a commit, so `change` follows `input` immediately —
      // what a radio group does, not what a range slider does.
      this.dispatchEvent(new Event('input', {bubbles: true, composed: true}));
      this.dispatchEvent(new Event('change', {bubbles: true, composed: true}));
    }
  }

  #handleSegmentClick(value: number) {
    if (!this.#editable) {
      return;
    }
    this.#setValue(value, true);
  }

  #handleKeyDown = (event: KeyboardEvent) => {
    if (!this.#editable) {
      return;
    }

    const rtl = this.#isRtl();

    switch (event.key) {
      case 'ArrowUp':
        this.#setValue(this.value + this.step, true);
        event.preventDefault();
        break;
      case 'ArrowDown':
        this.#setValue(this.value - this.step, true);
        event.preventDefault();
        break;
      case 'ArrowRight':
        this.#setValue(this.value + (rtl ? -this.step : this.step), true);
        event.preventDefault();
        break;
      case 'ArrowLeft':
        this.#setValue(this.value + (rtl ? this.step : -this.step), true);
        event.preventDefault();
        break;
      case 'Home':
        this.#setValue(this.min, true);
        event.preventDefault();
        break;
      case 'End':
        this.#setValue(this.max, true);
        event.preventDefault();
        break;
    }
  };

  protected override updated(changed: PropertyValues<this>) {
    super.updated(changed);

    this.setAttribute('role', 'slider');
    this.tabIndex = this.#editable ? 0 : -1;
    // A `<label for>` names the slider through `aria-labelledby` too, since
    // support for naming a custom element from its `<label>` is uneven.
    const labelCount = this.#labelledBy.size;
    for (const label of this.labels) {
      this.#labelledBy.add(label as HTMLElement);
    }
    // `aria-label` would outrank those, so `label` only names the slider when
    // nothing else does.
    this.#setOrRemove('aria-label', this.#labelledBy.size ? '' : this.label);
    this.setAttribute('aria-valuemin', String(this.min));
    this.setAttribute('aria-valuemax', String(this.max));
    this.setAttribute('aria-valuenow', String(this.value));
    this.setAttribute('aria-valuetext', this.#valueText(this.value));
    this.setAttribute('aria-readonly', this.readonly ? 'true' : 'false');
    this.setAttribute('aria-disabled', this.disabled ? 'true' : 'false');

    if (changed.has('describedBy') || this.#labelledBy.size !== labelCount) {
      this.#syncReferences();
    }
  }

  override render() {
    const values = this.#values();

    return html`
      <div class="slide-picker">
        ${values.map((segmentValue) => {
          const active = segmentValue <= this.value;
          const lastActive = segmentValue === this.value;
          return html`
            <span
              class=${classMap({
                'slide-picker__segment': true,
                'is-active': active,
                'is-last-active': lastActive,
              })}
              role="presentation"
              aria-hidden="true"
              @click=${() => this.#handleSegmentClick(segmentValue)}
              title=${this.#valueText(segmentValue)}
              >${nothing}</span
            >
          `;
        })}
      </div>
    `;
  }
}

if (!customElements.get('craft-slide-picker')) {
  customElements.define('craft-slide-picker', CraftSlidePicker);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-slide-picker': CraftSlidePicker;
  }
}
