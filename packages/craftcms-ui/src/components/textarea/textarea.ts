import styles from './textarea.styles.js';
import {inputStyles} from '../../styles/form.styles.js';
import {css, LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import autosize from 'autosize';
import {formControlStyles, type SlotFactory} from '@src/mixins/FormControl';
import {NativeField} from '@src/mixins/NativeField';

/**
 * @summary A multi-line text input, with the same label and help text as
 * `craft-input`. It grows with its content, up to `max-rows`, until it's
 * resized by hand.
 *
 * Use it where the value is genuinely prose — a description, a note. A
 * textarea for something short trains people to expect more room than the
 * value needs.
 *
 * @slot label - The field's label, as an alternative to the `label` attribute.
 * @slot help-text - Guidance shown below the label.
 * @slot feedback - Validation messages.
 */
export default class CraftTextarea extends NativeField(LitElement) {
  static override styles = [
    formControlStyles,
    css`
      .input-group__container > .input-group__input ::slotted(.form-control) {
        box-sizing: content-box;
        /* Keeps Firefox from reserving height for a horizontal scrollbar. */
        overflow-x: hidden;
      }

      :host([disabled]) ::slotted(textarea) {
        user-select: none;
      }
    `,
    inputStyles,
    styles,
  ];

  /** Rows shown before it grows. */
  @property({type: Number, reflect: true}) rows = 2;

  /**
   * Rows it grows to before scrolling. Set `max-rows="none"` (or `Infinity`)
   * to let it grow without limit.
   */
  @property({
    attribute: 'max-rows',
    converter: {
      fromAttribute: (value: string | null) =>
        value === 'none' ? Infinity : value === null ? 30 : Number(value),
    },
  })
  maxRows = 30;

  /** Creates the native textarea when none is slotted. */
  override get slots(): Record<string, SlotFactory> {
    return {
      ...super.slots,
      input: () => document.createElement('textarea'),
    };
  }

  override get _inputNode(): HTMLTextAreaElement | undefined {
    return super._inputNode as HTMLTextAreaElement | undefined;
  }

  /** Renders the value in a monospace font, for code or structured text. */
  @property({type: Boolean, reflect: true})
  monospace: boolean = false;

  #resizeObserver?: ResizeObserver;
  #styleObserver?: MutationObserver;
  #intersectionObserver?: IntersectionObserver;
  #lastAutosizeHeight: string | null = null;

  override connectedCallback() {
    super.connectedCallback();

    const input = this._inputNode;
    if (input) {
      autosize(input);
      // Grows once it's laid out, which it may not be yet.
      this.#intersectionObserver = new IntersectionObserver(() =>
        this.resizeTextarea()
      );
      this.#intersectionObserver.observe(this);
    }

    this.#watchForManualResize();
  }

  protected override _pushToControl(changed: PropertyValues): void {
    super._pushToControl(changed);

    if (changed.has('rows') && this._inputNode) {
      this._inputNode.rows = this.rows;
    }
  }

  override updated(changed: PropertyValues) {
    super.updated(changed);

    if (changed.has('value')) {
      this.resizeTextarea();
    }

    if (changed.has('rows') || changed.has('maxRows')) {
      this.#setMaxHeight();
    }
  }

  /** Re-measures the height for the current value. */
  resizeTextarea(): void {
    if (this._inputNode) {
      autosize.update(this._inputNode);
    }
  }

  /** Caps the height at `max-rows`, measured from an empty textarea. */
  #setMaxHeight(): void {
    const input = this._inputNode;
    if (!input) {
      return;
    }

    if (!Number.isFinite(this.maxRows)) {
      input.style.removeProperty('max-height');
      return;
    }

    const {value} = input;
    input.value = '';
    this.resizeTextarea();

    const cs = getComputedStyle(input);
    const lineHeight =
      parseFloat(cs.lineHeight) || parseFloat(cs.height) / this.rows;
    const padding = parseFloat(cs.paddingTop) + parseFloat(cs.paddingBottom);
    const border =
      parseFloat(cs.borderTopWidth) + parseFloat(cs.borderBottomWidth);
    const offset = cs.boxSizing === 'border-box' ? padding + border : 0;

    input.style.maxHeight = `${lineHeight * this.maxRows + offset}px`;
    input.value = value;
    this.resizeTextarea();
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this.#intersectionObserver?.disconnect();
    this.#intersectionObserver = undefined;
    if (this._inputNode) {
      autosize.destroy(this._inputNode);
    }
    this.#resizeObserver?.disconnect();
    this.#resizeObserver = undefined;
    this.#styleObserver?.disconnect();
    this.#styleObserver = undefined;
    this._inputNode?.removeEventListener(
      'autosize:resized',
      this.#onAutosizeResized
    );
  }

  #onAutosizeResized = (event: Event) => {
    this.#lastAutosizeHeight = (
      event.target as HTMLTextAreaElement
    ).style.height;
  };

  /**
   * `autosize` auto-grows the textarea and disables
   * native vertical resize on every recalculation, to avoid fighting a
   * manual drag. We want the opposite: auto-grow stays on until the user
   * manually resizes, then `autosize` is destroyed for good so their choice
   * sticks.
   *
   * - `#lastAutosizeHeight` must only be set from `autosize`'s own
   *   `autosize:resized` event, never from the `MutationObserver` below —
   *   otherwise a manual resize would resync the baseline before the
   *   `ResizeObserver` can compare against it, and the drag would be undone
   *   on the very next keystroke.
   * - `autosize` also re-stamps `resize: none` on updates that don't fire
   *   `autosize:resized` (e.g. the initial `IntersectionObserver` check),
   *   so we watch the `style` attribute directly instead of relying on that
   *   event for cleanup.
   * - `autosize.destroy()` resets `height` to empty and leaves a stale
   *   `overflow` behind (a shorthand/longhand mismatch in its own code) —
   *   both are fixed up manually right after destroying.
   */
  #watchForManualResize() {
    const input = this._inputNode;
    if (
      !input ||
      typeof ResizeObserver === 'undefined' ||
      typeof MutationObserver === 'undefined'
    ) {
      return;
    }

    // autosize already ran once, synchronously, in connectedCallback() — seed
    // our baseline from it and undo its first resize:none stamp.
    this.#lastAutosizeHeight = input.style.height;
    input.style.removeProperty('resize');

    input.addEventListener('autosize:resized', this.#onAutosizeResized);

    this.#styleObserver = new MutationObserver(() => {
      // Skip if already clear, so our own removeProperty call below doesn't
      // retrigger this observer.
      if (input.style.resize !== '') {
        input.style.removeProperty('resize');
      }
    });
    this.#styleObserver.observe(input, {
      attributes: true,
      attributeFilter: ['style'],
    });

    this.#resizeObserver = new ResizeObserver(() => {
      if (input.style.height === this.#lastAutosizeHeight) {
        return;
      }

      // A manual resize — hand control to the user permanently.
      const manualHeight = input.style.height;
      autosize.destroy(input);
      input.style.height = manualHeight; // destroy() clears this; restore it
      input.style.removeProperty('overflow'); // destroy() leaves this stale

      this.#styleObserver?.disconnect();
      this.#styleObserver = undefined;
      this.#resizeObserver?.disconnect();
      this.#resizeObserver = undefined;
    });
    this.#resizeObserver.observe(input);
  }
}

if (!customElements.get('craft-textarea')) {
  customElements.define('craft-textarea', CraftTextarea);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-textarea': CraftTextarea;
  }
}
