import {actionClient, t} from '@craftcms/ui';
import {shallowRef, triggerRef, watch} from 'vue';
import AssistantController from '@/actions/CraftCms/Cms/Ai/Http/Controllers/AssistantController';
import {assistantApi} from '@/bootstrap/assistant';
import {useMessages} from '@/modules/messages/useMessages';
import {namePastedFiles} from '@/modules/uploader/pasted-files';
import {FileUpload} from '@/upload-client';
import {useAssistantThread} from './assistant';

/** The type of the items for files uploaded to the conversation. */
export const UPLOAD_CONTEXT_TYPE = 'craft:upload';

type UploadResult = Omit<CraftCms.Cms.Asset.Data.UploadResult, 'status'> & {
  contextItem?: {icon?: string};
};

interface AssistantUpload {
  task: FileUpload<UploadResult>;
  threadId: string;
  /** The uploaded asset’s ID, once it’s saved. */
  assetId: number | null;
  /** A preview of the file, if it’s an image, from the browser’s copy of it. */
  preview: string | null;
  done: Promise<void>;
}

/** Uploads in the composer, by their context items’ refs. */
const uploads = shallowRef(new Map<string, AssistantUpload>());

/** What happened to the latest upload, for screen readers. */
export const uploadStatus = shallowRef('');

/** Thumbnails of uploaded images, by their asset IDs, for the chips of the messages they were sent with. */
const thumbnails = shallowRef(new Map<number, string>());

/** The conversations whose thumbnails have been loaded. */
const loadedThumbnails = new Set<string>();

/** The thumbnail of an uploaded image, if there is one. Reactive. */
export function uploadThumbnail(assetId: unknown): string | null {
  return typeof assetId === 'number'
    ? (thumbnails.value.get(assetId) ?? null)
    : null;
}

/**
 * Loads the thumbnails of the images uploaded to a conversation, for its
 * messages’ chips after the page has reloaded.
 */
export async function loadUploadThumbnails(threadId: string): Promise<void> {
  if (loadedThumbnails.has(threadId)) {
    return;
  }

  loadedThumbnails.add(threadId);

  try {
    const {data} = await actionClient.get<{thumbnails: Record<string, string>}>(
      AssistantController.uploadThumbnails.url(),
      {params: {threadId}}
    );
    const next = new Map(thumbnails.value);

    for (const [assetId, url] of Object.entries(data.thumbnails)) {
      // The browser’s own preview is kept, as it’s already loaded.
      if (!next.has(Number(assetId))) {
        next.set(Number(assetId), url);
      }
    }

    thumbnails.value = next;
  } catch {
    loadedThumbnails.delete(threadId);
  }
}

const messages = useMessages();

/**
 * Uploads files to the conversation, adding each to the context as it starts,
 * so it’s sent with the next message.
 */
export function uploadFiles(files: readonly File[]): void {
  const thread = useAssistantThread();
  const threadId = thread.value;
  watchThread(thread);

  for (const file of namePastedFiles([...files])) {
    const task = new FileUpload<UploadResult>(file, {
      url: AssistantController.upload.url(),
      parameters: {threadId},
    });

    let ref = '';
    const upload: AssistantUpload = {
      task,
      threadId,
      assetId: null,
      preview: file.type.startsWith('image/')
        ? URL.createObjectURL(file)
        : null,
      done: Promise.resolve(),
    };

    ref = assistantApi.addContext({
      type: UPLOAD_CONTEXT_TYPE,
      label: file.name,
      icon: file.type.startsWith('image/') ? 'image' : 'file',
      thumbnail: upload.preview ?? undefined,
      data: () => (upload.assetId ? {assetId: upload.assetId} : {}),
      reference: () => (upload.assetId ? {assetId: upload.assetId} : {}),
      status: () =>
        uploads.value.get(ref) && !uploads.value.get(ref)!.assetId
          ? {fill: 'gray', label: t('Uploading')}
          : null,
    });

    upload.done = task
      .upload()
      .then((result) => {
        if (!result.assetId) {
          throw new Error(result.message || t('Couldn’t upload file.'));
        }

        upload.assetId = result.assetId;
        triggerRef(uploads);

        if (upload.preview) {
          thumbnails.value = new Map(thumbnails.value).set(
            result.assetId,
            upload.preview
          );
        }
        uploadStatus.value = t('Uploaded {name}.', {name: file.name});
      })
      .catch((error: unknown) => {
        forget(ref);

        if (task.state !== 'canceled') {
          messages.error(
            error instanceof Error ? error.message : t('Couldn’t upload file.')
          );
        }
      });

    uploads.value.set(ref, upload);
    triggerRef(uploads);
    uploadStatus.value = t('Uploading {name}…', {name: file.name});
  }
}

/** Whether any of the composer’s files are still uploading. */
export function isUploading(): boolean {
  return [...uploads.value.values()].some(({assetId}) => !assetId);
}

/**
 * Waits for the composer’s files to finish uploading, before a message is sent
 * with them, including files added while it waits.
 */
export async function finishUploads(): Promise<void> {
  while (isUploading()) {
    await Promise.all([...uploads.value.values()].map(({done}) => done));
  }
}

/** Forgets the composer’s uploads once they’ve been sent with a message. */
export function forgetUploads(): void {
  uploads.value = new Map();
}

/**
 * Removes an upload from the composer, canceling it, or deleting the file if
 * it’s been saved.
 */
export async function removeUpload(ref: string): Promise<void> {
  const upload = uploads.value.get(ref);
  forget(ref);

  if (!upload) {
    return;
  }

  if (upload.preview) {
    URL.revokeObjectURL(upload.preview);
  }

  if (!upload.assetId) {
    try {
      await upload.task.cancel();

      return;
    } catch {
      // It can’t be canceled once it’s being saved, so it’s deleted once it is.
      await upload.done;
    }

    if (!upload.assetId) {
      return;
    }
  }

  await actionClient
    .post(AssistantController.removeUpload.url(), {
      threadId: upload.threadId,
      assetId: upload.assetId,
    })
    .catch(() => undefined);
}

function forget(ref: string): void {
  assistantApi.removeContext(ref);

  if (uploads.value.delete(ref)) {
    triggerRef(uploads);
  }
}

let watchingThread = false;

/**
 * Removes the composer’s uploads when the user switches to another
 * conversation, as they belong to the one they were uploaded to.
 */
function watchThread(thread: ReturnType<typeof useAssistantThread>): void {
  if (watchingThread) {
    return;
  }

  watchingThread = true;
  watch(thread, (threadId) => {
    for (const [ref, upload] of uploads.value) {
      if (upload.threadId !== threadId) {
        void removeUpload(ref);
      }
    }
  });
}

/** Opens the file picker, and uploads the chosen files. */
function chooseFiles(): void {
  const input = document.createElement('input');
  input.type = 'file';
  input.multiple = true;
  input.addEventListener('change', () => {
    if (input.files?.length) {
      uploadFiles([...input.files]);
    }
  });
  input.click();
}

assistantApi.registerContextSource({
  id: UPLOAD_CONTEXT_TYPE,
  label: () => t('Upload files'),
  icon: 'upload',
  order: 100,
  add: chooseFiles,
});
