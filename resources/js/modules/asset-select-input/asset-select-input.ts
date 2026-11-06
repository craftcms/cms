import {Uploader} from '@/modules/uploader/uploader';
import {UploadError} from '@/upload-client';
import type {UploaderCallbacks} from '@/modules/uploader/base-uploader';
import {BaseElementSelectInput} from '@/modules/element-select-input/base-element-select-input';
import {IntervalManager} from '@/modules/interval-manager/interval-manager';
import {openImageEditorDialog} from '@/modules/image-editor/open-image-editor-dialog';
import {PreviewFileModal} from '@/modules/preview-file-modal/preview-file-modal';
import '@craftcms/ui/components/progress-bar/progress-bar';
import type CraftProgressBar from '@craftcms/ui/components/progress-bar/progress-bar';
import './asset-select-input.css';
import {t} from '@craftcms/ui/utilities/translate';
import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {appendBodyHtml, appendHeadHtml} from '@craftcms/ui/utilities/dom';
import {showError} from '@/modules/messages/messages';
import {QueueService} from '@/modules/queue/queue';
import {announce} from '@/common/announcer';
import {getElementInfo} from '@/common/craft/element-markup';
import {SPACE_KEY} from '@craftcms/garnish';

/**
 * The Assets field's input: the base element select, plus uploads (a button,
 * drag and drop, and a progress bar read out to screen readers) and
 * Shift+Space to preview the focused asset.
 */
export class AssetSelectInput extends BaseElementSelectInput {
  uploadBtn: HTMLButtonElement | null = null;
  fileInput: HTMLInputElement | null = null;
  uploader: Uploader | null = null;
  progressBar: CraftProgressBar | null = null;
  #progressAnnouncer: IntervalManager | null = null;
  #progressShade: HTMLElement | null = null;

  constructor(
    settings?: ConstructorParameters<typeof BaseElementSelectInput>[0]
  ) {
    super(settings);
    if (new.target === AssetSelectInput) {
      this.init(settings);
    }
  }

  override init(
    settings?: ConstructorParameters<typeof BaseElementSelectInput>[0]
  ): void {
    super.init(settings);

    if (this.settings.canUpload) {
      this.#attachUploader();
    }

    this.updateAddElementsBtn();

    this.addListener(this.elementsContainer, 'keydown', (ev) => {
      if (
        ev instanceof KeyboardEvent &&
        ev.keyCode === SPACE_KEY &&
        ev.shiftKey
      ) {
        this.openPreview();
        ev.stopPropagation();
      }
    });
  }

  override destroy(): void {
    this.#progressAnnouncer?.stop();
    this.#progressAnnouncer = null;
    this.uploader?.destroy();
    this.uploader = null;
    this.uploadBtn?.remove();
    this.uploadBtn = null;
    this.fileInput?.remove();
    this.fileInput = null;
    this.#progressShade?.remove();
    this.#progressShade = null;
    this.progressBar = null;
    this.container.classList.remove('uploading');

    super.destroy();
  }

  /**
   * Opens the image editor for the chip's asset, and puts an image saved as a
   * new asset in its place.
   */
  protected override handleChipAction(
    chip: HTMLElement,
    name: string,
    detail: Record<string, any>
  ): boolean {
    const behavior = detail.behavior;
    if (name !== 'craft:element-action' || behavior?.type !== 'editImage') {
      return super.handleChipAction(chip, name, detail);
    }

    void openImageEditorDialog(behavior.settings, (result) => {
      if (result.newAssetId) {
        this.replaceElement(behavior.assetId, result.newAssetId).catch(
          (error) => showError(error?.message)
        );
      }
    });

    return true;
  }

  override elementSelectSettings(): Record<string, unknown> {
    return {...super.elementSelectSettings(), makeFocusable: true};
  }

  /** Toggles the preview of `element`, or of the focused asset. */
  openPreview(element?: HTMLElement): void {
    if (PreviewFileModal.openInstance) {
      PreviewFileModal.openInstance.hide();
      return;
    }

    const target =
      element ??
      this.elements.find((el) => el.contains(document.activeElement));

    if (target) {
      PreviewFileModal.showForAsset(target, this.elementSelect);
    }
  }

  #attachUploader(): void {
    const shade = document.createElement('div');
    shade.className = 'progress-shade';
    this.progressBar = document.createElement('craft-progress-bar');
    this.progressBar.label = t('Upload progress');
    this.progressBar.hidden = true;
    shade.append(this.progressBar);
    this.container.append(shade);
    this.#progressShade = shade;

