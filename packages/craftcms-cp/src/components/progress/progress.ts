import type {PropertyValues} from 'lit';
import {css, html, LitElement} from 'lit';
import {property, query, state} from 'lit/decorators.js';

/**
 * A circular progress indicator that displays progress as an arc.
 *
 * @summary Displays progress in radial form
 *
 * @csspart bg - The background arc canvas
 * @csspart static - The static progress arc canvas
 * @csspart hover - The hover state progress arc canvas
 * @csspart fail - The fail state arc canvas
 */
export default class CraftProgress extends LitElement {
  static override styles = css`
    :host {
      display: inline-block;
      position: relative;
      width: 18px;
      height: 18px;
    }

    canvas {
      position: absolute;
      top: 0;
      left: 0;
      width: 18px;
      height: 18px;
    }

    .hidden {
      display: none;
    }
  `;

  /** Progress of the indicator, a percentage between 0 and 100 */
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

  @query('#bg') private _bgCanvas!: HTMLCanvasElement;
  @query('#static') private _staticCanvas!: HTMLCanvasElement;
  @query('#hover') private _hoverCanvas!: HTMLCanvasElement;
  @query('#fail') private _failCanvas!: HTMLCanvasElement;

  @state() private _showStatic: boolean = false;
  @state() private _showHover: boolean = false;

  private _canvasSize: number = 0;
  private _arcPos: number = 0;
  private _arcRadius: number = 0;
  private _lineWidth: number = 0;

  private _arcStartPos: number = 0;
  private _arcEndPos: number = 0;
  private _arcStartStepSize: number = 0;
  private _arcEndStepSize: number = 0;
  private _arcStep: number = 0;
  private _arcStepTimeout: number | null = null;
  private _arcAnimateCallback: (() => void) | null = null;

  private _lastProgress: number = 0;
  private _initialized: boolean = false;

  override connectedCallback(): void {
    super.connectedCallback();
    const m = window.devicePixelRatio > 1 ? 2 : 1;
    this._canvasSize = 18 * m;
    this._arcPos = this._canvasSize / 2;
    this._arcRadius = 7 * m;
    this._lineWidth = 3 * m;
  }

  override disconnectedCallback(): void {
    super.disconnectedCallback();
    if (this._arcStepTimeout) {
      clearTimeout(this._arcStepTimeout);
      this._arcStepTimeout = null;
    }
  }

  protected override firstUpdated(): void {
    this._initCanvas(this._bgCanvas, this.bgColor);
    this._initCanvas(this._staticCanvas, this.color);
    this._initCanvas(this._hoverCanvas, this.color);
    this._initCanvas(this._failCanvas, this.failColor);

    // Draw full background arc
    this._drawArc(this._bgCanvas.getContext('2d')!, 0, 1);
    this._drawArc(this._failCanvas.getContext('2d')!, 0, 1);

    this._initialized = true;
    this._updateProgress();
  }

  protected override updated(changedProperties: PropertyValues): void {
    if (changedProperties.has('progress') && this._initialized) {
      this._updateProgress();
    }
  }

  private _initCanvas(canvas: HTMLCanvasElement, color: string): void {
    canvas.width = this._canvasSize;
    canvas.height = this._canvasSize;
    const ctx = canvas.getContext('2d')!;
    ctx.strokeStyle = color;
    ctx.lineWidth = this._lineWidth;
    ctx.lineCap = 'round';
  }

  private _updateProgress(): void {
    if (this.progress === 0) {
      this._showStatic = false;
      this._showHover = false;
    } else {
      this._showStatic = true;
      this._showHover = true;
      if (this._lastProgress && this.progress > this._lastProgress) {
        this._animateArc(0, this.progress / 100);
      } else {
        this._setArc(0, this.progress / 100);
      }
    }
    this._lastProgress = this.progress;
  }

  private _setArc(startPos: number, endPos: number): void {
    this._arcStartPos = startPos;
    this._arcEndPos = endPos;

    this._drawArc(this._staticCanvas.getContext('2d')!, startPos, endPos);
    this._drawArc(this._hoverCanvas.getContext('2d')!, startPos, endPos);
  }

  private _drawArc(
    ctx: CanvasRenderingContext2D,
    startPos: number,
    endPos: number
  ): void {
    ctx.clearRect(0, 0, this._canvasSize, this._canvasSize);
    ctx.beginPath();
    ctx.arc(
      this._arcPos,
      this._arcPos,
      this._arcRadius,
      (1.5 + startPos * 2) * Math.PI,
      (1.5 + endPos * 2) * Math.PI
    );
    ctx.stroke();
    ctx.closePath();
  }

  private _animateArc(
    targetStartPos: number,
    targetEndPos: number,
    callback?: () => void
  ): void {
    if (this._arcStepTimeout) {
      clearTimeout(this._arcStepTimeout);
    }

    this._arcStep = 0;
    this._arcStartStepSize = (targetStartPos - this._arcStartPos) / 10;
    this._arcEndStepSize = (targetEndPos - this._arcEndPos) / 10;
    this._arcAnimateCallback = callback ?? null;
    this._takeNextArcStep();
  }

  private _takeNextArcStep(): void {
    this._setArc(
      this._arcStartPos + this._arcStartStepSize,
      this._arcEndPos + this._arcEndStepSize
    );

    this._arcStep++;

    if (this._arcStep < 10) {
      this._arcStepTimeout = window.setTimeout(
        () => this._takeNextArcStep(),
        50
      );
    } else if (this._arcAnimateCallback) {
      this._arcAnimateCallback();
    }
  }

  /** Animate progress to 100% and then fade out */
  complete(): Promise<void> {
    return new Promise((resolve) => {
      this._animateArc(0, 1, () => {
        this._bgCanvas.style.opacity = '0';
        this._bgCanvas.style.transition = 'opacity 0.4s';

        this._animateArc(1, 1, () => {
          resolve();
        });
      });
    });
  }

  protected override render() {
    return html`
      <canvas
        id="bg"
        class=${this.failed ? 'hidden' : ''}
        part="bg"
      ></canvas>
      <canvas
        id="static"
        class=${!this._showStatic || this.failed ? 'hidden' : ''}
        part="static"
      ></canvas>
      <canvas
        id="hover"
        class=${!this._showHover || this.failed ? 'hidden' : ''}
        part="hover"
      ></canvas>
      <canvas
        id="fail"
        class=${this.failed ? '' : 'hidden'}
        part="fail"
      ></canvas>
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
