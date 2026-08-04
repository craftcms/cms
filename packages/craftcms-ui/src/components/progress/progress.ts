import type {PropertyValues} from 'lit';
import {css, html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';

/**
 * A circular progress indicator that displays progress as an arc.
 *
 * @summary Displays progress in radial form
 *
 * @event complete - Fired when the completion animation finishes
 *
 * @csspart canvas - The canvas element
 *
 * @cssprop [--c-progress-size=18px] - The size of the progress indicator
 * @cssprop [--c-progress-stroke-width=3px] - The width of the progress arc stroke
 */
export default class CraftProgress extends LitElement {
  static override styles = css`
    :host {
      --_size: var(--c-progress-size, 16px);
      --_stroke-width: var(--c-progress-stroke-width, 3px);

      display: inline-block;
      position: relative;
      width: var(--_size);
      height: var(--_size);
    }

    canvas {
      position: absolute;
      top: 0;
      left: 0;
      width: var(--_size);
      height: var(--_size);
    }

    .visually-hidden {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
    }
  `;

  /** Progress of the indicator, a percentage between 0 and 100. Set to -1 for indeterminate. */
  @property({type: Number}) progress: number = 0;

  /** Whether the indicator is in fail mode */
  @property({type: Boolean}) failed: boolean = false;

  /** Color of the progress arc */
  @property({type: String}) color: string = 'currentColor';

  /** Color of the background arc */
  @property({type: String, attribute: 'bg-color'}) bgColor: string = '#a3afbb';

  /** Color of the fail arc */
  @property({type: String, attribute: 'fail-color'}) failColor: string =
    '#da5a47';

  /** Accessible label for the progress indicator */
  @property({type: String}) label: string = 'Progress';

  /** Whether to automatically run the complete animation when progress reaches 100 */
  @property({type: Boolean, attribute: 'auto-complete'}) autoComplete: boolean =
    false;

  #canvas: HTMLCanvasElement | null = null;
  #canvasSize: number = 0;
  #arcPos: number = 0;
  #arcRadius: number = 0;
  #lineWidth: number = 0;

  #arcEndPos: number = 0;
  #animationId: number | null = null;

  #indeterminateRotation: number = 0;
  #indeterminateAnimationId: number | null = null;

  #lastProgress: number = 0;
  #prefersReducedMotion: boolean = false;

  override connectedCallback(): void {
    super.connectedCallback();
    this.#prefersReducedMotion = window.matchMedia(
      '(prefers-reduced-motion: reduce)'
    ).matches;
  }

  override disconnectedCallback(): void {
    super.disconnectedCallback();
    this.#cancelAnimations();
  }

  protected override firstUpdated(): void {
    this.#canvas = this.renderRoot.querySelector('canvas');
    this.#updateCanvasSize();
    this.#handleProgressChange();
  }

  protected override updated(changedProperties: PropertyValues): void {
    if (changedProperties.has('progress')) {
      this.#handleProgressChange();
    } else if (
      changedProperties.has('color') ||
      changedProperties.has('bgColor') ||
      changedProperties.has('failColor') ||
      changedProperties.has('failed')
    ) {
      this.#draw();
    }
  }

  #updateCanvasSize(): void {
    const computedStyle = getComputedStyle(this);
    const size = parseFloat(computedStyle.getPropertyValue('--_size'));
    const strokeWidth = parseFloat(
      computedStyle.getPropertyValue('--_stroke-width')
    );

    const m = window.devicePixelRatio > 1 ? 2 : 1;
    this.#canvasSize = size * m;
    this.#arcPos = this.#canvasSize / 2;
    this.#lineWidth = strokeWidth * m;
    this.#arcRadius = (size / 2 - strokeWidth / 2) * m;

    if (this.#canvas) {
      this.#canvas.width = this.#canvasSize;
      this.#canvas.height = this.#canvasSize;
    }
  }

  #handleProgressChange(): void {
    // Stop indeterminate animation if switching to determinate
    if (this.progress >= 0 && this.#indeterminateAnimationId !== null) {
      cancelAnimationFrame(this.#indeterminateAnimationId);
      this.#indeterminateAnimationId = null;
      this.#indeterminateRotation = 0;
    }

    // Start indeterminate animation
    if (this.progress < 0) {
      if (this.#indeterminateAnimationId === null) {
        this.#startIndeterminateAnimation();
      }
      return;
    }

    const newEndPos = this.progress / 100;

    // Auto-complete when reaching 100
    if (this.autoComplete && this.progress >= 100 && this.#lastProgress < 100) {
      this.#lastProgress = this.progress;
      this.complete();
      return;
    }

    if (
      this.#lastProgress > 0 &&
      this.progress > this.#lastProgress &&
      !this.#prefersReducedMotion
    ) {
      this.#animateArc(newEndPos);
    } else {
      this.#arcEndPos = newEndPos;
      this.#draw();
    }

    this.#lastProgress = this.progress;
  }

  #startIndeterminateAnimation(): void {
    if (this.#prefersReducedMotion) {
      this.#arcEndPos = 0.25;
      this.#draw();
      return;
    }

    const animate = () => {
      this.#indeterminateRotation += 0.05;
      this.#arcEndPos = 0.15 + 0.1 * Math.sin(this.#indeterminateRotation * 3);
      this.#draw();
      this.#indeterminateAnimationId = requestAnimationFrame(animate);
    };

    this.#indeterminateAnimationId = requestAnimationFrame(animate);
  }

  #draw(): void {
    const ctx = this.#canvas?.getContext('2d');
    if (!ctx) return;

    ctx.clearRect(0, 0, this.#canvasSize, this.#canvasSize);

    if (this.failed) {
      this.#drawArc(ctx, this.failColor, 1, 0);
      return;
    }

    // Background arc
    this.#drawArc(ctx, this.bgColor, 1, 0);

    // Progress arc
    if (this.#arcEndPos > 0) {
      const rotation =
        this.progress < 0 ? this.#indeterminateRotation : -Math.PI / 2;
      this.#drawArc(ctx, this.color, this.#arcEndPos, rotation);
    }
  }

  #drawArc(
    ctx: CanvasRenderingContext2D,
    color: string,
    endPos: number,
    rotationOffset: number
  ): void {
    ctx.strokeStyle = color;
    ctx.lineWidth = this.#lineWidth;
    ctx.lineCap = 'round';
    ctx.beginPath();
    ctx.arc(
      this.#arcPos,
      this.#arcPos,
      this.#arcRadius,
      rotationOffset,
      rotationOffset + endPos * 2 * Math.PI
    );
    ctx.stroke();
  }

  #animateArc(targetEndPos: number, callback?: () => void): void {
    this.#cancelAnimations();

    const startTime = performance.now();
    const duration = 500;
    const initialEndPos = this.#arcEndPos;

    const animate = (currentTime: number) => {
      const elapsed = currentTime - startTime;
      const t = Math.min(elapsed / duration, 1);
      const eased = 1 - Math.pow(1 - t, 3); // ease-out cubic

      this.#arcEndPos = initialEndPos + (targetEndPos - initialEndPos) * eased;
      this.#draw();

      if (t < 1) {
        this.#animationId = requestAnimationFrame(animate);
      } else {
        this.#animationId = null;
        callback?.();
      }
    };

    this.#animationId = requestAnimationFrame(animate);
  }

  #cancelAnimations(): void {
    if (this.#animationId !== null) {
      cancelAnimationFrame(this.#animationId);
      this.#animationId = null;
    }
    if (this.#indeterminateAnimationId !== null) {
      cancelAnimationFrame(this.#indeterminateAnimationId);
      this.#indeterminateAnimationId = null;
    }
  }

  /** The canvas element, exposed for subclasses */
  protected get canvas(): HTMLCanvasElement | null {
    return this.#canvas;
  }

  /** Whether the user prefers reduced motion, exposed for subclasses */
  protected get prefersReducedMotion(): boolean {
    return this.#prefersReducedMotion;
  }

  /**
   * Runs the complete animation. Override this method to customize the animation.
   * The default animation fills to 100% and fades out.
   */
  protected runCompleteAnimation(): Promise<void> {
    return new Promise((resolve) => {
      if (this.#prefersReducedMotion) {
        this.#arcEndPos = 1;
        if (this.#canvas) {
          this.#canvas.style.opacity = '0';
        }
        this.#draw();
        resolve();
        return;
      }

      this.#animateArc(1, () => {
        if (this.#canvas) {
          this.#canvas.style.transition = 'opacity 0.4s';
          this.#canvas.style.opacity = '0';
        }
        setTimeout(resolve, 400);
      });
    });
  }

  /** Triggers the complete animation and dispatches the complete event */
  async complete(): Promise<void> {
    await this.runCompleteAnimation();
    this.dispatchEvent(
      new CustomEvent('complete', {bubbles: true, composed: true})
    );
  }

  protected override render() {
    const ariaValueNow = this.progress >= 0 ? this.progress : undefined;

    return html`
      <canvas
        part="canvas"
        role="progressbar"
        aria-valuenow=${ariaValueNow ?? ''}
        aria-valuemin="0"
        aria-valuemax="100"
        aria-label=${this.label}
      ></canvas>
      <span class="visually-hidden">
        ${this.failed
          ? 'Failed'
          : this.progress < 0
            ? 'Loading'
            : `${this.progress}%`}
      </span>
    `;
  }
}

if (!customElements.get('craft-progress')) {
  customElements.define('craft-progress', CraftProgress);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-progress': CraftProgress;
  }
}
