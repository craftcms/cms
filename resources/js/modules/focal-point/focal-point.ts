import './focal.scss';
import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {t} from '@craftcms/ui/utilities/translate';
import {announce} from '@/common/announcer';
import {refreshPreviews} from '@/modules/elements/live-preview/previews';
import {contentSize} from '@/common/utils/dom';

export class FocalPoint {
  private readonly target: HTMLElement;
  private readonly focal: HTMLElement;
  private readonly button: HTMLElement | null;
  private readonly focalId: string;
  private readonly movable: boolean;

  private visible = false;
  private dragging = false;
  private saving = false;
  private shouldSave = false;

  private focalPos: [number, number];
  private assetUid: string;
  private lastX = 0;
  private lastY = 0;

  private debounceTimer?: number;
  private debounceTimeout = 1500;

  readonly #onDragStart = (ev: PointerEvent) => this.handleDragStart(ev);
  readonly #onDragEnd = () => this.handleDragEnd();
  readonly #onMove = (ev: PointerEvent) => this.handleMove(ev);

  constructor(
    target: HTMLElement,
    button: HTMLElement | null,
    movable: boolean
  ) {
    this.focalId = 'focal-point-' + Math.floor(Math.random() * 99999);
    this.target = target;
    this.focal = document.createElement('div');
    this.focal.className = 'preview-focal-point';
    this.focal.id = this.focalId;
    this.focal.innerHTML = '<div class="inner"></div>';
    this.focal.style.display = 'none';
    this.target.append(this.focal);

    const img = this.target.querySelector('img');
    const initialPosition = img?.dataset.focal ?? '0.5;0.5';
    const [x = 0.5, y = 0.5] = initialPosition.split(';').map(Number);
    this.focalPos = [x, y];
    this.assetUid = img?.dataset.uid ?? '';

    this.visible = !this.isCentered();
    this.movable = movable;
    this.button = button;

    if (this.movable) {
      this.button?.addEventListener('click', () => {
        this.toggleFocal();
      });

      this.addFocalMoveListeners();
    } else {
      this.focal.style.cursor = 'auto';
    }

    this.renderFocal();
  }

  protected addFocalMoveListeners() {
    // Pointer events cover touch too, once the browser stops panning on it
    this.focal.style.touchAction = 'none';
    this.focal.addEventListener('pointerdown', this.#onDragStart);
    window.addEventListener('pointerup', this.#onDragEnd);
    window.addEventListener('pointercancel', this.#onDragEnd);
    window.addEventListener('pointermove', this.#onMove);
  }

  public destruct() {
    if (this.debounceTimer) {
      this.storeFocalPosition();
    }

    this.focal.removeEventListener('pointerdown', this.#onDragStart);
    window.removeEventListener('pointerup', this.#onDragEnd);
    window.removeEventListener('pointercancel', this.#onDragEnd);
    window.removeEventListener('pointermove', this.#onMove);
  }

  public handleDragStart(ev: PointerEvent) {
    this.dragging = this.movable;
    this.lastX = ev.clientX;
    this.lastY = ev.clientY;
  }

  protected isCentered() {
    return this.focalPos[0] === 0.5 && this.focalPos[1] === 0.5;
  }

  protected handleDragEnd() {
    this.dragging = false;
  }

  protected handleMove(ev: PointerEvent) {
    if (!this.dragging || this.saving) {
      return;
    }

    ev.preventDefault();

    const deltaX = ev.clientX - this.lastX;
    const deltaY = ev.clientY - this.lastY;

    if (deltaY !== 0 || deltaX !== 0) {
      this.shouldSave = true;
    }

    // Update last position
    this.lastX = ev.clientX;
    this.lastY = ev.clientY;

    // Moved from where the focal point is, rather than where the marker is
    // measured to be: in a dialog, the marker's offset parent isn't the
    // element it's positioned in, so measuring it added the dialog's offset
    // on every move.
    const {width: containerWidth, height: containerHeight} = contentSize(
      this.target
    );
    const desiredLeft = containerWidth * this.focalPos[0] + deltaX;
    const desiredTop = containerHeight * this.focalPos[1] + deltaY;

    // Make it fit the actual container
    const actualLeft = Math.min(Math.max(0, desiredLeft), containerWidth);
    const actualTop = Math.min(Math.max(0, desiredTop), containerHeight);

    // convert to percentage, as that's going to be reliable after resizing, too
    this.focalPos = [actualLeft / containerWidth, actualTop / containerHeight];

    this.debouncedSave();
    this.positionFocal();
  }

  public debouncedSave() {
    if (this.saving) {
      return;
    }

    clearTimeout(this.debounceTimer);
    this.debounceTimer = window.setTimeout(
      () => this.storeFocalPosition(),
      this.debounceTimeout
    );
  }

  public storeFocalPosition() {
    this.saving = true;

    this.renderButton();
    announce(t('Saving…'));

    let data = {
      assetUid: this.assetUid,
      focal: (this.visible ? this.focalPos : [0.5, 0.5]).join(';'),
      focalEnabled: this.visible,
    };

    void actionClient.post('assets/update-focal-position', data).then(() => {
      this.saving = false;
      this.shouldSave = false;
      this.renderButton();
      announce(t('Saved'));
      refreshPreviews();
    });
  }

  public toggleFocal() {
    if (this.saving) {
      return;
    }

    this.visible = !this.visible;

    if (!this.isCentered()) {
      this.shouldSave = true;
    }

    this.renderButton();
    this.debouncedSave();
    this.renderFocal();
  }

  /** Presses the toggle button while the focal point is enabled. */
  protected renderButton() {
    if (this.button) {
      (this.button as HTMLElement & {active?: boolean}).active = this.visible;
    }
  }

  protected renderFocal() {
    this.focal.style.display = this.visible ? '' : 'none';
    if (this.visible) {
      this.positionFocal();
    }

    this.renderButton();
  }

  /**
   * Position focal point correctly
   * @protected
   */
  protected positionFocal() {
    const {width, height} = contentSize(this.target);
    this.focal.style.left = `${width * this.focalPos[0] - this.focal.offsetWidth / 2}px`;
    this.focal.style.top = `${height * this.focalPos[1] - this.focal.offsetHeight / 2}px`;
  }
}

let current: FocalPoint | null = null;

/** Starts the focal point editor on an image preview inside `root`, if it has one. */
export function bootFocalPoint(root: Element): void {
  const container = root.querySelector<HTMLElement>('[data-focal-point]');

  if (!container) {
    return;
  }

  current?.destruct();
  current = new FocalPoint(
    container,
    container.dataset.focalPointToggle
      ? document.getElementById(container.dataset.focalPointToggle)
      : null,
    container.dataset.focalPoint === 'edit'
  );
}