    // The bar has no live region of its own, so progress is read out
    // periodically while an upload runs.
    this.#progressAnnouncer = new IntervalManager({
      onInterval: () => announce(this.#progressMessage()),
    });

    if (this.addElementBtn) {
      const label =
        this.settings.limit == 1 ? t('Upload a file') : t('Upload files');

      this.uploadBtn = document.createElement('button');
      this.uploadBtn.type = 'button';
      this.uploadBtn.className = 'btn dashed';
      this.uploadBtn.setAttribute('aria-label', label);
      if (this.settings.describedBy) {
        this.uploadBtn.setAttribute(
          'aria-describedby',
          this.settings.describedBy
        );
      }
      const icon = document.createElement('craft-icon');
      icon.setAttribute('name', 'upload');
      this.uploadBtn.append(icon, label);
      this.addElementBtn.after(this.uploadBtn);

      this.fileInput = document.createElement('input');
      this.fileInput.type = 'file';
      this.fileInput.className = 'hidden';
      this.fileInput.multiple = this.settings.limit != 1;
      this.uploadBtn.after(this.fileInput);

      this.addListener(this.uploadBtn, 'click', () => this.fileInput?.click());

      // The field may be inside an element editor HUD that sizes to its content
      window.dispatchEvent(new Event('resize'));
    }

    const options: any = {
      dropZone: [this.container],
      fileInput: this.fileInput ? [this.fileInput] : undefined,
      canAddMoreFiles: this.canAddMoreFiles.bind(this),
      on: {
        start: this._onUploadStart.bind(this),
        progress: this._onUploadProgress.bind(this),
        done: this._onUploadComplete.bind(this),
        fail: this._onUploadFailure.bind(this),
      } satisfies UploaderCallbacks,
    };

    if (this.settings.criteria.kind !== undefined) {
      options.allowedKinds = [this.settings.criteria.kind].flat();
    }

    this.uploader = new Uploader(this.container, options);

    const params: Record<string, unknown> = {fieldId: this.settings.fieldId};
    if (this.settings.sourceElementId) {
      params.elementId = this.settings.sourceElementId;
    }
    if (this.settings.criteria.siteId) {
      params.siteId = this.settings.criteria.siteId;
    }
    this.uploader.setParams(params);
  }

  override enableAddElementsBtn(): void {
    this.uploadBtn?.classList.remove('hidden');
    super.enableAddElementsBtn();
  }

  override disableAddElementsBtn(): void {
    this.uploadBtn?.classList.add('hidden');
    super.disableAddElementsBtn();
  }

  _onUploadStart(): void {
    this.container.classList.add('uploading');
    this.progressBar?.reset();
    this.progressBar?.show();
    this.#progressAnnouncer?.start();
  }

  #hideProgress(): void {
    this.progressBar?.hide();
    this.#progressAnnouncer?.stop();
    this.container.classList.remove('uploading');
  }

  #progressMessage(): string {
    const bar = this.progressBar;
    if (!bar || bar.pending || bar.progress === 100) {
      return t('Processing');
    }

    return t('{num} percent complete', {num: bar.progress});
  }

  _onUploadProgress(
    data: Parameters<NonNullable<UploaderCallbacks['progress']>>[0]
  ): void {
    const progress = Math.round(Math.min(data.loaded / data.total, 1) * 100);
    if (this.progressBar) {
      this.progressBar.progress = progress;
    }
  }

  _onUploadComplete({
    result,
  }: Parameters<NonNullable<UploaderCallbacks['done']>>[0]): void {
    actionClient
      .post('app/render-elements', {
        elements: [
          {
            type: 'CraftCms\\Cms\\Asset\\Elements\\Asset',
            id: result.assetId,
            siteId: this.settings.criteria.siteId,
            instances: [
              {
                context: 'field',
                ui: ['list', 'list-inline', 'large', 'thumbs'].includes(
                  this.settings.viewMode
                )
                  ? 'chip'
                  : 'card',
                size: ['large', 'thumbs'].includes(this.settings.viewMode)
                  ? 'large'
                  : 'small',
                showActionMenu: this.settings.showActionMenu,
              },
            ],
          },
        ],
      })
      .then(async ({data}: any) => {
        const info = getElementInfo(data.elements[result.assetId][0]);
        await this.selectElements([
          {...info, id: Number(info.id), siteId: info.siteId},
        ]);

        await appendHeadHtml(data.headHtml);
        await appendBodyHtml(data.bodyHtml);

        if (this.uploader?.isLastUpload()) {
          this.#hideProgress();
        }
      })
      .catch((error: any) => {
        if (error?.response) {
          showError(error.response.data.message);
        } else {
          showError();
          throw error;
        }
      });

    void QueueService.getInstance().runQueue();
  }

  _onUploadFailure({
    error,
    canceled,
    file,
  }: Parameters<NonNullable<UploaderCallbacks['fail']>>[0]): void {
    if (canceled) {
      this.#hideProgress();
      return;
    }

    const response = error instanceof UploadError ? error.data : {};

    let message = error instanceof Error ? error.message : undefined;
    const filename = response.filename || file?.name;
    const {errors} = response || {};

    // SAFETY: The upload endpoint returns arrays of validation message strings.
    const errorMessages: string[] = errors
      ? (Object.values(errors).flat() as string[])
      : [];

    if (!message) {
      if (errorMessages.length) {
        message = errorMessages.join('\n');
      } else if (filename) {
        message = t('Upload failed for “{filename}”.', {filename});
      } else {
        message = t('Upload failed.');
      }
    }

    showError(message);
    this.#hideProgress();
  }

  canAddMoreFiles(slotsTaken: number): boolean {
    return (
      !this.settings.limit ||
      this.elements.length + slotsTaken < this.settings.limit
    );
  }
}
