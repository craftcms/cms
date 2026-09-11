import {html, LitElement, type PropertyValues} from 'lit';
import {property, query} from 'lit/decorators.js';
import {classMap} from 'lit/directives/class-map.js';
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
  @query('.indicator') private _indicator!: HTMLElement;

  #dragging = false;
  #rotateIntent = false;
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
    // The strip is centred against the width of its window, so an unmeasurable
    // window puts it half its own length out — 20 degrees off for the default
    // range. That happens whenever the rule first renders inside something not
    // yet laid out, a closed dialog being the usual case. The resize observer
    // calls back the moment there is a size, so waiting costs nothing.
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
   * Lets the strip and the indicator start animating, once they have been put
   * where they belong.
   *
   * Neither resting place is something to animate into. `left` never did,
   * because a transition can't run from `auto` to a length -- but `none` to a
   * matrix interpolates fine, so both would slide into place every time the
   * rule first appears. The stylesheet holds their transitions off until this
   * class lands, a frame later.
   */
  #markPlaced(): void {
    if (this.#placed) {
      return;
    }

    this.#placed = true;
    requestAnimationFrame(() => this._root?.classList.add('placed'));
  }

  /**
   * Sizes the band running from zero to the current value.
   *
   * The graduations can't carry this themselves: the value is continuous, so
   * it usually falls between two of them and there is no mark to light up.
   * The band is measured in the same pixels-per-unit the strip is positioned
   * in, so it lands exactly on a fractional value.
   *
   * It grows from the middle of the window, because that is where the cursor
   * is and so where the current value always sits. Zero is however far away
   * the value says it is -- to the left once the value goes positive, since
   * that is the direction the strip slides.
   */
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
   * Reads the rendered graduation width instead of assuming the default.
   *
   * The positioning maths is in units of one graduation, and
   * `--c-slide-rule-graduation-width` can be set to anything — so a strip that
   * doesn't match what the maths assumes puts zero nowhere near the cursor.
   * That is the shape of two bugs already: graduations rendered as
   * inline-blocks picked up the template's newlines as whitespace, and an
   * unmeasurable window centred the strip against zero.
   *
   * Measured off the strip rather than one graduation, so a fractional width
   * doesn't accumulate a rounding error across the whole range.
   */
  #measureGraduations(): void {
    const count = this.#graduations().length;
    const width = this._list.getBoundingClientRect().width / count;

    if (width > 0) {
      // (n - 1) because each border sits at the start of its own box.
      this.#calculatedWidth = (count - 1) * width;
    }
  }

  /**
   * Where the strip is sitting right now, read back off the transform.
   *
   * Not the value `#reposition()` last wrote: a drag can start while the
   * transition from a tap or a keypress is still running, and this has to be
   * the position on screen rather than the one being animated towards.
   */
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

  #setValue(rawValue: number, emitChange = true): void {
    const value = Math.min(Math.max(rawValue, this.min), this.max);

    // A move that lands on the value already showing shouldn't ask a consumer
    // to redraw for it.
    if (value === this.value) {
      return;
    }

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
    this.#startLeft = this.#currentOffset();
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
