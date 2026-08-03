import {html, LitElement, nothing, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {classMap} from 'lit/directives/class-map.js';
import {ifDefined} from 'lit/directives/if-defined.js';
import {t} from '@src/utilities/translate';
import styles from './slide-picker.styles.js';

/**
 * @summary Segmented slider control for selecting numeric values in fixed steps.
 * @since 1.0
 *
 * @fires {CustomEvent<{value:number}>} value-change - Emitted when user input
 * changes the current value.
 */
export default class CraftSlidePicker extends LitElement {
  static override styles = [styles];

  @property({type: Number}) min = 0;
  @property({type: Number}) max = 100;
  @property({type: Number}) step = 10;
  @property({type: Number}) value = 0;
  @property() label = t('Number of columns');
  @property({attribute: 'described-by'}) describedBy?: string;
  @property({attribute: 'value-unit'}) valueUnit = '';
  @property({attribute: false}) valueLabel?: (value: number) => string;
  @property({type: Boolean, reflect: true, attribute: 'read-only'})
  readOnly = false;

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
    return this.valueUnit ? `${value}${this.valueUnit}` : `${value}`;
  }

  #setValue(next: number, emit: boolean) {
    const normalized = this.#normalize(next);
    if (normalized === this.value) {
      return;
    }

    this.value = normalized;

    if (emit) {
      this.dispatchEvent(
        new CustomEvent<{value: number}>('value-change', {
          detail: {value: normalized},
          bubbles: true,
          composed: true,
        })
      );
    }
  }

  #handleSegmentClick(value: number) {
    if (this.readOnly) {
      return;
    }
    this.#setValue(value, true);
  }

  #handleKeyDown(event: KeyboardEvent) {
    if (this.readOnly) {
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
  }

  override render() {
    const values = this.#values();

    return html`
      <div
        class="slide-picker"
        role="slider"
        tabindex=${this.readOnly ? -1 : 0}
        aria-label=${this.label}
        aria-valuemin=${this.min}
        aria-valuemax=${this.max}
        aria-valuenow=${this.value}
        aria-valuetext=${this.#valueText(this.value)}
        aria-readonly=${this.readOnly ? 'true' : 'false'}
        aria-describedby=${ifDefined(this.describedBy)}
        @keydown=${this.#handleKeyDown}
      >
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
