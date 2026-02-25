import type {PropertyValues} from 'lit';
import {html, LitElement, nothing} from 'lit';
import {property} from 'lit/decorators.js';
import {classMap} from 'lit/directives/class-map.js';
import {styleMap} from 'lit/directives/style-map.js';
import styles from './progress-bar.styles';

/**
 * A linear progress bar component for displaying task progress.
 *
 * The progress bar supports two modes:
 *
 * 1. **Percentage mode**: Set the `progress` property directly (0-100)
 * 2. **Item count mode**: Set `total` and `processed` properties, and the
 *    progress percentage is calculated automatically
 *
 * @summary Displays progress as a horizontal bar
 *
 * @example Basic usage with percentage
 * ```html
 * <craft-progress-bar progress="50"></craft-progress-bar>
 * ```
 *
 * @example Item count mode with status text
 * ```html
 * <craft-progress-bar
 *   total="100"
 *   processed="25"
 *   show-status
 * ></craft-progress-bar>
 * ```
 *
 * @example Indeterminate/pending state
 * ```html
 * <craft-progress-bar pending></craft-progress-bar>
 * ```
 *
 * @example Animated transitions
 * ```html
 * <craft-progress-bar progress="75" smooth></craft-progress-bar>
 * ```
 *
 * @event complete - Fired when progress reaches 100% (only when using item count mode)
 *
 * @csspart track - The background track of the progress bar
 * @csspart fill - The filled portion indicating progress
 * @csspart status - The status text container (when show-status is true)
 *
 * @cssprop [--c-progress-bar-height=0.5rem] - Height of the progress bar
 * @cssprop [--c-progress-bar-radius=var(--c-radius-sm)] - Border radius
 * @cssprop [--c-progress-bar-track-color=var(--c-color-neutral-bg-subtle)] - Track background color
 * @cssprop [--c-progress-bar-fill-color=var(--c-color-primary-bg)] - Fill color
 * @cssprop [--c-progress-bar-pending-color=var(--c-color-neutral-bg-hover)] - Pending stripe color
 */
export default class CraftProgressBar extends LitElement {
  static override styles = [styles];

  /**
   * Progress percentage (0-100). When using item count mode (total/processed),
   * this is calculated automatically.
   */
  @property({type: Number}) progress: number = 0;

  /** Total number of items to process (for item count mode) */
  @property({type: Number}) total: number = 0;

  /** Number of items processed so far (for item count mode) */
  @property({type: Number}) processed: number = 0;

  /** Whether to show the status text (e.g., "25 / 100") */
  @property({type: Boolean, attribute: 'show-status'}) showStatus: boolean =
    false;

  /** Whether the progress bar is in a pending/indeterminate state */
  @property({type: Boolean, reflect: true}) pending: boolean = false;

  /** Whether to smoothly transition between progress changes */
  @property({type: Boolean}) smooth: boolean = false;

  /** Accessible label for the progress bar */
  @property({type: String}) label: string = 'Progress';

  #lastProgress: number = 0;

  protected override updated(changedProperties: PropertyValues): void {
    // Update progress from item counts when they change
    if (changedProperties.has('total') || changedProperties.has('processed')) {
      if (this.total > 0) {
        const newProgress = Math.min(
          100,
          Math.round((this.processed / this.total) * 100)
        );

        // Check if we just reached 100%
        if (newProgress >= 100 && this.#lastProgress < 100) {
          this.dispatchEvent(
            new CustomEvent('complete', {bubbles: true, composed: true})
          );
        }

        this.progress = newProgress;
      }
    }

    if (changedProperties.has('progress')) {
      // Remove pending state when progress is set
      if (this.progress > 0 && this.pending) {
        this.pending = false;
      }
      this.#lastProgress = this.progress;
    }
  }

  /** Gets the current progress percentage */
  get progressPercent(): number {
    return Math.min(100, Math.max(0, this.progress));
  }

  /** Gets the status text (e.g., "25 / 100") */
  get statusText(): string {
    if (this.total > 0) {
      return `${this.processed} / ${this.total}`;
    }
    return `${this.progressPercent}%`;
  }

  /** Resets the progress bar to its initial state */
  reset(): void {
    this.progress = 0;
    this.processed = 0;
    this.pending = true;
    this.#lastProgress = 0;
  }

  /** Shows the progress bar (removes hidden attribute) */
  show(): void {
    this.hidden = false;
  }

  /** Hides the progress bar (sets hidden attribute) */
  hide(): void {
    this.hidden = true;
  }

  protected override render() {
    const fillStyles = {
      width: this.pending ? '100%' : `${this.progressPercent}%`,
    };

    return html`
      <div
        class=${classMap({
          'progress-bar': true,
          'progress-bar--pending': this.pending,
        })}
        part="track"
        role="progressbar"
        aria-valuenow=${this.pending ? nothing : this.progressPercent}
        aria-valuemin="0"
        aria-valuemax="100"
        aria-label=${this.label}
      >
        <div
          class=${classMap({
            'progress-bar__fill': true,
            'progress-bar__fill--smooth': this.smooth && !this.pending,
          })}
          part="fill"
          style=${styleMap(fillStyles)}
        ></div>
      </div>
      ${this.showStatus
        ? html`<div class="progress-bar__status" part="status">
            ${this.statusText}
          </div>`
        : nothing}
      <span class="visually-hidden">
        ${this.pending ? 'Loading' : `${this.progressPercent}%`}
      </span>
    `;
  }
}

if (!customElements.get('craft-progress-bar')) {
  customElements.define('craft-progress-bar', CraftProgressBar);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-progress-bar': CraftProgressBar;
  }
}
