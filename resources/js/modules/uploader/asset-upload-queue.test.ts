import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {UploadOptions, UploadState} from '@/upload-client';
import type {UploadConflictChoice} from './upload-notification';
import type {FileUploadNotificationElement} from './file-upload-notification.ce';

const ui = vi.hoisted(() => ({on: vi.fn(), post: vi.fn()}));
vi.mock('@inertiajs/vue3', () => ({router: {on: ui.on}}));
vi.mock('@craftcms/ui', () => ({
  t: (message: string, parameters?: {num: number}) =>
    parameters?.num ? `${parameters.num} files uploaded.` : message,
  actionClient: {post: ui.post},
}));

const notices: HTMLElement[] = [];
function displayNotice(message: string, {details}: {details: HTMLElement}) {
  const notice = document.createElement('div');
  notice.append(message, details);
  document.body.append(notice);
  notices.push(notice);
  return {
    close: () => notice.remove(),
    on: (_event: string, callback: () => void) => queueMicrotask(callback),
    $closeBtn: {hide: () => {}, on: () => {}},
  };
}

async function clickNotice(index: number, label: string): Promise<void> {
  await notices[index]!.querySelector<FileUploadNotificationElement>(
    'craft-file-upload-notification'
  )!.updateComplete;
  const button = [
    ...notices[index]!.querySelectorAll<HTMLElement>('craft-button'),
  ].find((button) => button.textContent?.trim() === label && !button.hidden);
  expect(button, `Visible ${label} button`).toBeDefined();
  button!.click();
}

const transfers: TestUpload[] = [];
class TestUpload {
  state: UploadState = 'ready';
  result = deferred<CraftCms.Cms.Asset.Data.UploadResult>();
  upload = vi.fn(() => {
    this.state = 'uploading';
    this.options.onStateChange?.(this.state);
    return this.result.promise;
  });
  get canPause(): boolean {
    return this.state === 'uploading';
  }
  pause = vi.fn(() => {
    this.state = 'paused';
    this.options.onStateChange?.(this.state);
  });
  resume = vi.fn(() => {
    this.state = 'uploading';
    this.options.onStateChange?.(this.state);
  });
  cancel = vi.fn(async () => {
    this.state = 'canceled';
    this.result.reject(new Error('Canceled'));
  });
  constructor(
    public file: File,
    public options: UploadOptions
  ) {
    // A queued upload can be canceled before its promise has a consumer.
    void this.result.promise.catch(() => {});
    transfers.push(this);
  }
  complete(result: CraftCms.Cms.Asset.Data.UploadResult = {assetId: 12}): void {
    this.state = 'completed';
    this.options.onStateChange?.(this.state);
    this.result.resolve(result);
  }
  fail(message: string): void {
    this.state = 'failed';
    this.result.reject(new Error(message));
  }
}
vi.mock('@/upload-client', () => ({
  FileUpload: class {
    constructor(file: File, options: UploadOptions) {
      return new TestUpload(file, options);
    }
  },
}));

import {AssetUploadQueue} from './asset-upload-queue';
import {createFileUploadNotification} from './upload-notification';

let queue: AssetUploadQueue;
const destination = {folderId: 7, url: '/admin/assets/images', label: 'Images'};
const file = () => new File(['contents'], 'photo.png');
const conflict = {
  assetId: 21,
  filename: 'photo.png',
  conflict: 'That filename exists.',
  conflictingAssetId: 20,
};

beforeEach(() => {
  ui.on.mockReset().mockReturnValue(vi.fn());
  transfers.length = 0;
  notices.length = 0;
  ui.post.mockReset().mockResolvedValue({data: {}});
  vi.stubGlobal('Craft', {
    csrfTokenValue: 'token',
    cp: {displayNotice, displayError: vi.fn(), runQueue: vi.fn()},
  });
  queue = new AssetUploadQueue();
});

afterEach(async () => {
  await queue.cancelAll();
  document.body.replaceChildren();
  vi.unstubAllGlobals();
});

it.each(['paused', 'failed'] as const)(
  'restarts progress estimates after an upload is %s',
  async (state) => {
    const now = vi.spyOn(Date, 'now').mockReturnValue(1000);
    const notice = createFileUploadNotification(
      new File(['a'.repeat(1000)], 'file.txt'),
      {
        retry: vi.fn(),
        cancel: vi.fn(),
        pause: vi.fn(),
        resume: vi.fn(),
      }
    );
    const details = notices[0]!.querySelector<FileUploadNotificationElement>(
      'craft-file-upload-notification'
    )!;

    try {
      notice.updateState('uploading');
      notice.updateProgress(100);
      expect(details.speed).toBe(0);
      now.mockReturnValue(2000);
      notice.updateProgress(200);
      expect(details.speed).toBe(100);

      notice.updateState(state);
      expect(details.speed).toBe(0);
      now.mockReturnValue(102000);
      notice.updateState('uploading');
      notice.updateProgress(200);
      now.mockReturnValue(103000);
      notice.updateProgress(300);
      expect(details.speed).toBe(100);

      notice.updateProgress(100);
      expect(details.speed).toBe(0);
      now.mockReturnValue(104000);
      notice.updateProgress(200);
      expect(details.speed).toBe(100);
      notice.updateState('completing');
      await details.updateComplete;
      expect(details.speed).toBe(0);
      expect(details.textContent).toContain('Saving file…');
    } finally {
      now.mockRestore();
      notice.close();
    }
  }
);

