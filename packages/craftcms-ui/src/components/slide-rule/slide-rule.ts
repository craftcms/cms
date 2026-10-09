import {html, LitElement, type PropertyValues} from 'lit';
import {property, query} from 'lit/decorators.js';
import {classMap} from 'lit/directives/class-map.js';
import {FormAssociated} from '@src/mixins/FormAssociated';
import {t} from '@src/utilities/translate';
import styles from './slide-rule.styles.js';

const SENSITIVITY = 3;

/** Matches `--c-slide-rule-graduation-width`, for before anything is laid out. */
const DEFAULT_GRADUATION_WIDTH = 10;

/**
 * @summary Ruler-style slider for fine rotation adjustment — the port of the
 * legacy `Craft.SlideRuleInput`, used by the image editor's straighten control.
 * Drag the ruler (or use the arrow keys) to pick a value; the visible slide range
 * (`min`/`max`) is narrower than the drawn graduation range
 * (`graduation-min`/`graduation-max`).
 *
 * @since 1.0
 *
 * @attr {string} name - Field name. The control posts under this, like any
 * other input. Supplied by the `FormAssociated` mixin.
 * @attr {boolean} disabled - Whether the control responds to input. A disabled
 * control is left out of form submission. Supplied by the `FormAssociated`
 * mixin.
 *
 * @fires input - Emitted on every alteration to the value — continuously
 * through a drag, and once per keyboard step.
 * @fires change - Emitted when an alteration is committed: the end of a drag,
 * or each keyboard step. Not emitted when a gesture leaves the value where it
 * started.
 * @fires {CustomEvent<{value:number}>} craft-drag-start - A drag began.
 * @fires {CustomEvent<{value:number}>} craft-drag-end - A drag ended.
 */
export default class CraftSlideRule extends FormAssociated(LitElement) {
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

