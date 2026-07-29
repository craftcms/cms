import {html, LitElement, type PropertyValues} from 'lit';
import {property, query} from 'lit/decorators.js';
import {classMap} from 'lit/directives/class-map.js';
import {t} from '@src/utilities/translate';
import styles from './slide-rule.styles.js';

const SENSITIVITY = 3;

/**
 * @summary Ruler-style slider for fine rotation adjustment — the port of the
 * legacy `Craft.SlideRuleInput`, used by the image editor's straighten control.
 * Drag the ruler (or use the arrow keys) to pick a value; the visible slide range
 * (`min`/`max`) is narrower than the drawn graduation range
 * (`graduation-min`/`graduation-max`).
 *
 * @since 1.0
 *
 * @fires {CustomEvent<{value:number}>} start - A drag/interaction began.
 * @fires {CustomEvent<{value:number}>} change - The value changed.
 * @fires {CustomEvent<{value:number}>} end - A drag/interaction ended.
 */
export default class CraftSlideRule extends LitElement {
  static override styles = [styles];

  /** Minimum selectable value (the slide range floor). */
  @property({type: Number}) min = -45;

  /** Maximum selectable value (the slide range ceiling). */
  @property({type: Number}) max = 45;

  /** Current value. */
  @property({type: Number}) value = 0;

  /** First drawn graduation (wider than the slide range). */
  @property({type: Number, attribute: 'graduation-min'}) graduationMin = -70;

  /** Last drawn graduation (wider than the slide range). */
  @property({type: Number, attribute: 'graduation-max'}) graduationMax = 70;

  /** Accessible name for the slider. */
  @property() label = t('Rotate');

  @query('.slide-rule') private _root!: HTMLElement;
  @query('.graduations') private _graduations!: HTMLElement;
  @query('.graduations ul') private _list!: HTMLElement;
  @query('.cursor') private _cursor!: HTMLElement;

  #dragging = false;
  #rotateIntent = false;
  #startPositionX = 0;
  #startLeft = 0;
  #calculatedWidth = 0;
  #resizeObserver: ResizeObserver | null = null;

