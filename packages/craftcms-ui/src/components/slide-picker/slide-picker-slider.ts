import {html, LitElement, nothing, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {classMap} from 'lit/directives/class-map.js';
import styles from './slide-picker-slider.styles.js';

/**
 * @summary The slider itself — the segments a `craft-slide-picker` renders.
 *
 * It is the picker's internal control rather than something to use directly:
 * on its own it has no label, no help text, and nothing posts. Reach for
 * `craft-slide-picker`, which supplies all three and drives this.
 *
 * Like a native input, it holds its value as a string and fires `input` when a
 * person changes it, which is what the picker listens for.
 *
 * @fires input - A person changed the value. Doesn't bubble; the picker
 *   announces the change from its own element.
 */
export default class CraftSlidePickerSlider extends LitElement {
  static override styles = [styles];

  /** Lowest selectable value. */
  @property({type: Number}) min = 0;
  /** Highest selectable value. */
  @property({type: Number}) max = 100;
  /** Spacing between selectable values. */
  @property({type: Number}) step = 10;
  /** The selected value, as a string, the way a native input holds it. */
  @property() value = '';
  /** Takes the slider out of the tab order and stops it changing. */
  @property({type: Boolean, reflect: true}) disabled = false;
  /** Shows the value without letting it be changed. */
  @property({type: Boolean, reflect: true, attribute: 'readonly'})
  readOnly = false;
  /** Announces a value, as `aria-valuetext` and each segment's tooltip. */
  @property({attribute: false}) valueText: (value: number) => string = String;

  constructor() {
    super();
    this.addEventListener('keydown', this.#handleKeyDown);
  }

  override connectedCallback() {
    super.connectedCallback();
    this.setAttribute('role', 'slider');
  }

  get #editable(): boolean {
    return !this.readOnly && !this.disabled;
  }

  get #number(): number {
    return this.value === '' ? this.min : Number(this.value);
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

  #isRtl(): boolean {
    const dir =
      (this.closest('[dir]') as HTMLElement | null)?.getAttribute('dir') ??
      document.documentElement.getAttribute('dir');
    return dir?.toLowerCase() === 'rtl';
  }

  #setValue(next: number) {
    const value = Math.min(Math.max(next, this.min), this.max);
    if (!this.#editable || value === this.#number) {
      return;
    }

    this.value = String(value);
    this.dispatchEvent(new Event('input'));
  }

  #handleKeyDown = (event: KeyboardEvent) => {
    if (!this.#editable) {
      return;
    }

    const forward = this.#isRtl() ? -this.step : this.step;
    const next: Record<string, number> = {
      ArrowUp: this.#number + this.step,
      ArrowDown: this.#number - this.step,
      ArrowRight: this.#number + forward,
      ArrowLeft: this.#number - forward,
      Home: this.min,
      End: this.max,
    };

    if (event.key in next) {
      event.preventDefault();
      this.#setValue(next[event.key]!);
    }
  };

  override updated(changed: PropertyValues<this>) {
    super.updated(changed);

    this.setAttribute('aria-valuemin', String(this.min));
    this.setAttribute('aria-valuemax', String(this.max));
    this.setAttribute('aria-valuenow', String(this.#number));
    this.setAttribute('aria-valuetext', this.valueText(this.#number));
    this.setAttribute('aria-readonly', String(this.readOnly));
    this.setAttribute('aria-disabled', String(this.disabled));

    if (changed.has('disabled')) {
      this.tabIndex = this.disabled ? -1 : 0;
    }
  }

  override render() {
    return html`
      <div class="slide-picker">
        ${this.#values().map((segmentValue) => {
          return html`
            <span
              class=${classMap({
                'slide-picker__segment': true,
                'is-active': segmentValue <= this.#number,
                'is-last-active': segmentValue === this.#number,
              })}
              aria-hidden="true"
              @click=${() => this.#setValue(segmentValue)}
              title=${this.valueText(segmentValue)}
              >${nothing}</span
            >
          `;
        })}
      </div>
    `;
  }
}

if (!customElements.get('craft-slide-picker-slider')) {
  customElements.define('craft-slide-picker-slider', CraftSlidePickerSlider);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-slide-picker-slider': CraftSlidePickerSlider;
  }
}
