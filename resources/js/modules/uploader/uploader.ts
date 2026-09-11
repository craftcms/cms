import Uppy from '@uppy/core';
import DropTarget from '@uppy/drop-target';
import {t} from '@craftcms/ui';
import './uploader.css';
import {
  BaseUploader,
  type UploadControls,
  type UploaderSettings,
} from './base-uploader';
import {UploadQueue} from './upload-queue';
import {store} from '@/routes/craft/actions/craft/cp/uploads';

declare const Craft: any;

/** Native file selection and Uppy drop targets feeding Craft upload sessions. */
export class Uploader extends BaseUploader {
  private selectionCount = 0;
  private rejectedFiles: string[] = [];
  private picker: Uppy | null = null;
  private listeners = new AbortController();
  private inputAccept = new Map<HTMLInputElement, string>();
  private dropZones: HTMLElement[] = [];
  private destroyed = false;
  private uploads = new UploadQueue();

  static get defaults(): UploaderSettings {
    return {
      url: store.url(),
      maxFileSize: Craft.maxAssetUploadSize,
    };
  }

  constructor(element: HTMLElement, settings: UploaderSettings = {}) {
    super(element, {...Uploader.defaults, ...settings});
    this.createPicker();
  }

  private createPicker(): void {
    const inputs = [
      ...new Set(
        [this.settings.fileInput ?? this.element]
          .flat()
          .flatMap((element) =>
            element instanceof HTMLInputElement && element.type === 'file'
              ? [element]
              : Array.from(
                  element.querySelectorAll<HTMLInputElement>('input[type=file]')
                )
          )
      ),
    ];
    const {allowedKinds} = this.settings;
    const allowedFileTypes: string[] = allowedKinds?.length
      ? allowedKinds
          .flatMap((kind) => Craft.fileKinds[kind]?.extensions ?? [])
          .map((extension: string) => `.${extension}`)
      : inputs.flatMap((input) =>
          input.accept
            .split(',')
            .map((type) => type.trim())
            .filter(Boolean)
        );
    const picker = (this.picker = new Uppy({
      autoProceed: false,
      restrictions: {
        maxFileSize: this.settings.maxFileSize,
        allowedFileTypes:
          allowedFileTypes.length || allowedKinds?.length
            ? allowedFileTypes
            : null,
        maxNumberOfFiles:
          inputs.length && inputs.every((input) => !input.multiple) ? 1 : null,
      },
      locale: {
        pluralize: (count) => (count === 1 ? 0 : 1),
        strings: {
          exceedsSize: t('{file} exceeds the maximum allowed size of {size}.', {
            file: '%{file}',
            size: '%{size}',
          }),
          youCanOnlyUploadFileTypes: t('Allowed file types: {types}', {
            types: '%{types}',
          }),
          youCanOnlyUploadX: {
            0: t('You can only upload one file.'),
            1: t('You can only upload {count} files.', {
              count: '%{smart_count}',
            }),
          },
          noDuplicates: t('The file “{filename}” has already been added.', {
            filename: '%{fileName}',
          }),
        },
      },
    }));
    picker.on('restriction-failed', (_file, error) => {
      if (!this.destroyed) {
        Craft.cp.displayError(error.message);
      }
    });
    picker.on('files-added', (files) => {
      if (this.destroyed) {
        return;
      }
      const originalFiles = files.map((file) => file.data as File);
      picker.removeFiles(files.map((file) => file.id));
      this.selectionCount = 0;
      try {
        for (const file of originalFiles) {
          this.acceptFile(file, originalFiles);
        }
      } finally {
        this.selectionCount = 0;
        if (this.rejectedFiles.length) {
          Craft.cp.displayError(
            t(
              this.rejectedFiles.length === 1
                ? 'The file {files} could not be uploaded, because the field limit has been reached.'
                : 'The files {files} could not be uploaded, because the field limit has been reached.',
              {files: this.rejectedFiles.join(', ')}
            )
          );
          this.rejectedFiles = [];
        }
      }
    });

    for (const input of inputs) {
      this.inputAccept.set(input, input.accept);
      if (allowedFileTypes.length) {
        input.accept = allowedFileTypes.join(',');
      }
      input.addEventListener(
        'change',
        () => {
          if (input.disabled) {
            return;
          }
          const files = Array.from(input.files ?? []);
          input.value = '';
          this.addFiles(files);
        },
        {signal: this.listeners.signal}
      );
    }

    this.dropZones = [this.settings.dropZone ?? []].flat();
    this.dropZones.forEach((target, index) => {
      picker.use(DropTarget, {id: `DropTarget-${index}`, target});
    });
    for (const target of [this.settings.pasteZone ?? []].flat()) {
      target.addEventListener(
        'paste',
        (event) => {
          const files = Array.from(event.clipboardData?.files ?? []);
          if (files.length) {
            event.preventDefault();
            event.stopPropagation();
            this.addFiles(files);
          }
        },
        {signal: this.listeners.signal}
      );
    }
  }

  addFiles(files: File[]): void {
    if (this.destroyed) {
      return;
    }
    this.picker!.addFiles(
      files.map((file) => ({name: file.name, type: file.type, data: file}))
    );
  }

  acceptFile(
    file: File,
    originalFiles: File[] = [file]
  ): UploadControls | undefined {
    if (this.destroyed) {
      return;
    }
    const slotsTaken = this.settings.enqueueUpload
      ? this.selectionCount
      : this.inProgress;
    if (
      this.settings.canAddMoreFiles &&
      !this.settings.canAddMoreFiles(slotsTaken)
    ) {
      this.rejectedFiles.push(`“${file.name}”`);
      return;
    }

    this.selectionCount++;
    if (this.settings.enqueueUpload) {
      this.settings.enqueueUpload(file, originalFiles);
    } else {
      return this.uploadFile(file, originalFiles);
    }
  }

  private uploadFile(file: File, originalFiles: File[]): UploadControls {
    const upload = {
      file,
      originalFiles,
      cancel: () => this.uploads.cancel(job),
      retry: () => this.uploads.retry(job),
    };
    const job = this.uploads.add(
      file,
      {
        url: this.settings.url!,
        parameters: {
          ...this.formData,
          ...(this.settings.replace ? {operation: 'replace'} : {}),
        },
        csrfToken: Craft.csrfTokenValue,
      },
      undefined,
      {
        queued: () => {
          if (++this.inProgress === 1) {
            this.uploadCallbacks.start?.();
          }
        },
        progress: (loaded, total) =>
          this.uploadCallbacks.progress?.({loaded, total}),
        done: (result) => this.uploadCallbacks.done?.({...upload, result}),
        fail: (error, canceled) => {
          if (!this.destroyed) {
            this.uploadCallbacks.fail?.({...upload, error, canceled});
          }
        },
        settled: () => {
          this.inProgress--;
          if (!this.destroyed) {
            this.uploadCallbacks.settled?.(upload);
            if (this.inProgress === 0) {
              this.uploadCallbacks.stop?.();
            }
          }
        },
      }
    );
    return upload;
  }

  override destroy(): void {
    this.destroyed = true;
    void this.uploads.cancelAll();

    this.listeners.abort();
    this.picker?.destroy();
    this.picker = null;
    for (const [input, accept] of this.inputAccept) {
      input.accept = accept;
    }
    this.inputAccept.clear();
    for (const target of this.dropZones) {
      target.classList.remove('uppy-is-drag-over');
    }
  }
}