  /** Whether the control responds to input. */
  get #editable(): boolean {
    return !this.disabled;
  }

  override _formValue(): string | null {
    return this.name ? String(this.value) : null;
  }

  override _restoreFormValue(value: string | null): void {
    this.value = Math.min(Math.max(Number(value ?? 0), this.min), this.max);
  }

  @query('.slide-rule') private _root!: HTMLElement;
  @query('.graduations') private _graduations!: HTMLElement;
  @query('.graduations ul') private _list!: HTMLElement;
  @query('.cursor') private _cursor!: HTMLElement;
  @query('.indicator') private _indicator!: HTMLElement;

  #dragging = false;
  #rotateIntent = false;
  /** Value when the current drag began, so `change` only fires on a real move. */
  #valueAtDragStart = 0;
  #startPositionX = 0;
  #startLeft = 0;
  #calculatedWidth = 0;
  #placed = false;
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
    // A starting point for the unmeasurable case below; `#reposition()`
    // replaces it with what was actually rendered.
    this.#calculatedWidth =
      (this.#graduations().length - 1) * DEFAULT_GRADUATION_WIDTH;
    this.#reposition();
  }

  override updated(changed: PropertyValues<this>): void {
    // The mixin re-posts the form value from here, so this must chain.
    super.updated(changed);

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
    if (this._graduations?.offsetWidth) {
      this.#measureGraduations();
    }

    // Drawn before the bail below: the indicator is a function of the value
    // and the graduation width, so unlike the strip it doesn't need the
    // window to have been laid out to know where it goes.
    this.#drawIndicator();

    if (!this._list || !this._graduations?.offsetWidth) {
      return;
    }

    this._list.style.transform = `translateX(${this.#valueToPosition(
      this.value
    )}px)`;

    this.#markPlaced();
  }

  /**
   * Enables transitions a frame after the first placement, so the strip and
   * indicator don't animate into place.
   */
  #markPlaced(): void {
    if (this.#placed) {
      return;
    }

    this.#placed = true;
    requestAnimationFrame(() => this._root?.classList.add('placed'));
  }

  /** Sizes the band from the cursor to zero, in the strip's pixels per unit. */
  #drawIndicator(): void {
    if (!this._indicator) {
      return;
    }

    const scaleMax = (this.graduationMin - this.graduationMax) * -1;
    const perUnit = this.#calculatedWidth / scaleMax;

    this._indicator.style.inlineSize = `${Math.abs(this.value) * perUnit}px`;
    this._indicator.style.translate = this.value > 0 ? '-100%' : '0';
  }

  /**
   * Measures the rendered graduation width, since the token can change it.
   * Taken across the whole strip to avoid rounding error.
   */
  #measureGraduations(): void {
    const count = this.#graduations().length;
    const width = this._list.getBoundingClientRect().width / count;

    if (width > 0) {
      // (n - 1) because each border sits at the start of its own box.
      this.#calculatedWidth = (count - 1) * width;
    }
  }

  /** The strip's on-screen offset, which may be mid-transition. */
  #currentOffset(): number {
    const {transform} = getComputedStyle(this._list);

    // Before the first reposition, and whenever a stylesheet hasn't applied.
    if (!transform || transform === 'none') {
      return 0;
    }

    return new DOMMatrixReadOnly(transform).m41;
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

  /** Sets the value and emits `input`. Returns whether the value moved. */
  #setValue(rawValue: number): boolean {
    const value = Math.min(Math.max(rawValue, this.min), this.max);

    // A move that lands on the value already showing shouldn't ask a consumer
    // to redraw for it.
    if (value === this.value) {
      return false;
    }

    this.value = value;
    this.dispatchEvent(new Event('input', {bubbles: true, composed: true}));

    return true;
  }

  #emitChange(): void {
    this.dispatchEvent(new Event('change', {bubbles: true, composed: true}));
  }

  #emitDrag(type: 'craft-drag-start' | 'craft-drag-end'): void {
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
      this.#editable &&
      this._graduations.contains(event.target as Node) &&
      event.target !== this._graduations;

    if (!this.#rotateIntent) {
      return;
    }

    event.preventDefault();
    this.#startPositionX = event.clientX;
    this.#startLeft = this.#currentOffset();
    this.#valueAtDragStart = this.value;
    this._root.setPointerCapture(event.pointerId);
    this.#emitDrag('craft-drag-start');
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

    if (this.value !== this.#valueAtDragStart) {
      this.#emitChange();
    }

    this.#emitDrag('craft-drag-end');
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
    if (!this.#editable) {
      return;
    }

    const current = this.value;
    let moved: boolean;

    switch (event.key) {
      case 'ArrowUp':
      case 'ArrowRight':
        moved = this.#setValue(current + 1);
        break;
      case 'ArrowDown':
      case 'ArrowLeft':
        moved = this.#setValue(current - 1);
        break;
      case 'PageUp':
        moved = this.#setValue(current + 10);
        break;
      case 'PageDown':
        moved = this.#setValue(current - 10);
        break;
      case 'Home':
        moved = this.#setValue(this.min);
        break;
      case 'End':
        moved = this.#setValue(this.max);
        break;
      default:
        return;
    }

    // A keyboard step commits as it moves, the way a native range input does.
    if (moved) {
      this.#emitChange();
    }

    event.preventDefault();
  }

  override render() {
    return html`
      <div
        class="slide-rule"
        role="slider"
        tabindex=${this.#editable ? 0 : -1}
        aria-label=${this.label}
        aria-valuemin=${this.min}
        aria-valuemax=${this.max}
        aria-valuenow=${this.value}
        aria-valuetext=${this.#valueText(this.value)}
        aria-disabled=${this.disabled ? 'true' : 'false'}
        @keydown=${this.#handleKeyDown}
        @pointerdown=${this.#handlePointerDown}
        @pointermove=${this.#handlePointerMove}
        @pointerup=${this.#handlePointerUp}
      >
        <div class="cursor"></div>
        <div class="graduations">
          <div
            class="indicator"
            aria-hidden="true"
            ?hidden=${this.value === 0}
          ></div>
          <ul aria-hidden="true">
            ${this.#graduations().map(
              (graduation) => html`
                <li
                  class=${classMap({
                    graduation: true,
                    'main-graduation': graduation % 5 === 0,
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