it('starts selections in parallel and retains their original destinations', async () => {
  const folder = {...destination};
  queue.enqueue(file(), folder, []);
  queue.enqueue(file(), {...destination, folderId: 8}, []);
  folder.folderId = 99;

  await vi.waitFor(() => expect(transfers[0]!.upload).toHaveBeenCalledOnce());
  expect(transfers[1]!.upload).toHaveBeenCalledOnce();
  expect(transfers.map((transfer) => transfer.options.parameters)).toEqual([
    {folderId: 7},
    {folderId: 8},
  ]);

  transfers[0]!.complete();
  transfers[1]!.complete();
  await vi.waitFor(() => expect(queue.hasPending).toBe(false));
  expect(transfers[0]!.cancel).not.toHaveBeenCalled();
});

it('pauses and resumes from the notification while other files keep uploading', async () => {
  queue.enqueue(file(), destination, []);
  queue.enqueue(file(), destination, []);
  await vi.waitFor(() => expect(transfers[0]!.upload).toHaveBeenCalledOnce());

  await clickNotice(0, 'Pause');
  expect(transfers[0]!.pause).toHaveBeenCalledOnce();
  expect(queue.hasPending).toBe(true);
  expect(transfers[1]!.upload).toHaveBeenCalledOnce();
  const leaving = new Event('beforeunload', {cancelable: true});
  window.dispatchEvent(leaving);
  expect(leaving.defaultPrevented).toBe(true);

  await clickNotice(0, 'Resume');
  expect(transfers[0]!.resume).toHaveBeenCalledOnce();
  expect(transfers[0]!.upload).toHaveBeenCalledOnce();
  transfers[0]!.complete();
  transfers[1]!.complete();
  await vi.waitFor(() => expect(queue.hasPending).toBe(false));
});

it('leaves failures actionable, continues the queue, and retries the same transfer', async () => {
  queue.enqueue(file(), destination, []);
  queue.enqueue(file(), destination, []);
  await vi.waitFor(() => expect(transfers[0]!.upload).toHaveBeenCalled());
  transfers[0]!.fail('You no longer have permission to upload here.');
  await vi.waitFor(() => expect(transfers[1]!.upload).toHaveBeenCalled());
  await vi.waitFor(() =>
    expect(notices[0]!.textContent).toContain(
      'You no longer have permission to upload here.'
    )
  );
  transfers[0]!.result = deferred();
  await clickNotice(0, 'Retry');
  await vi.waitFor(() => expect(transfers[0]!.upload).toHaveBeenCalledTimes(2));
  transfers[1]!.complete();
  transfers[0]!.complete();
  await vi.waitFor(() => expect(queue.hasPending).toBe(false));
});

it('does not interrupt navigation with a conflict dialog or block later uploads', async () => {
  queue.enqueue(file(), destination, []);
  queue.enqueue(file(), destination, []);
  await vi.waitFor(() => expect(transfers[0]!.upload).toHaveBeenCalled());
  transfers[0]!.complete(conflict);
  await vi.waitFor(() => expect(transfers[1]!.upload).toHaveBeenCalled());
  expect(document.querySelector('craft-dialog')).toBeNull();
  expect(notices[0]!.isConnected).toBe(true);
  await vi.waitFor(() =>
    expect(notices[0]!.textContent).toContain(conflict.conflict)
  );

  await clickNotice(0, 'Action required');
  expect(document.querySelector('craft-dialog')!.textContent).toContain(
    conflict.conflict
  );
  choose('keepBoth');
  transfers[1]!.complete();
  await vi.waitFor(() => expect(queue.hasPending).toBe(false));
  expect(ui.post).not.toHaveBeenCalled();
});

it.each([
  [conflict, {sourceAssetId: 21, assetId: 20}],
  [
    {...conflict, conflictingAssetId: null},
    {sourceAssetId: 21, targetFilename: 'photo.png'},
  ],
])(
  'resolves an indexed or unindexed filename conflict only on request',
  async (result, parameters) => {
    await uploadConflict(result);
    await clickNotice(0, 'Action required');
    choose('replace');
    await vi.waitFor(() => expect(queue.hasPending).toBe(false));
    expect(ui.post).toHaveBeenCalledWith(
      '/admin/actions/assets/resolve-upload-conflict',
      parameters
    );
  }
);

