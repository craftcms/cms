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
   * `LionTextarea` uses the `autosize` package to keep the textarea's height
   * in sync with its content. To avoid fighting a user's own drag-resize,
   * `autosize` disables the native vertical resize handle (downgrading
   * `resize: vertical`/`both` to `none`/`horizontal` via an inline style) on
   * every height recalculation — which is what `textarea.styles.ts`'s
   * `resize: vertical` is fighting against.
   *
   * We want the opposite default: let auto-grow run until the user manually
   * resizes, then hand control to them permanently for that instance (the
   * common "auto-grow until you take over" pattern).
   *
   * A `ResizeObserver` can't tell "autosize changed the height" apart from
   * "the user dragged the resize handle" on its own — both produce a size
   * change. We disambiguate by comparing the observed height against the
   * height `autosize` itself last wrote, tracked via `#lastAutosizeHeight`.
   * That tracking must come *exclusively* from autosize's own
   * `autosize:resized` event — the one signal that's reliably "autosize did
   * this" and nothing else. It must never be updated from the
   * `MutationObserver` below, even though that observer also sees every
   * height change: it can't tell autosize's mutations apart from a manual
   * one, so folding it into the same baseline would silently resync
   * `#lastAutosizeHeight` to match a manual resize too, right before the
   * `ResizeObserver` gets a chance to compare against it — which defeats
   * the whole comparison (this happened; a manual shrink briefly appeared
   * to work, but the next keystroke immediately snapped the height back,
   * because `autosize.destroy()` was never actually reached).
   *
   * Separately, we need to keep undoing the `resize: none` downgrade
   * itself: `autosize` re-checks `computed.resize` and re-applies it on
   * *every* recalculation it does, not just the first one. That includes
   * several paths besides typing — its own initial synchronous run inside
   * `super.connectedCallback()` (the stamp visible on page load, before any
   * user interaction), and, notably, the `IntersectionObserver` that
   * `LionTextarea.connectedCallback()` sets up right after — it always
   * fires once automatically on `observe()`, which calls `resizeTextarea()`
   * and re-triggers the downgrade check even though nothing actually
   * changed. That case computes the *same* height as before, so `autosize`
   * doesn't dispatch `autosize:resized` for it (that event is conditional
   * on the height actually changing) — the event alone would silently miss
   * it. A `MutationObserver` on the `style` attribute instead reacts to
   * every inline mutation unconditionally, however it happened, so nothing
   * slips past it — but for that same reason, it must stay purely a
   * cleanup mechanism and never feed the manual-resize baseline above.
   */
  #watchForManualResize() {
    // `_inputNode` is inherited from Lion's FormControlMixin, typed there as
    // the general form-control union — narrow it, since for CraftTextarea
    // it's always the textarea itself.
    const input = this._inputNode as HTMLTextAreaElement | undefined;
    if (
      !input ||
      typeof ResizeObserver === 'undefined' ||
      typeof MutationObserver === 'undefined'
    ) {
      return;
    }

    // Seed the baseline from whatever autosize already applied during its
    // synchronous initial run inside super.connectedCallback(), and clean
    // up that first `resize: none` stamp immediately — both happen before
    // this method can attach the listener below, so they'd otherwise be
    // missed entirely (see the class-level doc comment).
    this.#lastAutosizeHeight = input.style.height;
    input.style.removeProperty('resize');

    input.addEventListener('autosize:resized', () => {
      this.#lastAutosizeHeight = input.style.height;
    });

    this.#styleObserver = new MutationObserver(() => {
      // Guard against reacting to our own `removeProperty` call below —
      // once `resize` is already cleared, this is a no-op, so the mutation
      // it would otherwise cause (and the observer callback it would
      // trigger) never happens.
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

      // The user dragged the native resize handle — let them keep the
      // height they chose instead of autosize overwriting it on the next
      // keystroke.
      const manualHeight = input.style.height;
      autosize.destroy(input);
      // destroy() restores `height` (among other properties) to whatever it
      // was *before* autosize's very first run — always empty here, since
      // nothing else sets an inline height — which would otherwise snap the
      // box back down to its unstyled size right after the user lets go of
      // the handle. Put the height they just chose back.
      input.style.height = manualHeight;

      // autosize's own destroy() doesn't fully clean up after itself: its
      // internal setHeight() sets the *shorthand* `overflow` inline (to
      // `scroll` once content is clamped at max-height, or `hidden` while
      // everything still fits without it), but destroy()'s restore snapshot
      // only tracks the *longhand* `overflowY`/`overflowX`, so it never
      // touches the shorthand it actually set. Left alone, a stale
      // `overflow: hidden` would block scrolling to any content that
      // overflows once the user shrinks the textarea below its natural
      // height. There's no stylesheet rule for `overflow` here, so clearing
      // it hands the textarea back to its native scrollable default.
      input.style.removeProperty('overflow');

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
