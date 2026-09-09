import styles from './textarea.styles.js';
import {inputStyles} from '../../styles/form.styles.js';
import {LionTextarea} from '@lion/ui/textarea.js';
import {property} from 'lit/decorators.js';
import autosize from 'autosize';

export default class CraftTextarea extends LionTextarea {
  static override get styles() {
    return [...super.styles, inputStyles, styles];
  }

  @property({type: Boolean, reflect: true})
  monospace: boolean = false;

  #resizeObserver?: ResizeObserver;
  #styleObserver?: MutationObserver;
  #lastAutosizeHeight: string | null = null;

  override connectedCallback() {
    super.connectedCallback();
    this.#watchForManualResize();
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this.#resizeObserver?.disconnect();
    this.#resizeObserver = undefined;
    this.#styleObserver?.disconnect();
    this.#styleObserver = undefined;
  }

  /**
   * `autosize` (used by `LionTextarea`) auto-grows the textarea and disables
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
    // Narrowed from Lion's general form-control union — always the textarea
    // itself for CraftTextarea.
    const input = this._inputNode as HTMLTextAreaElement | undefined;
    if (
      !input ||
      typeof ResizeObserver === 'undefined' ||
      typeof MutationObserver === 'undefined'
    ) {
      return;
    }

    // autosize already ran once, synchronously, inside super.connectedCallback()
    // — seed our baseline from it and undo its first resize:none stamp.
    this.#lastAutosizeHeight = input.style.height;
    input.style.removeProperty('resize');

    input.addEventListener('autosize:resized', () => {
      this.#lastAutosizeHeight = input.style.height;
    });

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
