export interface UploadSelection {
  file: File;
  originalFiles: File[];
}

export interface UploadControls {
  cancel: () => Promise<void>;
  retry: () => void;
}

export interface UploaderCallbacks {
  start?: () => void;
  progress?: (progress: {loaded: number; total: number}) => void;
  done?: (upload: UploadSelection & UploadControls & {result: any}) => void;
  fail?: (
    upload: UploadSelection &
      UploadControls & {error: unknown; canceled: boolean}
  ) => void;
  settled?: (upload: UploadSelection & UploadControls) => void;
  stop?: () => void;
}

export type UploadTarget = HTMLElement | HTMLElement[];

export interface UploaderSettings {
  fileInput?: UploadTarget | null;
  dropZone?: UploadTarget | null;
  pasteZone?: UploadTarget | null;
  maxFileSize?: number | null;
  allowedKinds?: string[] | null;
  url?: string;
  formData?: Record<string, unknown>;
  replace?: boolean;
  on?: UploaderCallbacks;
  canAddMoreFiles?: (slotsTaken: number) => boolean;
  enqueueUpload?: (file: File, selection: File[]) => void;
}

/** Upload settings and lifecycle callbacks. */
export class BaseUploader {
  inProgress = 0;
  formData: Record<string, unknown>;
  uploadCallbacks: UploaderCallbacks;

  constructor(
    public readonly element: HTMLElement,
    public readonly settings: UploaderSettings = {}
  ) {
    this.formData = settings.formData ?? {};
    this.uploadCallbacks = {...settings.on};
  }

  setParams(parameters: Record<string, unknown>): void {
    this.formData = parameters;
  }

  getInProgress(): number {
    return this.inProgress;
  }

  isLastUpload(): boolean {
    return this.inProgress < 2;
  }

  destroy(): void {}
}