it('retains a conflict and its error when replacement fails', async () => {
  await uploadConflict();
  ui.post.mockRejectedValue(new Error('The destination was deleted.'));
  await clickNotice(0, 'Action required');
  choose('replace');
  await vi.waitFor(() =>
    expect(notices[0]!.textContent).toContain('The destination was deleted.')
  );
  expect(queue.hasPending).toBe(true);
});

it('discards the uploaded copy when canceling a conflict', async () => {
  await uploadConflict();
  await clickNotice(0, 'Cancel');
  await vi.waitFor(() => expect(queue.hasPending).toBe(false));
  expect(ui.post).toHaveBeenCalledWith('/admin/actions/assets/delete-asset', {
    assetId: 21,
  });
});

it('cancels one file without interrupting parallel transfers', async () => {
  queue.enqueue(file(), destination, []);
  queue.enqueue(file(), destination, []);
  await clickNotice(1, 'Cancel');
  transfers[0]!.complete();
  await vi.waitFor(() => expect(queue.hasPending).toBe(false));
  expect(transfers[1]!.upload).toHaveBeenCalledOnce();
  expect(transfers[1]!.cancel).toHaveBeenCalledOnce();
});

it('clears queued work without canceling a final save or displaying its result', async () => {
  queue.enqueue(file(), destination, []);
  queue.enqueue(file(), destination, []);
  await vi.waitFor(() => expect(transfers[0]!.upload).toHaveBeenCalled());
  transfers[0]!.state = 'completing';
  const changed = vi.fn();
  queue.addEventListener('change', changed);

  await queue.cancelAll();
  expect(queue.hasPending).toBe(false);
  expect(transfers[0]!.cancel).not.toHaveBeenCalled();
  expect(transfers[1]!.cancel).toHaveBeenCalledOnce();
  transfers[0]!.complete();
  await Promise.resolve();
  expect(changed).not.toHaveBeenCalled();
  expect(document.body.textContent).not.toContain('Upload complete.');
  expect(transfers[1]!.upload).toHaveBeenCalledOnce();
});

it('groups completed files from one selection into one destination notice', async () => {
  const selection: File[] = [];
  queue.enqueue(file(), destination, selection);
  queue.enqueue(file(), destination, selection);
  transfers[0]!.complete();
  await vi.waitFor(() => expect(transfers[1]!.upload).toHaveBeenCalled());
  transfers[1]!.complete();
  await vi.waitFor(() => expect(queue.hasPending).toBe(false));

  expect(document.body.textContent).toContain('2 files uploaded.');
  const links = document.querySelectorAll('a');
  expect(links).toHaveLength(1);
  expect(links[0]!.getAttribute('href')).toBe(destination.url);
  expect(links[0]!.textContent?.trim()).toBe(destination.label);
});

it('keeps uploads on ordinary visits and cancels them on authentication visits', async () => {
  queue.enqueue(file(), destination, []);
  const navigate = ui.on.mock.calls[0]![1];
  navigate({detail: {page: {component: 'entries/Edit'}}});
  expect(queue.hasPending).toBe(true);
  navigate({detail: {page: {component: 'auth/Login'}}});
  await vi.waitFor(() => expect(queue.hasPending).toBe(false));
  expect(transfers[0]!.cancel).toHaveBeenCalledOnce();
  const idle = new Event('beforeunload', {cancelable: true});
  window.dispatchEvent(idle);
  expect(idle.defaultPrevented).toBe(false);
});

async function uploadConflict(
  result: CraftCms.Cms.Asset.Data.UploadResult = conflict
): Promise<void> {
  queue.enqueue(file(), destination, []);
  await vi.waitFor(() => expect(transfers[0]!.upload).toHaveBeenCalled());
  transfers[0]!.complete(result);
  await vi.waitFor(() =>
    expect(notices[0]!.textContent).toContain(result.conflict!)
  );
}

function choose(choice: UploadConflictChoice): void {
  const label = {
    keepBoth: 'Keep both',
    replace: 'Replace',
    cancel: 'Cancel upload',
  }[choice];
  const button = [
    ...document.querySelectorAll<HTMLElement>('craft-dialog craft-button'),
  ].find((button) => button.textContent?.trim() === label);
  expect(button).toBeDefined();
  button!.click();
}

function deferred<T>(): {
  promise: Promise<T>;
  resolve: (value: T) => void;
  reject: (reason: Error) => void;
} {
  let resolve!: (value: T) => void;
  let reject!: (reason: Error) => void;
  const promise = new Promise<T>((success, failure) => {
    resolve = success;
    reject = failure;
  });
  return {promise, resolve, reject};
}