  #graduations(): number[] {
    const list: number[] = [];
    for (let i = this.graduationMin; i <= this.graduationMax; i++) {
      list.push(i);
    }
    return list;
  }

  override connectedCallback(): void {
    super.connectedCallback();
    this.#resizeObserver = new ResizeObserver(() => this.#reposition());
    this.#resizeObserver.observe(this);
  }

  override disconnectedCallback(): void {
    super.disconnectedCallback();
    this.#resizeObserver?.disconnect();
    this.#resizeObserver = null;
  }

  override firstUpdated(): void {
    // (n - 1) graduations because each border sits on the left of its 10px box.
    this.#calculatedWidth = (this.#graduations().length - 1) * 10;
    this.#reposition();
  }

  override updated(changed: PropertyValues<this>): void {
    if (
      changed.has('value') ||
      changed.has('min') ||
      changed.has('max') ||
      changed.has('graduationMin') ||
      changed.has('graduationMax')
    ) {
      this.#reposition();
    }
  }

  /** Slides the ruler so the current value lines up under the cursor. */
  #reposition(): void {
    if (!this._list) {
      return;
    }
    this._list.style.left = `${this.#valueToPosition(this.value)}px`;
  }

  #valueText(value: number): string {
    return t('{num, number} {num, plural, =1{degree} other{degrees}}', {
      num: Math.round(value),
    });
  }

  // --- Value math (ported verbatim from Craft.SlideRuleInput) --------------

  #positionToValue(position: number): number {
    const scaleMin = this.graduationMin * -1;
    const scaleMax = (this.graduationMin - this.graduationMax) * -1;

    return (
      ((this._graduations.offsetWidth / 2 + position * -1) /
        this.#calculatedWidth) *
        scaleMax -
      scaleMin
    );
  }

  #valueToPosition(value: number): number {
    const scaleMin = this.graduationMin * -1;
    const scaleMax = (this.graduationMin - this.graduationMax) * -1;

    return -(
      ((value + scaleMin) * this.#calculatedWidth) / scaleMax -
      this._graduations.offsetWidth / 2
    );
  }

  #setValue(rawValue: number, emitChange = true): void {
    const value = Math.min(Math.max(rawValue, this.min), this.max);

    this.value = value;

    if (emitChange) {
      this.#emit('change');
    }
  }

  #emit(type: 'start' | 'change' | 'end'): void {
    this.dispatchEvent(
      new CustomEvent<{value: number}>(type, {
        detail: {value: this.value},
        bubbles: true,
        composed: true,
      })
    );
  }

  // --- Pointer drag (Garnish tap -> pointer events) ------------------------

  #handlePointerDown(event: PointerEvent) {
    // Only rotate when the press lands inside the graduations, matching the
    // legacy `.graduations *` intent check.
    this.#rotateIntent =
      this._graduations.contains(event.target as Node) &&
      event.target !== this._graduations;

    if (!this.#rotateIntent) {
      return;
    }

    event.preventDefault();
    this.#startPositionX = event.clientX;
    this.#startLeft = this._list.offsetLeft;
    this._root.setPointerCapture(event.pointerId);
    this.#emit('start');
  }

  #handlePointerMove(event: PointerEvent) {
    if (!this.#rotateIntent) {
      return;
    }

    if (Math.abs(event.clientX - this.#startPositionX) > SENSITIVITY) {
      this.#dragging = true;
      this._root.classList.add('dragging');
      event.preventDefault();
      this.#setValueFromPointer(event);
    }
  }

  #handlePointerUp(event: PointerEvent) {
    if (!this.#rotateIntent) {
      return;
    }

    if (this.#dragging) {
      event.preventDefault();
      this.#dragging = false;
      this._root.classList.remove('dragging');
    } else {
      // A tap without a drag sets the value at the tapped position.
      this.#setValueFromPointer(event);
    }

    this.#emit('end');
    this._root.releasePointerCapture(event.pointerId);
    this.#startPositionX = 0;
    this.#rotateIntent = false;
  }

  #setValueFromPointer(event: PointerEvent) {
    const referencePosition = this.#dragging
      ? this.#startPositionX
      : this._cursor.getBoundingClientRect().left +
        this._cursor.offsetWidth / 2;

    const delta = this.#dragging
      ? referencePosition - event.clientX
      : event.clientX - referencePosition;

    const position = this.#startLeft - delta;
    this.#setValue(this.#positionToValue(position));
  }

  #handleKeyDown(event: KeyboardEvent) {
    const current = this.value;

    switch (event.key) {
      case 'ArrowUp':
      case 'ArrowRight':
        this.#setValue(current + 1);
        break;
      case 'ArrowDown':
      case 'ArrowLeft':
        this.#setValue(current - 1);
        break;
      case 'PageUp':
        this.#setValue(current + 10);
        break;
      case 'PageDown':
        this.#setValue(current - 10);
        break;
      case 'Home':
        this.#setValue(this.min);
        break;
      case 'End':
        this.#setValue(this.max);
        break;
      default:
        return;
    }

    event.preventDefault();
  }

  #isSelected(graduation: number): boolean {
    return (
      graduation >= Math.min(0, this.value) &&
      graduation <= Math.max(0, this.value)
    );
  }

  override render() {
    return html`
      <div
        class="slide-rule"
        role="slider"
        tabindex="0"
        aria-label=${this.label}
        aria-valuemin=${this.min}
        aria-valuemax=${this.max}
        aria-valuenow=${this.value}
        aria-valuetext=${this.#valueText(this.value)}
        @keydown=${this.#handleKeyDown}
        @pointerdown=${this.#handlePointerDown}
        @pointermove=${this.#handlePointerMove}
        @pointerup=${this.#handlePointerUp}
      >
        <div class="overlay"></div>
        <div class="cursor"></div>
        <div class="graduations">
          <ul aria-hidden="true">
            ${this.#graduations().map(
              (graduation) => html`
                <li
                  class=${classMap({
                    graduation: true,
                    'main-graduation': graduation % 5 === 0,
                    selected: this.#isSelected(graduation),
                  })}
                  data-graduation=${graduation}
                >
                  <div class="label">${graduation}</div>
                </li>
              `
            )}
          </ul>
        </div>
      </div>
    `;
  }
}

if (!customElements.get('craft-slide-rule')) {
  customElements.define('craft-slide-rule', CraftSlideRule);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-slide-rule': CraftSlideRule;
  }
}
